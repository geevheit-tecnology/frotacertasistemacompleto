<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json(
            Notification::query()
                ->tenant($request->user()->company_id, $request->user()->branch_id)
                ->latest()
                ->paginate(25)
        );
    }
}
