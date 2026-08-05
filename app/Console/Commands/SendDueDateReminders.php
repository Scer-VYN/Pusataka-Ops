<?php

namespace App\Console\Commands;

use App\Models\Borrowing;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendDueDateReminders extends Command
{
    protected $signature = 'library:send-due-reminders';

    protected $description = 'Create one reminder per day for borrowings nearing their due date';

    public function handle(): int
    {
        $today = Carbon::today();
        $created = 0;

        Borrowing::query()
            ->with('book')
            ->whereIn('status', ['borrowed', 'extended'])
            ->whereNull('return_date')
            ->whereDate('due_date', '<=', $today->copy()->addDays(3))
            ->each(function (Borrowing $borrowing) use ($today, &$created): void {
                $alreadySent = Notification::query()
                    ->where('user_id', $borrowing->user_id)
                    ->where('borrowing_id', $borrowing->id)
                    ->whereDate('created_at', $today)
                    ->exists();

                if ($alreadySent) {
                    return;
                }

                $days = $today->diffInDays($borrowing->due_date, false);
                $timing = $days < 0
                    ? 'sudah melewati tenggat'
                    : ($days === 0 ? 'jatuh tempo hari ini' : "jatuh tempo dalam {$days} hari");

                Notification::query()->create([
                    'user_id' => $borrowing->user_id,
                    'borrowing_id' => $borrowing->id,
                    'message' => "{$borrowing->book->title} {$timing}.",
                    'is_read' => false,
                ]);
                $created++;
            });

        $this->info("Created {$created} due-date reminder(s).");

        return self::SUCCESS;
    }
}
