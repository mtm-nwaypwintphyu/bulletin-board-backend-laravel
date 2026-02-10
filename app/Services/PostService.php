<?php

namespace App\Services;
use App\Models\Post;
use App\Models\User;
use App\Models\PostImportHistory;
use App\Models\PostHistory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Enums\UserTypeEnum;
use Illuminate\Support\Facades\Validator;

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

      PostHistory::create([
        'post_id' => $post->id,
        'user_id'=>$userId,
        'change_description' => 'Post created.'
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
          PostHistory::create([
            'post_id' => $post->id,
            'user_id'=>$currentUser->id,
            'change_description' => 'Post inactivated by admin.'
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
        PostHistory::create([
          'post_id' => $post->id,
          'user_id' => $currentUser->id,
          'change_description' => 'Post deleted by user.'
          ]);
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
      if ($userType == UserTypeEnum::Admin && $post->create_user_id !== $currentUser->id) {
          $post->update([
            'status' => $data['status'],
            'updated_at' => now(),
            'updated_user_id' =>  $currentUser->id
          ]);
      } else if ($userType == UserTypeEnum::User || $post->create_user_id == $currentUser->id) {
          $post->update([
            'title' => $data['title'],
            'description' => $data['description'],
            'status' => $data['status'],
            'updated_user_id' => $currentUser->id,
            'updated_at' => now()
          ]);
      }
      PostHistory::create([
        'post_id' => $post->id,
        'user_id'=>$currentUser->id,
        'change_description' => 'Post updated.'
      ]);
      return 
          [
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

  // post csv import
  public function importPostsFromCsv($file, int $userId): array
  {
    try {
        $path = $file->getRealPath();
        $fileHandle = fopen($path, 'r');
        
        $header = fgetcsv($fileHandle);
        
        $requiredHeaders = ['title', 'description'];

        if (array_diff($requiredHeaders, $header)) {
            fclose($fileHandle);

            PostImportHistory::create([
                'import_file' => $file->getClientOriginalName(),
                'import_timestamp' => now(),
                'records_imported' => 0,
                'status' => 'failed',
                'error_message' => 'Invalid CSV headers.',
                'user_id' => $userId,
            ]);
            PostHistory::create([
              'post_id' => $post->id,
              'user_id'=>$userId,
              'change_description' => 'Post CSV imported.'
            ]);
            return [
                'success' => false,
                'message' => 'Invalid CSV headers. Please use the template.',
                'errors' => [],
                'data' => [],
                'status' => 422
            ];
        }

        $posts = [];
        $errors = [];
        $rowNumber = 1;
        $importedCount = 0;

        while (($row = fgetcsv($fileHandle)) !== false) {
            $rowNumber++;

            $data = array_combine($header, $row);

            $validator = Validator::make($data, [
                'title' => 'required|string|max:255|unique:posts,title',
                'description' => 'required|string|max:1000'
            ]);

            if ($validator->fails()) {
                $rowErrors = implode(', ', $validator->errors()->all());
                $errors[] = "Row $rowNumber: $rowErrors";
                continue;
            }

            if (Post::where('title', $data['title'])->exists()) {
                $errors[] = "Row $rowNumber: Duplicate title.";
                continue;
            }

            $post = Post::create([
                'title' => $data['title'],
                'description' => $data['description'],
                'status' => 1,
                'create_user_id' => $userId,
                'updated_user_id' => $userId,
            ]);

            $importedCount++;
            $posts[] = $post;
        }

        fclose($fileHandle);
        
        PostImportHistory::create([
          'import_file' => $file->getClientOriginalName(),
          'import_timestamp' => now(),
          'records_imported' => $importedCount,
          'status' => $importedCount > 0 ? 'success' : 'failed',
          'error_message' => count($errors) > 0 ? implode(', ', $errors) : null,
          'user_id' => $userId,
        ]);

        if (count($posts) > 0) {
            $message = count($posts) . ' post(s) imported successfully.';
        } else {
            $message = 'Duplicate post title(s), no post was imported.';
        }

        return [
            'success' => true,
            'message' => $message,
            'errors' => $errors,
            'data' => $posts,
            'status' => 200
        ];

    } catch (\Exception $e) {

        PostImportHistory::create([
          'import_file' => $file->getClientOriginalName(),
          'import_timestamp' => now(),
          'records_imported' => 0,
          'status' => 'failed',
          'error_message' => 'Error importing posts: ' . $e->getMessage(),
          'user_id' => $userId,
        ]);

        return [
            'success' => false,
            'message' => 'Error importing posts: ' . $e->getMessage(),
            'errors' => [],
            'data' => [],
            'status' => 500
        ];
    }
  }

  // get import history
  public function getCsvImportHistory(int $userId, int $perPage = 10, int $page= 1): array
  {
    try {
      $errors = [];
      $history = PostImportHistory::where('user_id', $userId)
                                    ->orderBy('import_timestamp', 'desc')
                                    ->paginate($perPage, ['*'], 'page', $page);
      return [
        'success' => true,
        'message' => 'Post import history get successfully.',
        'errors' => [],
        'history' => $history,
        'status' => 200
      ];
    } catch (\Exception $e) {
      return [
        'success' => false,
        'message' => 'Server error: '. $e->getMessage(),
        'errors' => [],
        'history' => null,
        'status' => 500
      ];
    }
  }

  // delete import history
  public function destroyImportHistory(int $historyId, User $currentUser): array
  {
    try {
      $errors = [];
      $history = PostImportHistory::find($historyId);

      if (!$history) {
        return [
          'success' => false,
          'message' => 'History not found!',
          'post' => null,
          'status' => 404
        ];
      }
        $history->forceDelete();

      return [
        'success' => true,
        'message' => 'History deleted successfully.',
        'errors' => [],
        'post' => [],
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

  // get post history
  public function getPostHistory(int $userId, int $perPage = 10, int $page= 1): array
  {
    try {
      $errors = [];
      $history = PostHistory::where('user_id', $userId)
                                    ->orderBy('created_at', 'desc')
                                    ->paginate($perPage, ['*'], 'page', $page);
      return [
        'success' => true,
        'message' => 'Post history get successfully.',
        'errors' => [],
        'history' => $history,
        'status' => 200
      ];
    } catch (\Exception $e) {
      return [
        'success' => false,
        'message' => 'Server error: '. $e->getMessage(),
        'errors' => [],
        'history' => null,
        'status' => 500
      ];
    }
  }
}
