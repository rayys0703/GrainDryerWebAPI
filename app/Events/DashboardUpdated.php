<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DashboardUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $dryerId;
    public $data;

    public function __construct($dryerId, $data)
    {
        $this->dryerId = $dryerId;
        $this->data = $data; // Data seperti response /api/dashboard-data
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('dashboard.' . $this->dryerId)];
    }

    public function broadcastWith(): array
    {
        return ['data' => $this->data];
    }
}