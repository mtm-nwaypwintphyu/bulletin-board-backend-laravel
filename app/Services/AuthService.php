<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthService
{ 
  // create User by user
  public function createUser(array $data): array
  {
    try {
        // check email
        if (User::where('email', $data['email'])->exists()) {
          return [
            'success' => false,
            'message' => 'Email already exists',
            'status' => 409
        ];
        }

        // check name
        if (User::where('name',$data['name'])->exists()) {
          return [
            'success' => false,
            'message' => 'Name already exists',
            'status' => 409
          ];
        }

        // create user
        $user = User::create([
          'name' => $data['name'],
          'email' => $data['email'],
          'password' => Hash::make($data['password']),
          'type' => $data['type'] ?? '1',
          'profile' => $data['profile'] ?? null,
          'phone' => $data['phone'] ?? null,
          'address' => $data['address'] ?? null,
          'dob' => $data['dob'] ?? null,
          'create_user_id' => $data['create_user_id'] ?? 1,
          'updated_user_id' => $data['updated_user_id'] ?? 1,
          'deleted_user_id' => $data['deleted_user_id'] ?? null,
        ]);
        return [
              'success' => true,
              'message' => 'User registered successfully',
              'user' => $user,
              'status' => 201
          ];


    } catch (\Exception $e) {
    return [
              'success' => false,
              'message' => 'Server error: ' . $e->getMessage(),
              'status' => 500
          ];
    }
  }

  // Create user account by userself
  public function createAccount(array $data): array
  {
      try {
          // check name
          if (User::where('name', $data['name'])->exists()) {
              return [
                  'success' => false,
                  'message' => 'Name already exists',
                  'status' => 409
              ];
          }

            // check email
          if (User::where('email', $data['email'])->exists()) {
              return [
                  'success' => false,
                  'message' => 'Email already exists',
                  'status' => 409
              ];
          }

          // Create the user
          $user = User::create([
              'name' => $data['name'],
              'email' => $data['email'],
              'password' => Hash::make($data['password']),
              'create_user_id' => 1,
              'updated_user_id' => 1,
          ]);

          return [
              'success' => true,
              'message' => 'Account created successfully',
              'user' => $user,
              'status' => 201
          ];
      } catch (\Exception $e) {
          return [
              'success' => false,
              'message' => 'Server error: ' . $e->getMessage(),
              'status' => 500
          ];
      }
  }

  // login
  public function login(array $data)
  {
    try {
        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return [
                'success' => false,
                'message' => 'Invalid email or password',
                'status' => 401
            ];
        }

        $expiresAt = $data['rememberMe']
            ? now()->addDays(7)
            : now()->addHours(2);

        $token = $user->createToken(
            'access_token',
            ['*'],
            $expiresAt
        )->plainTextToken;

        return [
            'success' => true,
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
            'expires_at' => $expiresAt->toDateTimeString(),
            'status' => 200
        ];

    } catch (\Exception $e) {
        return [
            'success' => false,
            'message' => 'Server error: ' . $e->getMessage(),
            'status' => 500
        ];
    }
  }

  // get profile
  public function profile(User $user): array
  {
    return [
        'success' => true,
        'user' => $user,
        'message' => 'User profile retrieved successfully',
        'status' => 200
    ];
  }

  // logout
  public function logout(User $user): void
  {
    $user->tokens()->delete(); 
  }
}
