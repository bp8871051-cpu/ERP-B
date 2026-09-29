<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateLayoutPreferenceRequest;
use App\Http\Resources\LayoutPreferenceResource;
use App\Services\LayoutPreferenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LayoutPreferenceController extends Controller
{
    public function __construct(protected LayoutPreferenceService $layoutService)
    {
    }

    /**
     * Get current authenticated user layout preferences.
     */
    public function index(Request $request): JsonResponse
    {
        $preferences = $this->layoutService->getPreferences($request->user());

        return response()->json([
            'success' => true,
            'data' => new LayoutPreferenceResource($preferences),
        ]);
    }

    /**
     * Save or update layout preferences.
     */
    public function store(UpdateLayoutPreferenceRequest $request): JsonResponse
    {
        $preferences = $this->layoutService->updatePreferences(
            $request->user(),
            $request->validated(),
            $request
        );

        return response()->json([
            'success' => true,
            'message' => 'Layout preferences saved successfully.',
            'data' => new LayoutPreferenceResource($preferences),
        ]);
    }

    /**
     * Update layout preferences.
     */
    public function update(UpdateLayoutPreferenceRequest $request): JsonResponse
    {
        return $this->store($request);
    }

    /**
     * Reset user layout preferences to corporate system defaults.
     */
    public function reset(Request $request): JsonResponse
    {
        $preferences = $this->layoutService->resetPreferences($request->user(), $request);

        return response()->json([
            'success' => true,
            'message' => 'Layout preferences reset to default.',
            'data' => new LayoutPreferenceResource($preferences),
        ]);
    }
}
