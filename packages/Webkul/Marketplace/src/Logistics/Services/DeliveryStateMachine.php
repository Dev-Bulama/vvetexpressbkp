<?php

namespace Webkul\Marketplace\Logistics\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;
use Webkul\Marketplace\Events\DeliveryStatusChanged;
use Webkul\Marketplace\Models\Delivery;
use Webkul\Marketplace\Models\DeliveryStatusHistory;

/**
 * The single place allowed to change a delivery's status. Every transition
 * is checked against Delivery::ALLOWED_TRANSITIONS, recorded to
 * delivery_status_histories, and broadcast - callers must never set
 * ->status directly and save().
 */
class DeliveryStateMachine
{
    /**
     * @throws \DomainException if the transition isn't allowed from the delivery's current status.
     */
    public function transition(
        Delivery $delivery,
        string $toStatus,
        string $actorType = 'system',
        ?int $actorId = null,
        ?string $note = null,
    ): Delivery {
        if (! $delivery->canTransitionTo($toStatus)) {
            throw new \DomainException(
                "Delivery #{$delivery->id} cannot move from '{$delivery->status}' to '{$toStatus}'."
            );
        }

        $fromStatus = $delivery->status;

        DB::transaction(function () use ($delivery, $toStatus, $fromStatus, $actorType, $actorId, $note) {
            $delivery->status = $toStatus;
            $this->stampTimestampFor($delivery, $toStatus);
            $delivery->save();

            DeliveryStatusHistory::create([
                'delivery_id' => $delivery->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'actor_type' => $actorType,
                'actor_id' => $actorId,
                'note' => $note,
            ]);
        });

        // Broadcasting is a real-time enhancement, not a precondition for the
        // delivery itself existing - with QUEUE_CONNECTION=sync this call
        // hits Reverb inline, and a broadcaster outage must never roll back
        // (or, since this now runs after the transaction, orphan) an
        // otherwise-successful status transition. This is also why it's
        // dispatched after the transaction commits, not inside it: nothing
        // should be notified of a status change that could still be rolled
        // back.
        try {
            event(new DeliveryStatusChanged($delivery->fresh(), $fromStatus, $toStatus));
        } catch (Throwable $e) {
            Log::warning('Failed to broadcast delivery status change.', [
                'delivery_id' => $delivery->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'error' => $e->getMessage(),
            ]);
        }

        return $delivery;
    }

    private function stampTimestampFor(Delivery $delivery, string $status): void
    {
        $now = now();

        match ($status) {
            Delivery::STATUS_AGENT_ASSIGNED => $delivery->agent_assigned_at = $now,
            Delivery::STATUS_ARRIVED_AT_VENDOR => $delivery->arrived_at_vendor_at = $now,
            Delivery::STATUS_PICKED_UP => $delivery->picked_up_at = $now,
            Delivery::STATUS_ARRIVED_AT_CUSTOMER => $delivery->arrived_at_customer_at = $now,
            Delivery::STATUS_COMPLETED => $delivery->completed_at = $now,
            Delivery::STATUS_CANCELLED => $delivery->cancelled_at = $now,
            default => null,
        };
    }
}
