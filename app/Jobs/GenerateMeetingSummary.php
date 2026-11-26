<?php

namespace App\Jobs;

use App\Models\AiSummary;
use App\Notifications\AiSummaryReadyNotification;
use App\Services\Ai\AiSummaryService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateMeetingSummary implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    /** @var array<int, int> */
    public array $backoff = [30, 120];

    public int $uniqueFor = 300;

    public function __construct(public AiSummary $summary, public string $tier = 'auto')
    {
        $this->onQueue('ai');
    }

    public function uniqueId(): string
    {
        return 'meeting-summary-'.$this->summary->id;
    }

    public function handle(AiSummaryService $service): void
    {
        try {
            $summary = $service->generate($this->summary, $this->tier);
        } catch (\Throwable $exception) {
            Log::error('Gagal membuat resume AI', [
                'summary_id' => $this->summary->id,
                'meeting_id' => $this->summary->meeting_id,
                'attempt' => $this->attempts(),
                'error' => $exception->getMessage(),
            ]);

            $this->summary->forceFill([
                'generation_status' => 'failed',
                'error_message' => mb_substr($exception->getMessage(), 0, 1000),
                'completed_at' => now(),
            ])->save();

            throw $exception;
        }

        try {
            $summary->generator?->notify(new AiSummaryReadyNotification($summary));
        } catch (\Throwable $exception) {
            Log::error('Resume AI selesai tetapi notifikasi gagal dikirim', [
                'summary_id' => $summary->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function failed(?\Throwable $exception): void
    {
        Log::critical('Resume AI gagal setelah seluruh percobaan', [
            'summary_id' => $this->summary->id,
            'meeting_id' => $this->summary->meeting_id,
            'error' => $exception?->getMessage(),
        ]);
    }
}
