<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'token_name' => ['nullable', 'string', 'max:80'],
        ]);

        $user = \App\Models\User::query()
            ->with('roles.permissions')
            ->where('email', $data['email'])
            ->where('status', 'ACTIVE')
            ->first();

        if ($user === null || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 422);
        }

        $plainToken = Str::random(80);

        ApiToken::create([
            'user_id' => $user->id,
            'name' => $data['token_name'] ?? 'api',
            'token_hash' => hash('sha256', $plainToken),
            'expires_at' => now()->addDays(30),
        ]);

        return response()->json([
            'token_type' => 'Bearer',
            'access_token' => $plainToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'group_id' => $user->group_id,
                'company_id' => $user->company_id,
                'branch_id' => $user->branch_id,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user()->load('roles.permissions'));
    }
}
