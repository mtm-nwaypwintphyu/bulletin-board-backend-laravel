<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Factory as Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Mail\Message; 

class PasswordController extends Controller
{
    protected $validator;

    public function __construct(Validator $validator)
    {
        $this->validator = $validator;
    }

    protected function sendResponse(bool $success, string $message, array $data = [], int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => $success,
            'message' => $message,
            'errors' => $data['errors'] ?? [],
            'data' => $data['data'] ?? []
        ], $statusCode);
    }

    public function change(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return $this->sendResponse(false, 'User not authenticated.', [], 401);
        }

        $rules = [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|different:current_password',
            'new_confirm_password' => 'required|string|same:new_password',
        ];

        $messages = [
            'current_password.required' => 'Current password is required.',
            'new_password.required' => 'New password is required.',
            'new_password.different' => 'New password must be different from the current password.',
            'new_confirm_password.required' => 'Confirm password is required.',
            'new_confirm_password.same' => 'Confirm password must match the new password.',
        ];

        $validator = $this->validator->make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return $this->sendResponse(false, 'Validation errors', [
                'errors' => $validator->errors()->toArray(),
                'data' => []
            ], 422);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->sendResponse(false, 'Current password is incorrect.', [], 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return $this->sendResponse(true, 'Password changed successfully.');
    }

    public function forgot(Request $request): JsonResponse
    {
        $rules = [
            'email' => 'required|email|exists:users,email',
        ];

        $validator = $this->validator->make($request->all(), $rules);

        if ($validator->fails()) {
           return $this->sendResponse(false, 'Validation errors', [
                'errors' => $validator->errors()->toArray(),
                'data' => []
            ], 422);
        }

        $broker = Password::broker();

        $status = $broker->sendResetLink(
            $request->only('email'),
            function ($user, $token) {
                Mail::send(
                    'emails.password-reset',
                    ['token' => $token, 'user' => $user],
                    function (Message $message) use ($user) {
                        $message->to($user->email)
                                ->subject('Reset Your Password Notification');
                    }
                );
            }
        );

        if ($status == Password::RESET_LINK_SENT) {
            return $this->sendResponse(true, trans($status));
        }

        return $this->sendResponse(false, trans($status), [], 500);
    }

    // reset password
    public function reset(Request $request): JsonResponse
    {
        $rules = [
            'email' => 'required|email|exists:users,email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ];

        $messages = [
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email.',
            'email.exists' => 'We could not find a user with that email.',
            'token.required' => 'Reset token is required.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ];

        $validator = $this->validator->make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return $this->sendResponse(false, 'Validation errors', [
                'errors' => $validator->errors()->toArray(),
                'data' => []
            ], 422);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->password = Hash::make($request->password);
                $user->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return $this->sendResponse(true, 'Password reset successfully.');
        }

        return $this->sendResponse(false, 'The token is invalid or expired.', [], 400);
    }

}
