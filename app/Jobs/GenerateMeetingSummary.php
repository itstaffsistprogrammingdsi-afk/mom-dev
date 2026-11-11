<?php

namespace App\Jobs;

use App\Models\AiSummary;
use App\Notifications\AiSummaryReadyNotification;
use App\Services\Ai\AiSummaryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class GenerateMeetingSummary implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(public AiSummary $summary, public string $tier = 'auto') {}

    public function handle(AiSummaryService $service): void
    {
        try {
            $summary = $service->generate($this->summary, $this->tier);

            $summary->generator?->notify(new AiSummaryReadyNotification($summary));
        } catch (\Throwable $exception) {
            Log::error('Gagal membuat resume AI', [
                'summary_id' => $this->summary->id,
                'error' => $exception->getMessage(),
            ]);

            $this->summary->forceFill([
                'generation_status' => 'failed',
                'error_message' => mb_substr($exception->getMessage(), 0, 1000),
                'completed_at' => now(),
            ])->save();
        }
    }
}
