<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;


class AuthService
{
  // create user account
  public function createAccount(array $data): array
  { 
    try {
        $errors = [];

        // check name
        if (User::where('name', $data['name'])->exists()) {
            $errors['name'][] = 'Name already exists!';
        }

        // check email
        if (User::where('email', $data['email'])->exists()) {
            $errors['email'][] = 'Email already exists!';
        }

        // return validation errors
        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $errors,
                'user' => null,
                'status' => 422
            ];
        }

        $uploadPath = public_path('uploads');

        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        if (!empty($data['profile'])) {
            // remove prefix
            $image = preg_replace('/^data:image\/\w+;base64,/', '', $data['profile']);
            // replace space with plus sign
            $image = str_replace(' ', '+', $image);
            // generate unique file name
            $imageName = time() . '_' . uniqid() . '.jpg';
            // save decoded image to server
            file_put_contents($uploadPath . '/' . $imageName, base64_decode($image));
            $profilePath = 'uploads/' . $imageName;
        } else {
            $profilePath = null;
        }
          // create user
          $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'] ?? null,
                'type'=> $data['type'] ?? 1,
                'dob' => $data['dob'] ?? null,
                'address' => $data['address'] ?? null,
                'profile' => $profilePath ?? null,
                'create_user_id' => $data['current_user_id'] ?? 1,
                'updated_user_id' => $data['current_user_id'] ?? 1,
          ]);

          return [
              'success' => true,
              'message' => 'Account created successfully',
              'errors' => [],
              'user' => $user,
              'status' => 201
          ];
      } catch (\Exception $e) {
          return [
              'success' => false,
              'message' => 'Server error: ' . $e->getMessage(),
              'errors' => [],
              'user' => null,
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
