<?php

namespace App\Console\Commands;

use App\Models\CartItem;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanExpiredCartItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cart:clean-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove cart items that have expired (older than 48 hours)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();
        $count = CartItem::where('expires_at', '<', $now)->delete();
        
        $this->info("Removed {$count} expired cart items.");
        
        return Command::SUCCESS;
    }
}
