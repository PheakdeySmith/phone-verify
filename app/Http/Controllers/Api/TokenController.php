<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TokenController extends Controller
{
    public function create(Request $request)
    {
        $request->validate([
            'token_name' => 'required|string|max:255'
        ]);

        $user = $request->user();

        $token = $user->createToken($request->token_name)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'API Token created',
            'token' => $token,
            'token_name' => $request->token_name,
            'note' => 'Save the token please!'
        ]);
    }

    public function index(Request $request)
    {
        $tokens = $request->user()->tokens;

        return response()->json([
            'success' => true,
            'tokens' => $tokens->map(function($token) {
                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'created_at' => $token->created_at,
                    'last_used_at' => $token->last_used_at
                ];
            })
        ]);
    }
}
