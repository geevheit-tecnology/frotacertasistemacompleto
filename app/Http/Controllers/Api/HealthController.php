<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function health(): JsonResponse
    {
        return response()->json(['status' => 'ok']);
    }

    public function ready(): JsonResponse
    {
        try {
            DB::select('select 1');
            $database = 'ok';
        } catch (\Throwable $exception) {
            $database = 'unavailable';
        }

        return response()->json([
            'status' => $database === 'ok' ? 'ready' : 'degraded',
            'database' => $database,
        ]);
    }
}
