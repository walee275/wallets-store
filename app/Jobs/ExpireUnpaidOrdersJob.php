<?php

namespace App\Jobs;

use App\Actions\Checkout\ExpireUnpaidOrders;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExpireUnpaidOrdersJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $minutes = 60,
    ) {}

    public function handle(ExpireUnpaidOrders $expireUnpaidOrders): void
    {
        $expireUnpaidOrders->handle($this->minutes);
    }
}
