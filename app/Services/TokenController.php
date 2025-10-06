<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use App\Models\User;

class TokenController extends Controller
{
    /**
     * Get current user from JWT session
     */
    private function getCurrentUser(Request $request)
    {
        // For Sanctum authenticated requests, use the normal way
        if ($request->user()) {
            return $request->user();
        }

        // For JWT web users, get user from session
        if (Session::has('jwt_token') && Session::get('user_authenticated')) {
            // Extract username from JWT session or use a stored identifier
            $jwtToken = Session::get('jwt_token');

            // For now, we'll use a simple approach - you might want to decode JWT properly
            // Get the user that was created during login based on session
            $sessionUser = Session::get('current_user_email');

            if (!$sessionUser) {
                // Fallback: create a temp identifier from the JWT
                $sessionUser = 'jwt_user_' . substr(md5($jwtToken), 0, 10) . '@system.local';
            }

            return User::firstOrCreate([
                'email' => $sessionUser
            ], [
                'name' => 'JWT User',
                'password' => bcrypt('system-generated')
            ]);
        }

        return null;
    }

    /**
     * Create new API token
     */
    public function create(Request $request)
    {
        $request->validate([
            'token_name' => 'required|string|max:255'
        ]);

        $user = $this->getCurrentUser($request);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        $token = $user->createToken($request->token_name)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'API Token created successfully',
            'token' => $token,
            'token_name' => $request->token_name,
            'note' => 'Save this token securely. You won\'t be able to see it again!'
        ]);
    }

    /**
     * Get all user tokens
     */
    public function index(Request $request)
    {
        $user = $this->getCurrentUser($request);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        $tokens = $user->tokens;

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

    /**
     * Delete specific token
     */
    public function destroy(Request $request, $tokenId)
    {
        $user = $this->getCurrentUser($request);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        $deleted = $user->tokens()->where('id', $tokenId)->delete();

        if($deleted) {
            return response()->json([
                'success' => true,
                'message' => 'Token revoked successfully'
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Token not found'
        ], 404);
    }

    /**
     * Delete all user tokens
     */
    public function destroyAll(Request $request)
    {
        $user = $this->getCurrentUser($request);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required'
            ], 401);
        }

        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'All tokens revoked successfully'
        ]);
    }
}