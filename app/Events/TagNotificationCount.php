<?php

namespace App\Events;

use App\Models\Transaction;
use App\Services\TagService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TagNotificationCount implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $tagService;
    public function __construct(TagService $tagService = null)
    {
        $this->tagService = $tagService ?? app(TagService::class);
    }

    public function broadcastOn()
    {
        return new Channel('tag-notifications');
    }

    public function broadcastAs() {
        return 'tag-notifications-count';
    }

    public function broadcastWith() {
        return [
            'status_count' => $this->tagService->statusCount()
        ];
    }
}
