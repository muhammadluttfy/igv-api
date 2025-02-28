<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // Validasi input
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
            ['password' => bcrypt($request->password)]
        ));

        $token = $user->createToken('userToken')->accessToken;

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully',
            'data' => $user,
            'token' => $token
        ], 201);

    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => [
                'required',
                'string',
                'min:11',
                'max:14',
                function ($attribute, $value, $fail) {
                    if (substr($value, 0, 2) !== '62') {
                        $fail('Phone number must start with 62.');
                        return;
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

        $user = User::where('phone', $request->phone)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Phone number not registered'
            ], 404);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid password'
            ], 401);
        }

        $token = $user->createToken('userToken')->accessToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => $user,
            'token' => $token
        ], 200);
    }


    public function user()
    {
        $user = Auth::user();
        return response()->json([
            'success' => true,
            'message' => 'User data retrieved successfully',
            'data' => $user
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

}
