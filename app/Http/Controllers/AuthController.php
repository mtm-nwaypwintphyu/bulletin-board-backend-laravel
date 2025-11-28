<?php

namespace App\Http\Controllers;

use App\Services\AuthService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{   
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }
    // create user account
    public function createAccount(Request $request): JsonResponse
    {
        // validate request 
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'data' => []
            ], 422);
        }

        // call service
        $result = $this->authService->createAccount($request->all());

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'errors' => $result['errors'] ?? [],
            'data' => $result['user'] ?? []
        ], $result['status']);
    }

    // login
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(),[
            'email' => 'required',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->authService->login($request->all());

        return response()->json(
            $result['success'] ? $result : ['message' => $result['message']],
            $result['status']
        );
    }

    // get profile
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user(); 

        $result = $this->authService->profile($user);

        return response()->json(
            $result['success'] ? $result : ['message' => $result['message']],
            $result['status']
        );
    }

    // logout
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());
        return response()->json(['message' => 'Logged out successfully']);
    }
}
