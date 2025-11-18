<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Enums\UserTypeEnum;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    // create user by admin
    public function createUser(Request $request): JsonResponse
    {   
        $user = $request->user();

        if ($user->type !== UserTypeEnum::Admin) {
            return response()->json([
                'message' => 'You do not have permission to access this resource.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'profile' => 'nullable|string|max:255',
            'type' => '|in:0,1',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'dob' => 'nullable|date',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ],422);
        }

        $result = $this->authService->createUser($request->all(), $user->id);

        return response()->json(
            $result['success'] ? $result : ['message' => $result['message']],
            $result['status']
        );
    }
}
