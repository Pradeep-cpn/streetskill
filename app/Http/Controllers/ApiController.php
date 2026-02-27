<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        if (!auth()->attempt($request->only('email', 'password'))) {
            return response()->json(['error' => 'Invalid'], 401);
        }

        $token = $request->user()->createToken('api-token')->plainTextToken;

        return response()->json(['token' => $token]);
    }

    public function marketplace(): JsonResponse
    {
        return response()->json(User::query()->get());
    }
}
