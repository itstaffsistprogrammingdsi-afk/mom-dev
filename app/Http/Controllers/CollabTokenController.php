<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Services\CollabTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollabTokenController extends Controller
{
    public function __invoke(Request $request, Meeting $meeting, CollabTokenService $tokens): JsonResponse
    {
        $this->authorize('view', $meeting);

        $canEdit = $request->user()->can('update', $meeting);

        return response()->json([
            'url' => config('collab.url'),
            'document' => "meeting-{$meeting->id}",
            'token' => $tokens->issue($meeting, $request->user(), $canEdit),
            'user' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
            ],
        ]);
    }
}
