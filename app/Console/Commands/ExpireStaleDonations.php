<?php

namespace App\Console\Commands;

use App\Models\Donation;
use Illuminate\Console\Command;

class ExpireStaleDonations extends Command
{
    protected $signature   = 'donations:expire-stale';
    protected $description = 'Mark pending donations older than 24 hours as expired.';

    public function handle(): int
    {
        $count = Donation::where('status', 'pending')
            ->where('created_at', '<', now()->subHours(24))
            ->update(['status' => 'expired']);

        $this->info("Expired {$count} stale donation(s).");

        return Command::SUCCESS;
    }
}
