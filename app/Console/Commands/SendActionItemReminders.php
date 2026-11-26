<?php

namespace App\Console\Commands;

use App\Enums\ActionItemStatus;
use App\Models\ActionItem;
use App\Notifications\ActionItemDueSoonNotification;
use Illuminate\Console\Command;

class SendActionItemReminders extends Command
{
    protected $signature = 'mom:remind-action-items';

    protected $description = 'Kirim pengingat H-3, H-1, dan keterlambatan action item dengan catch-up';

    public function handle(): int
    {
        $sent = 0;
        $today = today();

        ActionItem::query()
            ->with(['pic', 'division.head', 'meeting'])
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', $today->copy()->addDays(3)->toDateString())
            ->whereNotIn('status', [ActionItemStatus::Done->value, ActionItemStatus::Cancelled->value])
            ->chunkById(100, function ($items) use (&$sent, $today) {
                foreach ($items as $item) {
                    $due = $item->due_date;
                    $daysUntilDue = $today->diffInDays($due, false);

                    if ($daysUntilDue >= 0 && $daysUntilDue <= 3) {
                        if ($daysUntilDue <= 1) {
                            if (! $item->reminder_h1_sent_at) {
                                if ($this->notify($item, 'h1')) {
                                    $sent++;
                                }

                                $item->reminder_h1_sent_at = now();
                            }

                            if (! $item->reminder_h3_sent_at) {
                                $item->reminder_h3_sent_at = now();
                            }

                            if ($item->isDirty()) {
                                $item->save();
                            }
                        } elseif (! $item->reminder_h3_sent_at) {
                            if ($this->notify($item, 'h3')) {
                                $sent++;
                            }

                            $item->update(['reminder_h3_sent_at' => now()]);
                        }
                    }

                    if ($daysUntilDue < 0 && (! $item->overdue_notified_at || $item->overdue_notified_at->toDateString() !== $today->toDateString())) {
                        if ($this->notify($item, 'overdue')) {
                            $sent++;
                        }

                        if ($item->division?->head && $item->division->head->id !== $item->pic_id) {
                            $item->division->head->notify(new ActionItemDueSoonNotification($item, 'overdue'));
                        }

                        $item->update(['overdue_notified_at' => now()]);
                    }
                }
            });

        $this->info("Pengingat action item dikirim: {$sent}");

        return self::SUCCESS;
    }

    private function notify(ActionItem $item, string $kind): bool
    {
        if (! $item->pic) {
            return false;
        }

        $item->pic->notify(new ActionItemDueSoonNotification($item, $kind));

        return true;
    }
}
