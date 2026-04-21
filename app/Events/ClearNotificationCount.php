<?php

namespace App\Events;

use App\Services\ClearService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClearNotificationCount implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public $clearService;
    public function __construct(ClearService $clearService = null)
    {
        $this->clearService = $clearService ?? app(ClearService::class);
    }

    public function broadcastOn(): Channel
    {
        return new Channel('clear-notifications');
    }

    public function broadCastAs(): string
    {
        return 'clear-notifications-count';
    }

    public function broadcastWith() {
        return [
            'status_count' => $this->clearService->statusCount()
        ];
    }
}
