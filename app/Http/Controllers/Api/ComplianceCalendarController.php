<?php

namespace App\Http\Controllers\Api;

use App\Compliance\Application\ComplianceCalendarService;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComplianceCalendarController extends Controller
{
    public function index(Request $request, ComplianceCalendarService $calendar): JsonResponse
    {
        $data = $request->validate([
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        return response()->json([
            'data' => $calendar->upcoming($request->user()->company_id, $request->user()->branch_id, $data['days'] ?? 90),
        ]);
    }
}
