<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *   path="/api/auth/login",
     *   summary="Login and get tokens",
     *   tags={"Auth"},
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       required={"email","password"},
     *       @OA\Property(property="email", type="string", format="email"),
     *       @OA\Property(property="password", type="string", format="password")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Login successful",
     *     @OA\JsonContent(
     *       @OA\Property(property="user", type="object"),
     *       @OA\Property(property="accessToken", type="string"),
     *       @OA\Property(property="refreshToken", type="string"),
     *       @OA\Property(property="tokenType", type="string", example="Bearer")
     *     )
     *   ),
     *   @OA\Response(response=422, description="Validation error")
     * )
     * Login user and return user data with role information and tokens
     */
    public function login(Request $request)
    {
        // Log the raw content first
        $rawContent = $request->getContent();
        \Log::info('Raw request content', ['content' => $rawContent]);

        // Parse JSON data manually since Laravel 11+ doesn't auto-parse
        // Handle Windows cmd malformed JSON (single quotes and missing quotes around property names)
        $cleanContent = trim($rawContent, "'");

        // Fix Windows cmd JSON format by adding quotes around property names and values
        $cleanContent = preg_replace('/(\w+):/', '"$1":', $cleanContent);
        $cleanContent = preg_replace('/:\s*([^",\s}]+)([,}])/', ':"$1"$2', $cleanContent);

        $data = json_decode($cleanContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            \Log::error('JSON decode error', [
                'error' => json_last_error_msg(),
                'content' => $rawContent,
                'clean_content' => $cleanContent
            ]);
            return response()->json([
                'message' => 'Invalid JSON data',
                'error' => json_last_error_msg(),
                'received_content' => $rawContent,
                'clean_content' => $cleanContent
            ], 400);
        }

        // Merge the parsed data into the request
        $request->merge($data);

        // Log the incoming request for debugging
        \Log::info('Login attempt', [
            'email' => $request->email,
            'password' => $request->password,
            'all_data' => $request->all(),
            'headers' => $request->headers->all(),
            'content' => $rawContent
        ]);

        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->with(['role', 'fillingStations'])->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Create access and refresh tokens (Sanctum personal access tokens)
        $accessToken = $user->createToken('access-token', ['access'])->plainTextToken;
        $refreshToken = $user->createToken('refresh-token', ['refresh'])->plainTextToken;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ? [
                    'id' => $user->role->id,
                    'name' => $user->role->name,
                    'display_name' => $user->role->display_name,
                ] : null,
                'filling_stations' => $user->fillingStations,
            ],
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
            'tokenType' => 'Bearer',
            'message' => 'Login successful',
        ]);
    }

    /**
     * Get current authenticated user with role information
     */
    public function me(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user->load(['role', 'fillingStations']);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ? [
                    'id' => $user->role->id,
                    'name' => $user->role->name,
                    'display_name' => $user->role->display_name,
                ] : null,
                'filling_stations' => $user->fillingStations,
            ],
        ]);
    }

    /**
     * Logout user (if using tokens)
     */
    public function logout(Request $request)
    {
        // If using Sanctum, revoke tokens
        // $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }
}