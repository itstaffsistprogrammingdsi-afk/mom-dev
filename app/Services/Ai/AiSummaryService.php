<?php

namespace App\Services\Ai;

use App\Enums\AiSummaryStatus;
use App\Enums\Confidentiality;
use App\Models\AiSummary;
use App\Models\Meeting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AiSummaryService
{
    public function generate(AiSummary $summary, string $tier = 'auto'): AiSummary
    {
        $meeting = $summary->meeting;
        $user = $summary->generator;

        if (! $meeting || ! $user || ! Meeting::query()->visibleTo($user)->whereKey($meeting->id)->exists()) {
            throw new AiException('Pengguna tidak berhak memproses notulen ini.');
        }

        if ($meeting->confidentiality === Confidentiality::Secret && ! config('ai.allow_secret')) {
            throw new AiException('AI dinonaktifkan untuk notulen rahasia.');
        }

        $summary->update([
            'generation_status' => 'processing',
            'started_at' => now(),
            'error_message' => null,
        ]);

        try {
            $model = $this->model($tier);
            $payload = $this->payload($meeting);
            $contentText = (string) ($payload['content_text'] ?? '');
            $contentHash = hash('sha256', $contentText);
            $promptHash = hash('sha256', (string) json_encode($payload, JSON_UNESCAPED_UNICODE));
            $cacheKey = 'ai-summary:'.hash('sha256', $tier.'|'.$model.'|'.$promptHash);
            $raw = config('ai.driver') === 'mock' ? null : Cache::get($cacheKey);

            if (! is_array($raw)) {
                $raw = $this->client($tier)->summarizeMeeting($payload);

                if (config('ai.driver') !== 'mock') {
                    Cache::put($cacheKey, $raw, now()->addHours(6));
                }
            }

            $content = $this->normalize($raw);

            $summary->update([
                'content' => $content,
                'sources' => [['meeting_id' => $meeting->id, 'title' => $meeting->title, 'url' => route('meetings.show', $meeting->id)]],
                'usage' => [
                    'content_hash' => $contentHash,
                    'prompt_hash' => $promptHash,
                    'input_chars' => mb_strlen($contentText),
                    'estimated_input_tokens' => (int) ceil(mb_strlen($contentText) / 4),
                    'tier' => $tier,
                ],
                'model' => config('ai.driver') === 'mock' ? 'mock' : $model,
                'status' => AiSummaryStatus::Draft,
                'generation_status' => 'completed',
                'error_message' => null,
                'completed_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            $summary->update([
                'generation_status' => 'failed',
                'error_message' => Str::limit($exception->getMessage(), 1000),
                'completed_at' => now(),
            ]);

            throw $exception;
        }

        return $summary->fresh();
    }

    private function client(string $tier): AiClient
    {
        if (config('ai.driver') === 'mock' || blank(config('ai.api_key'))) {
            return new MockAiClient;
        }

        return new OpenAiCompatibleClient(
            (string) config('ai.base_url'),
            config('ai.api_key'),
            $this->model($tier),
            (int) config('ai.timeout'),
            (int) config('ai.max_tokens'),
        );
    }

    private function model(string $tier): string
    {
        $model = config("ai.models.$tier");

        return (string) ($model ?: config('ai.model'));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Meeting $meeting): array
    {
        $meeting->loadMissing(['participants.user:id,name', 'tags', 'division:id,name']);

        return [
            'title' => $meeting->title,
            'meeting_type' => $meeting->meeting_type?->value,
            'scheduled_at' => $meeting->scheduled_at?->toIso8601String(),
            'division' => $meeting->division?->name,
            'agenda' => $meeting->agenda,
            'content_text' => Str::limit((string) $meeting->content_text, (int) config('ai.max_input_chars', 12000), ''),
            'participants' => $meeting->participants->map(fn ($participant) => $participant->user?->name ?? $participant->name)->filter()->values()->all(),
            'tags' => $meeting->tags->map(fn ($tag) => ['type' => $tag->type, 'name' => $tag->target_name])->values()->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    private function normalize(array $raw): array
    {
        $this->assertValid($raw);

        $points = Arr::get($raw, 'poin') ?? Arr::get($raw, 'poin_penting') ?? [];
        $decisions = Arr::get($raw, 'keputusan') ?? [];
        $actions = Arr::get($raw, 'action_items') ?? Arr::get($raw, 'usulan_action_item') ?? [];

        return [
            'ringkasan' => (string) (Arr::get($raw, 'ringkasan') ?? ''),
            'poin' => array_values(array_filter((array) $points, 'is_string')),
            'keputusan' => array_values(array_filter((array) $decisions, 'is_string')),
            'action_items' => collect((array) $actions)
                ->filter(fn ($item) => is_array($item) && filled($item['title'] ?? null))
                ->map(fn (array $item) => [
                    'title' => (string) $item['title'],
                    'description' => $item['description'] ?? null,
                    'pic_name' => $item['pic_name'] ?? null,
                    'division_name' => $item['division_name'] ?? null,
                    'due_date' => $item['due_date'] ?? null,
                ])
                ->values()
                ->all(),
            'saran_tag' => (array) (Arr::get($raw, 'saran_tag') ?? []),
        ];
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    private function assertValid(array $raw): void
    {
        if (isset($raw['ringkasan']) && ! is_string($raw['ringkasan'])) {
            throw new AiException('Respons AI tidak valid: ringkasan bukan teks.');
        }

        foreach (['poin', 'poin_penting', 'keputusan'] as $key) {
            if (! isset($raw[$key])) {
                continue;
            }

            if (! is_array($raw[$key])) {
                throw new AiException('Respons AI tidak valid: '.$key.' bukan array.');
            }

            foreach ($raw[$key] as $item) {
                if (! is_string($item)) {
                    throw new AiException('Respons AI tidak valid: '.$key.' berisi item bukan teks.');
                }
            }
        }

        if (isset($raw['action_items']) && ! is_array($raw['action_items'])) {
            throw new AiException('Respons AI tidak valid: action_items bukan array.');
        }

        foreach ((array) ($raw['action_items'] ?? []) as $item) {
            if (! is_array($item)) {
                throw new AiException('Respons AI tidak valid: action item bukan objek.');
            }

            if (isset($item['title']) && ! is_string($item['title'])) {
                throw new AiException('Respons AI tidak valid: judul action item bukan teks.');
            }
        }

        if (blank($raw['ringkasan'] ?? null)
            && empty($raw['poin'])
            && empty($raw['poin_penting'])
            && empty($raw['keputusan'])
            && empty($raw['action_items'])) {
            throw new AiException('Respons AI tidak memuat ringkasan, poin, keputusan, atau action item.');
        }
    }
}
