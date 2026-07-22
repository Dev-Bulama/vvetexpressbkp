<?php

namespace Webkul\Marketplace\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Webkul\Marketplace\Http\Requests\LocationUpdateRequest;
use Webkul\Marketplace\Logistics\Services\LocationUpdateService;

class AgentLocationController extends Controller
{
    public function __construct(protected LocationUpdateService $locationUpdateService) {}

    /**
     * Only the authenticated agent's own identity is ever used here -
     * agent_id is never taken from the request body, so one agent can never
     * spoof another's position.
     */
    public function update(LocationUpdateRequest $request): JsonResponse
    {
        $agent = auth()->guard('delivery_agent')->user();

        $this->locationUpdateService->record(
            $agent,
            (float) $request->input('latitude'),
            (float) $request->input('longitude'),
            $request->filled('accuracy') ? (float) $request->input('accuracy') : null,
            $request->filled('heading') ? (float) $request->input('heading') : null,
            $request->filled('speed') ? (float) $request->input('speed') : null,
        );

        return response()->json(['message' => 'Location updated.']);
    }
}
