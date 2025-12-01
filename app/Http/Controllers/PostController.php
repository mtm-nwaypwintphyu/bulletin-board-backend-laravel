<?php

namespace App\Http\Controllers;
use App\Http\Controllers\Controller;

use App\Services\PostService;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class PostController extends Controller
{
  protected $postService;
  protected $currentUser;

  public function __construct(PostService $postService)
  {
    $this->postService = $postService;
    $this->currentUser = Auth::user();
  }

  // create post
  public function create(Request $request): JsonResponse
  {
    // validate request
    $validator = Validator::make($request->all(),[
        'title' => 'required|string|max:255|unique:posts,title',
        'description' => 'required|string|max:1000',
        'status' => 'nullable|integer|in:0,1'
      ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation failed.',
        'errors' => $validator->errors(),
        'data' => []
      ], 422);
    }

    // call service
    $result = $this->postService->create($request->all(),$this->currentUser->id);
    return response()->json([
      'success' => $result['success'],
      'message' => $result['message'],
      'errors' => $result['errors']  ?? [],
      'data' => $result['post']
    ], $result['status']);
  }

  // get all posts
  public function index(Request $request): JsonResponse
  {
    $search = $request->query('search', null);
    $perPage = (int) $request->query('per_page',10);
    $page = (int) $request->query('page',1);

    // call service
    $result = $this->postService->getAllPosts($this->currentUser, $search, $perPage, $page);

    return response()->json([
      'success' => $result['success'],
      'message' => $result['message'],
      'errors' => $result['errors'] ?? [],
      'data' => $result['posts'] ?? []
    ], $result['status']);
  }

  // delete
  public function destroy(Request $request): JsonResponse
  {
    $result = $this->postService->destroyPost($request->route('id'), $this->currentUser);

    return response()->json([
      'success' => $result['success'],
      'message' => $result['message'],
      'errors' =>$result['errors'] ?? [],
      'data' => $result['post'] ?? []
    ], $result['status']);
  }

  // post detail
  public function detail(Request $request): JsonResponse
  {
    $result = $this->postService->detailPost($request->route('id'), $this->currentUser);

    return response()->json([
      'success' => $result['success'],
      'message' => $result['message'],
      'errors' =>$result['errors'] ?? [],
      'data' => $result['post'] ?? []
    ], $result['status']);
  }

  // update post
  public function update(Request $request): JsonResponse
  { 
    // validate request
    $validator = Validator::make($request->all(), [
        'title' => 'required|string|max:255|unique:posts,title',
        'description' => 'required|string|max:1000',
        'status' => 'nullable|integer|in:0,1'
    ]);

    $result = $this->postService->updatePost($request->all(), $this->currentUser, $request->route('id'));

    return response()->json([
      'success' => $result['success'],
      'message' => $result['message'],
      'errors' =>$result['errors'] ?? [],
      'data' => $result['post'] ?? []
    ], $result['status']);
  }
}