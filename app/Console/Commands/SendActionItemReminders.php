<?php

namespace App\Console\Commands;

use App\Enums\ActionItemStatus;
use App\Models\ActionItem;
use App\Notifications\ActionItemDueSoonNotification;
use Illuminate\Console\Command;

class SendActionItemReminders extends Command
{
    protected $signature = 'mom:remind-action-items';

    protected $description = 'Kirim pengingat H-3, H-1, dan keterlambatan action item';

    public function handle(): int
    {
        $sent = 0;

        ActionItem::query()
            ->with(['pic', 'division.head', 'meeting'])
            ->whereNotNull('due_date')
            ->whereNotIn('status', [ActionItemStatus::Done->value, ActionItemStatus::Cancelled->value])
            ->chunkById(100, function ($items) use (&$sent) {
                foreach ($items as $item) {
                    $today = today();
                    $due = $item->due_date;

                    if ($due->toDateString() === $today->copy()->addDays(3)->toDateString() && ! $item->reminder_h3_sent_at) {
                        $this->notify($item, 'h3');
                        $item->update(['reminder_h3_sent_at' => now()]);
                        $sent++;
                    }

                    if ($due->toDateString() === $today->copy()->addDays(1)->toDateString() && ! $item->reminder_h1_sent_at) {
                        $this->notify($item, 'h1');
                        $item->update(['reminder_h1_sent_at' => now()]);
                        $sent++;
                    }

                    if ($due->lt($today) && (! $item->overdue_notified_at || $item->overdue_notified_at->toDateString() !== $today->toDateString())) {
                        $this->notify($item, 'overdue');

                        if ($item->division?->head && $item->division->head->id !== $item->pic_id) {
                            $item->division->head->notify(new ActionItemDueSoonNotification($item, 'overdue'));
                        }

                        $item->update(['overdue_notified_at' => now()]);
                        $sent++;
                    }
                }
            });

        $this->info("Pengingat action item dikirim: {$sent}");

        return self::SUCCESS;
    }

    private function notify(ActionItem $item, string $kind): void
    {
        $item->pic?->notify(new ActionItemDueSoonNotification($item, $kind));
    }
}
