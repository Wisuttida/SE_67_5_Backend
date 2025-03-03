<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\roles;
use App\Models\position;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function register(RegisterRequest $request)
    {
        try {
            $userData = [
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'phone_number' => $request->phone_number
            ];

            Log::info('Attempting to create user with data:', array_merge(
                $userData,
                ['password' => '[HIDDEN]']
            ));

            $user = User::create($userData);

            if (!$user || !$user->exists) {
                Log::error('User creation failed');
                return response()->json([
                    'status' => 'error',
                    'message' => 'User registration failed'
                ], 500);
            }

            Log::info('User created successfully:', ['user_id' => $user->id]);

            $defaultRoles = [
                'position_position_id' => 4,
                'users_user_id' => $user->user_id
            ];

            $assignRoles = roles::create($defaultRoles);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'User registered successfully',
                'data' => [
                    'user' => $user,
                    'token' => $token
                ]
            ], 201);
        } catch (\Exception $e) {
            Log::error('Registration failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
        Log::info('Incoming registration request:', $request->all());
    }

    public function login(LoginRequest $request)
    {
        try {
            Log::info('Attempting to log in user:', ['email' => $request->email]);

            if (!Auth::attempt($request->only('email', 'password'))) {
                Log::warning('Login attempt failed for user:', ['email' => $request->email]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid credentials'
                ], 401);
            }

            $user = User::where('email', $request->email)->firstOrFail();
            Log::info('User logged in successfully:', ['user_id' => $user->user_id]);

            $getRoles = roles::where('users_user_id', $user->user_id)->get();
            $positionId = [];
            $dataArray = json_decode($getRoles, true);
            foreach ($dataArray as $item) {
                $positionId[] = $item['position_position_id'];
            }
            $positionNames = position::whereIn('position_id', $positionId)->get();

            // Invalidate previous tokens
            $user->tokens()->delete();

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Logged in successfully',
                'data' => [
                    'user' => $user,
                    'roles' => $getRoles,
                    'rolesName' => $positionNames,
                    'token' => $token
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Login failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Login failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();
            Log::info('User logged out successfully');

            return response()->json([
                'status' => 'success',
                'message' => 'Logged out successfully'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Logout failed:', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Logout failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
