<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Services\Admin\UserService;
use App\Enums\UserTypeEnum;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService, UserService $userService)
    {
        $this->authService = $authService;
        $this->userService = $userService;
    }
    // get all users
    public function index(Request $request)
    {
        $user = $request->user();

        $search = $request->query('search',null);
        $perPage = (int) $request->query('per_page', 10);
        $page = (int) $request->query('page',1);

        // call service
        $result = $this->userService->getAllUsers($user, $search, $perPage, $page);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'errors' => $result['errors'] ?? [],
            'data' => $result['users'] ?? []
        ], $result['status']);
    }

    // create user by admin
    public function createUser(Request $request): JsonResponse
    {   
        $user = $request->user();
        // check admin permission
        if ($user->type !== UserTypeEnum::Admin) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to create user !',
                'errors' => [],
                'data'=> []
            ], 403);
        }

        // validate request
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

    // edit user
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        // check admin permission
        if ($user->type !== UserTypeEnum::Admin && $user->id != auth()->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update user !',
                'errors' => [],
                'data'=> []
            ], 403);
        }

        // validate request
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email',
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
        $result = $this->userService->updateUser($request->all(), $user->id);
        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'errors' => $result['errors'] ?? [],
            'data' => $result['user'] ?? []
        ], $result['status']);
    }
    // delete
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        $result = $this->userService->destroyUser($request->route('id'));
        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'errors' => $result['errors'] ?? [],
            'data' => $result['user'] ?? []
        ], $result['status']);
    }
}
