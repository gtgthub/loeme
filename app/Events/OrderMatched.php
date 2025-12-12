<?php

namespace App\Events;

use App\Models\Trade;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderMatched implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $trade;
    public $userId;

    /**
     * Create a new event instance.
     */
    public function __construct(Trade $trade, $userId)
    {
        $this->trade = $trade;
        $this->userId = $userId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->userId),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'trade_id' => $this->trade->id,
            'symbol' => $this->trade->symbol,
            'price' => $this->trade->price,
            'amount' => $this->trade->amount,
            'side' => $this->determineSide(),
            'created_at' => $this->trade->created_at->toISOString(),
        ];
    }

    /**
     * Get the broadcast event name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'OrderMatched';
    }

    /**
     * Determine the side (buy or sell) for the user
     *
     * @return string
     */
    private function determineSide(): string
    {
        // Check if this user is the buyer or seller
        $buyOrder = $this->trade->buyOrder;
        $sellOrder = $this->trade->sellOrder;

        if ($buyOrder && $buyOrder->user_id == $this->userId) {
            return 'buy';
        } elseif ($sellOrder && $sellOrder->user_id == $this->userId) {
            return 'sell';
        }

        return 'unknown';
    }
}
