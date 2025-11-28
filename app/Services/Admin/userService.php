<?php

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Enums\UserTypeEnum;

class UserService
{
    // get all users
    public function getAllUsers(User $user,?string $search = null, int $perPage = 10, int $page = 1): array
    {
        try {
            $query = User::query();

            $userType = $user->type;

            if ($search)  {
                $fixedSearchString = str_replace(':', '=', $search);
                parse_str($fixedSearchString, $searchParams);

                $query->where(function ($q) use ($searchParams) {
                    if (isset($searchParams['name'])) {
                        $q->where('name', 'like', "%" . $searchParams['name'] . "%");
                    }

                    if (isset($searchParams['email'])) {
                        $q->orWhere('email', 'like', "%" . $searchParams['email'] . "%");
                    }
                });
                if (isset($searchParams['from'])) {
                    $query->where('created_at', '>=', $searchParams['from']);
                }

                if (isset($searchParams['to'])) {
                    $query->where('created_at', '<=', $searchParams['to']);
                }
            }
            if($userType == UserTypeEnum::Admin) {
                $users = $query->with('creator')->orderBy('created_at','desc')->paginate($perPage, ['*'], 'page', $page);
            }else {
                $users = $query->where('create_user_id', $user->id)->with('creator')->orderBy('created_at','desc')->paginate($perPage, ['*'], 'page', $page);
            }

            $users->getCollection()->transform(function($user) {
                $user->creator_name = $user->creator ? $user->creator->name : 'N/A';
                return $user;
            });

             return [
                'success' => true,
                'message' => 'Users fetched successfully',
                'errors' => [],
                'users' => $users,
                'status' => 200
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Server error: '. $e->getMessage(),
                'errors' => [],
                'users' => null,
                'status' => 500
            ];
        }
    }

    // update user
    public function updateUser(array $data): array
    {
        try {
            $errors = [];

            $user = User::find($data['id']);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found!',
                    'errors' => [],
                    'user' => null,
                    'status' => 404
                ];
            }

            if (User::where('name', $data['name'])->where('id', '!=', $data['id'])->exists()) {
                $errors['name'][] = 'Name already exists!';
            }

            if (User::where('email', $data['email'])->where('id', '!=', $data['id'])->exists()) {
                $errors['email'][] = 'Email already exists!';
            }

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

                if ($user->profile && file_exists(public_path($user->profile))) {
                    File::delete(public_path($user->profile));
                }
            } else {
                $profilePath = null;
            }

            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? $user->phone,
                'dob' => $data['dob'] ?? $user->dob,
                'type' => $data['type'] ?? $user->type ?? 1,
                'address' => $data['address'] ?? $user->address,
                'profile' => $profilePath,
                'updated_date' => $user,
            ]);

            return [
                'success' => true,
                'message' => 'User updated successfully',
                'errors' => [],
                'user' => $user,
                'status' => 200
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

    // delete user
    public function destroyUser(int $userId): array
    {
        try {
            $errors = [];
            $user = User::find($userId);
            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found!',
                    'errors' => [],
                    'user' => null,
                    'status' => 404
                ];
            }
            $user->delete();

            return [
                'success' => true,
                'message' => 'User deleted successfully.',
                'errors' => [],
                'status' => 200
            ];
        } catch(\Exception $e) {
            return [
                'success' => false,
                'message' => 'Server error: '. $e->getMessage(),
                'errors' => [],
                'user' => null,
                'status' => 500
            ];
        }
    }
}
