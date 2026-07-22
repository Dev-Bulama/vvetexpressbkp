<?php

namespace Webkul\Marketplace\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Webkul\Marketplace\Models\Delivery;
use Webkul\Marketplace\Models\DeliveryAgentLocation;

class AgentLocationUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly Delivery $delivery,
        public readonly DeliveryAgentLocation $location,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("delivery.{$this->delivery->id}"),
            new PrivateChannel('admin.deliveries'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'delivery.location-updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'delivery_id' => $this->delivery->id,
            'latitude' => (float) $this->location->latitude,
            'longitude' => (float) $this->location->longitude,
            'heading_degrees' => $this->location->heading_degrees !== null ? (float) $this->location->heading_degrees : null,
            'speed_kph' => $this->location->speed_kph !== null ? (float) $this->location->speed_kph : null,
            'accuracy_meters' => $this->location->accuracy_meters !== null ? (float) $this->location->accuracy_meters : null,
            'recorded_at' => $this->location->recorded_at->toIso8601String(),
        ];
    }
}
