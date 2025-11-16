<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/v1/auth/register",
     *     summary="Register a new user",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password","password_confirmation","role"},
     *             @OA\Property(property="name", type="string", example="John Doe"),
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="password", type="string", example="password123"),
     *             @OA\Property(property="password_confirmation", type="string", example="password123"),
     *             @OA\Property(property="role", type="string", enum={"admin", "vendor", "customer"}, example="customer")
     *         )
     *     ),
     *     @OA\Response(response=201, description="User registered successfully")
     * )
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:admin,vendor,customer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        $token = Auth::login($user);

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'authorization' => [
                'token' => $token,
                'type' => 'bearer',
            ],
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     summary="Login user",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", example="john@example.com"),
     *             @OA\Property(property="password", type="string", example="password123")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Login successful")
     * )
     */
    public function login(Request $request)
    {
        
        $credentials = $request->only('email', 'password');

        try {
            if (! $token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'invalid_credentials'], 400);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token'], 500);
        }

        // Get the authenticated user
        $user = Auth::user();
        
        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'authorization' => [
                'token' => $token, // Now this will be the actual JWT string
                'type' => 'bearer',
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/logout",
     *     summary="Logout user",
     *     tags={"Authentication"},
     *     security={{"bearer":{}}},
     *     @OA\Response(response=200, description="Logout successful")
     * )
     */
    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json(['message' => 'Logout successful']);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/refresh",
     *     summary="Refresh JWT token",
     *     tags={"Authentication"},
     *     security={{"bearer":{}}},
     *     @OA\Response(response=200, description="Token refreshed")
     * )
     */
    public function refresh()
    {
        $token = JWTAuth::refresh(JWTAuth::getToken());
        return response()->json([
            'authorization' => [
                'token' => $token,
                'type' => 'bearer',
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/auth/me",
     *     summary="Get authenticated user",
     *     tags={"Authentication"},
     *     security={{"bearer":{}}},
     *     @OA\Response(response=200, description="User details")
     * )
     */
    public function me()
    {
        return response()->json(['user' => Auth::user()]);
    }
}
