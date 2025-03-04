<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\roles;
use App\Models\position;
use App\Models\shops;
use App\Models\farms;
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
            $shop = shops::where('users_user_id', $user->user_id)->get();
            $farm = farms::where('users_user_id', $user->user_id)->get();
            Log::info('User logged in successfully:', ['user_id' => $user->user_id]);

            $getRoles = roles::where('users_user_id', $user->user_id)->get();
            $positionId = [];
            $dataArray = json_decode($getRoles, true);
            foreach ($dataArray as $item) {
                $positionId[] = $item['position_position_id'];
            }
            $positionNames = position::whereIn('position_id', $positionId)->get();

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Logged in successfully',
                'data' => [
                    'user' => $user,
                    'shop' => $shop,
                    'farm' => $farm,
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
        Auth::logout();
        return response()->json(['message' => 'Successfully logged out']);
    }

    public function registerShop(Request $request)
    {
        try {
            $shopData = [
                'shop_name' => $request->shop_name,
                'accepts_custom' => $request->accepts_custom,
                'bank_name' => $request->bank_name,
                'bank_account' => $request->bank_account,
                'bank_number' => $request->bank_number,
                'users_user_id' => $request->users_user_id
            ];

            $shop = shops::create($shopData);

            if (!$shop || !$shop->exists) {
                Log::error('Shop creation failed');
                return response()->json([
                    'status' => 'error',
                    'message' => 'Shop registration failed'
                ], 500);
            }

            Log::info('Shop created successfully:', ['shop_id' => $shop->id]);

            $defaultRoles = [
                'position_position_id' => 2,
                'users_user_id' => $shopData['users_user_id']
            ];

            $assignRoles = roles::create($defaultRoles);
            $getRoles = roles::where('users_user_id', $shopData['users_user_id'])->get();
            $positionId = [];
            $dataArray = json_decode($getRoles, true);
            foreach ($dataArray as $item) {
                $positionId[] = $item['position_position_id'];
            }
            $positionNames = position::whereIn('position_id', $positionId)->get();
            return response()->json([
                'status' => 'success',
                'message' => 'Shop registered successfully',
                'data' => [
                    'shop' => $shop,
                    'roles' => $getRoles,
                    'rolesName' => $positionNames,
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

    public function registerFarm(Request $request)
    {
        try {
            $farmData = [
                'farm_name' => $request->farm_name,
                'bank_name' => $request->bank_name,
                'bank_account' => $request->bank_account,
                'bank_number' => $request->bank_number,
                'users_user_id' => $request->users_user_id
            ];

            $farm = farms::create($farmData);

            if (!$farm || !$farm->exists) {
                Log::error('Farm creation failed');
                return response()->json([
                    'status' => 'error',
                    'message' => 'Farm registration failed'
                ], 500);
            }

            Log::info('Farm created successfully:', ['farm_id' => $farm->id]);

            $defaultRoles = [
                'position_position_id' => 3,
                'users_user_id' => $farmData['users_user_id']
            ];

            $assignRoles = roles::create($defaultRoles);
            $getRoles = roles::where('users_user_id', $farmData['users_user_id'])->get();
            $positionId = [];
            $dataArray = json_decode($getRoles, true);
            foreach ($dataArray as $item) {
                $positionId[] = $item['position_position_id'];
            }
            $positionNames = position::whereIn('position_id', $positionId)->get();
            return response()->json([
                'status' => 'success',
                'message' => 'Farm registered successfully',
                'data' => [
                    'farm' => $farm,
                    'roles' => $getRoles,
                    'rolesName' => $positionNames,
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
}
