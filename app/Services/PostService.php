<?php

namespace App\Services;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Enums\UserTypeEnum;

class PostService
{ 
  // create post
  public function create(array $data, int $userId) {
    try {
      $errors = [];
      $post = Post::create([
        'title' => $data['title'],
        'description' => $data['description'],
        'status'=> $data['status'] ?? 1,
        'create_user_id' => $userId,
        'updated_user_id' => $userId,
      ]);

      return [
          'success' => true,
          'message' => 'Post created successfully',
          'errors' => [],
          'post' => $post,
          'status' => 201
          ];
    } catch (\Exception $e) {
      return [
        'success' => false,
        'message' => 'Server error: '. $e->getMessage(),
        'errors' => [],
        'post' => null,
        'status' => 500
      ];
    }
  }

  // get all posts
  public function getAllPosts(User $user, ?string $search = null, int $perPage = 10, int $page= 1): array
  {
    try {
      $query = Post::query();

      $userType = $user->type;

      if ($search) {
          $query->where('title', 'like', '%' . $search . '%')
                ->orWhere('description', 'like', '%' . $search . '%');
      }

      if ($userType == UserTypeEnum::Admin) {
          $posts = $query->with('creator')
                           ->orderBy('created_at', 'desc')
                           ->paginate($perPage, ['*'], 'page', $page); 
      }else {
          Log::info('User ID:', ['id' => $user->id]);
          $posts = $query->where('create_user_id', $user->id)
                           ->with('creator')
                           ->orderBy('created_at', 'desc')
                           ->paginate($perPage, ['*'], 'page', $page);;
      } 
          return [
              'success' => true,
              'message' => 'Posts fetched successfully',
              'errors' => [],
              'posts' => $posts,
              'status' => 200
          ];
    } catch (\Exception $e) {
       return [
              'success' => false,
              'message' => 'Server error: '. $e->getMessage(),
              'errors' => [],
              'posts' => null,
              'status' => 500
          ];
    }
  }

  // delete posts
  public function destroyPost(int $postId, User $currentUser): array
  {
    try {
      $errors = [];
      $post = Post::find($postId);

      if (!$post) {
        return [
          'success' => false,
          'message' => 'Post not found!',
          'post' => null,
          'status' => 404
        ];
      }
      $userType = $currentUser->type;

      if ($userType == UserTypeEnum::Admin) {
        $postStatus = $post->status;
        if ($post->status == 0 ) {
           return [
            'success' => true,
            'message' => 'Post is already inavtivated!',
            'errors' => [],
            'post' => [],
            'status' => 200
          ];
        } else {
          $post->update([
            'status' => 0,
            'updated_user_id' => $currentUser->id
          ]);

          return [
            'success' => true,
            'message' => 'Post status inactivated successfully.',
            'errors' => [],
            'post' => [],
            'status' => 200
          ];
        }
       }
      elseif ($userType == UserTypeEnum::User) {
        $post->deleted_user_id = $currentUser->id;
        $post->delete();
        $post-> save();

        return [
          'success' => true,
          'message' => 'Post deleted successfully.',
          'errors' => [],
          'post' => [],
          'status' => 200
        ];
      }
    } catch (\Exception $e) {
      return [
        'success' => false,
        'message' => 'Server error: '. $e->getMessage(),
        'errors' => [],
        'post' => null,
        'status' => 500
      ];
    }
  }

  // post detail
  public function detailPost(int $postId, User $currentUser): array
  {
    try {
      $errors = [];
      $post = Post::find($postId);

      if (!$post) {
        return [
          'success' => false,
          'message' => 'Post not found!',
          'post' => null,
          'status' => 404
        ];
      }
      return [
        'success' => true,
        'message' => 'Post get successfully.',
        'errors' => [],
        'post' => $post,
        'status' => 200
      ];
    } catch (\Exception $e) {
      return [
        'success' => false,
        'message' => 'Server error: '. $e->getMessage(),
        'errors' => [],
        'post' => null,
        'status' => 500
      ];
    }
  }

  // post update
  public function updatePost(array $data, User $currentUser, int $postId): array
  { 
   try {
     $post = Post::find($postId);
     if (!$post) {
        return [
            'success' => false,
            'message' => 'Post not found!',
            'errors' => [],
            'user' => null,
            'status' => 404
        ];
      }

      $userType = $currentUser->type;
      if ($userType == UserTypeEnum::Admin) {
          $post->update([
            'status' => $data['status'],
            'updated_at' => now(),
            'updated_user_id' =>  $currentUser->id
          ]);
      } else if ($userType == UserTypeEnum::User) {
          $post->update([
            'title' => $data['title'],
            'description' => $data['description'],
            'status' => $data['status'],
            'updated_user_id' => $currentUser->id,
            'updated_at' => now()
          ]);
      }
      return [
              'success' => true,
              'message' => 'Post updated successfully',
              'errors' => [],
              'post' => $post,
              'status' => 200
              ];
      } catch (\Exception $e) {
        return [
            'success' => false,
            'message' => 'Server error: ' . $e->getMessage(),
            'errors' => [],
            'post' => null,
            'status' => 500
          ];
      }
  }
}
