<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'phone' => [
                    'required',
                    'string',
                    'min:11',
                    'max:14',
                    'unique:users',
                    function ($attribute, $value, $fail) {
                        if (substr($value, 0, 2) !== '62') {
                            $fail('Phone number must start with 62.');
                            return;
                        }

                        $allowedPrefixes = [
                            '62817', '62818', '62819', '62859', '62877', '62878', '62879', // XL
                            '62831', '62832', '62833', '62838', // Axis
                            '62855', '62856', '62857', '62858', '62814', '62815', '62816' // IM3
                        ];

                        $isValidPrefix = false;
                        foreach ($allowedPrefixes as $prefix) {
                            if (strpos($value, $prefix) === 0) {
                                $isValidPrefix = true;
                                break;
                            }
                        }

                        if (!$isValidPrefix) {
                            $fail('Phone number must use XL/Axis or IM3 carrier.');
                        }
                    }
                ],
                'password' => 'required|string|min:8',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = User::create(array_merge(
                $validator->validated(),
                ['password' => Hash::make($request->password)]
            ));

            Log::channel('telegram_info')->info('User registered successfully', ['user_id' => $user->id, 'email' => $user->email]);

            $token = $user->createToken('userToken')->accessToken;

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => $user,
                'token' => $token
            ], 201);
        } catch (\Exception $exception) {
            Log::channel('telegram_error')->error('Registration failed', [
                'error_message' => $exception->getMessage(),
            ]);
            Log::error('Registration failed', [
                'error_message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error'
            ], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'identifier' => 'required|string',
                'password'   => 'required|string|min:8',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid input.',
                    'errors'  => $validator->errors()
                ], 422);
            }

            $identifier = $request->identifier;
            if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                $user = User::where('email', $identifier)->first();
            } elseif (preg_match('/^62[0-9]{8,13}$/', $identifier)) {
                $user = User::where('phone', $identifier)->first();
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email/phone format'
                ], 422);
            }

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Incorrect email/phone or password.'
                ], 401);
            }

            $user->update(['last_logged_in_at' => now()]);
            Log::channel('telegram_info')->info('User logged in successfully', ['user_id' => $user->id, 'email' => $user->email]);

            $token = $user->createToken('userToken')->accessToken;

            return response()->json([
                'success' => true,
                'message' => 'Login successful.',
                'data'    => [
                    'user'  => $user,
                    'token' => $token
                ]
            ], 200);
        } catch (\Exception $exception) {
            Log::channel('telegram_error')->error('Login failed', [
                'error_message' => $exception->getMessage(),
            ]);
            Log::error('Login failed', [
                'error_message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error'
            ], 500);
        }
    }

    public function user()
    {
        try {
            $user = Auth::user();
            return response()->json([
                'success' => true,
                'message' => 'User data retrieved successfully',
                'data' => $user
            ], 200);
        } catch (\Exception $exception) {
            Log::channel('telegram_error')->error('Failed to retrieve user data', [
                'error_message' => $exception->getMessage()
            ]);
            Log::error('Failed to retrieve user data', [
                'error_message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error'
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $request->user()->token()->revoke();
            Log::channel('telegram_info')->info('User logged out successfully', ['user_id' => $user->id, 'email' => $user->email]);

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully'
            ]);
        } catch (\Exception $exception) {
            Log::channel('telegram_error')->error('Logout failed', [
                'error_message' => $exception->getMessage()
            ]);
            Log::error('Logout failed', [
                'error_message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal Server Error'
            ], 500);
        }
    }
}
