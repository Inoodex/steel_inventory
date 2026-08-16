<?php

namespace App\Events;

use App\Models\Coil;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LowStockAlertEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Coil $coil,
        public int $currentStock,
        public int $threshold = 5
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('inventory-channel'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'stock.low';
    }

    public function broadcastWith(): array
    {
        return [
            'coil_id'       => $this->coil->id,
            'coil_number'   => $this->coil->coil_number,
            'current_stock' => $this->currentStock,
            'threshold'     => $this->threshold,
            'message'       => "Low stock alert: Coil #{$this->coil->coil_number} has only {$this->currentStock} remaining!",
        ];
    }
}
