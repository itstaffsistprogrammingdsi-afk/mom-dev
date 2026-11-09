<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;

class OpenAiCompatibleClient implements AiClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly ?string $apiKey,
        private readonly string $model,
        private readonly int $timeout,
        private readonly int $maxTokens,
    ) {}

    public function summarizeMeeting(array $payload): array
    {
        if (blank($this->apiKey)) {
            throw new AiException('AI_API_KEY belum dikonfigurasi.');
        }

        $system = <<<'PROMPT'
Anda adalah asisten notulensi rapat berbahasa Indonesia. Tugas Anda merangkum isi rapat menjadi JSON valid.
Gunakan hanya informasi yang ada pada data rapat. Jangan mengarang keputusan, peserta, atau tenggat.
Jika informasi tidak tersedia, gunakan null atau array kosong. Tandai ketidakpastian dengan kata "perlu dikonfirmasi".
Kembalikan hanya JSON tanpa markdown, dengan skema:
{
  "ringkasan": "ringkasan 2-4 kalimat",
  "poin": ["poin pembahasan penting"],
  "keputusan": ["keputusan rapat"],
  "action_items": [
    {"title": "tugas", "description": "detail atau null", "pic_name": "nama PIC atau null", "division_name": "nama divisi atau null", "due_date": "YYYY-MM-DD atau null"}
  ],
  "saran_tag": {"orang": [], "divisi": [], "topik": []}
}
PROMPT;

        $user = "Data rapat:\n".json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $response = Http::withToken($this->apiKey)
            ->timeout($this->timeout)
            ->acceptJson()
            ->post(rtrim($this->baseUrl, '/').'/chat/completions', [
                'model' => $this->model,
                'temperature' => 0.2,
                'max_tokens' => $this->maxTokens,
                'response_format' => ['type' => 'json_object'],
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ],
            ]);

        if ($response->failed()) {
            throw new AiException('Layanan AI gagal: '.$response->status().' '.$response->body());
        }

        $content = data_get($response->json(), 'choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new AiException('Respons AI kosong.');
        }

        $content = trim($content);
        $content = preg_replace('/^```(?:json)?\s*/i', '', $content) ?? $content;
        $content = preg_replace('/\s*```$/', '', $content) ?? $content;

        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            throw new AiException('Respons AI bukan JSON yang valid.');
        }

        return $decoded;
    }
}
