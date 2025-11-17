<?php
namespace App\Controllers;

use App\Models\User;
use App\Middleware\AuthMiddleware;
use App\Utils\Response;
use App\Utils\Logger;
use App\Utils\JWTHandler;

class AuthController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function register() {
        $input = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::error('Invalid JSON data', 'VALIDATION_ERROR');
            return;
        }

        // Add phone to required fields
        $required = ['email', 'password', 'full_name', 'phone', 'role'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                Response::error("Field {$field} is required", 'VALIDATION_ERROR');
                return;
            }
        }

        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            Response::error('Invalid email format', 'VALIDATION_ERROR');
            return;
        }

        if (strlen($input['password']) < 6) {
            Response::error('Password must be at least 6 characters', 'VALIDATION_ERROR');
            return;
        }

        // Validate and sanitize phone number
        $phone = trim($input['phone']);
        
        // Remove spaces, dashes, parentheses
        $phone = preg_replace('/[\s\-\(\)]/', '', $phone);
        
        // Check length (max 20 chars for DB)
        if (strlen($phone) > 20) {
            Response::error('Phone number is too long (max 20 characters)', 'VALIDATION_ERROR');
            return;
        }
        
        // Validate Kenyan phone format: +254712345678 or 0712345678
        if (!preg_match('/^(\+?254|0)[17]\d{8}$/', $phone)) {
            Response::error('Invalid phone number format. Use: +254712345678 or 0712345678', 'VALIDATION_ERROR');
            return;
        }
        
        // Normalize to international format
        if (strpos($phone, '0') === 0) {
            $phone = '+254' . substr($phone, 1);
        } elseif (strpos($phone, '254') === 0) {
            $phone = '+' . $phone;
        }

        // Validate role
        if (!in_array($input['role'], ['admin', 'tenant'])) {
            Response::error('Invalid role. Must be either "admin" or "tenant"', 'VALIDATION_ERROR');
            return;
        }

        $existingUser = $this->userModel->findByEmail($input['email']);
        if ($existingUser) {
            Response::error('Email already registered', 'VALIDATION_ERROR');
            return;
        }

        try {
            $hashedPassword = password_hash($input['password'], PASSWORD_BCRYPT);

            $userId = $this->userModel->create([
                'email' => trim($input['email']),
                'password_hash' => $hashedPassword,
                'full_name' => trim($input['full_name']),
                'phone' => $phone, // Use sanitized phone
                'role' => $input['role'],
                'status' => 'active'
            ]);

            $token = JWTHandler::generateToken([
                'sub' => $userId,
                'email' => $input['email'],
                'full_name' => $input['full_name'],
                'role' => $input['role']
            ]);

            Logger::info('User registered', ['user_id' => $userId]);

            Response::success([
                'user' => [
                    'id' => $userId,
                    'email' => $input['email'],
                    'full_name' => $input['full_name'],
                    'phone' => $phone,
                    'role' => $input['role']
                ],
                'token' => $token
            ], 'Registration successful', 201);

        } catch (\Exception $e) {
            Logger::error('Registration failed', ['error' => $e->getMessage()]);
            Response::error('Registration failed: ' . $e->getMessage(), 'SERVER_ERROR', [], 500);
        }
    }

    public function login() {
        $input = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::error('Invalid JSON data', 'VALIDATION_ERROR');
            return;
        }

        if (empty($input['email']) || empty($input['password'])) {
            Response::error('Email and password are required', 'VALIDATION_ERROR');
            return;
        }

        try {
            $user = $this->userModel->findByEmail($input['email']);

            if (!$user) {
                Logger::warning('Login attempt with non-existent email', ['email' => $input['email']]);
                Response::error('Invalid credentials', 'UNAUTHORIZED', [], 401);
                return;
            }

            if (!password_verify($input['password'], $user['password_hash'])) {
                Logger::warning('Incorrect password attempt', ['email' => $input['email']]);
                Response::error('Invalid credentials', 'UNAUTHORIZED', [], 401);
                return;
            }

            if ($user['status'] !== 'active') {
                Response::error('Account is not active', 'FORBIDDEN', [], 403);
                return;
            }

            $token = JWTHandler::generateToken([
                'sub' => $user['id'],
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'role' => $user['role']
            ]);

            $this->userModel->updateLastLogin($user['id']);

            Logger::info('User logged in', ['user_id' => $user['id']]);

            Response::success([
                'user' => [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'full_name' => $user['full_name'],
                    'role' => $user['role'],
                    'phone' => $user['phone']
                ],
                'token' => $token
            ], 'Login successful');

        } catch (\Exception $e) {
            Logger::error('Login failed', ['error' => $e->getMessage()]);
            Response::error('Login failed', 'SERVER_ERROR', [], 500);
        }
    }

    public function logout() {
        $user = AuthMiddleware::getUser();
        if ($user) {
            Logger::info('User logged out', ['user_id' => $user['sub']]);
        }
        Response::success([], 'Logout successful');
    }

    public function forgotPassword() {
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['email'])) {
            Response::error('Email is required', 'VALIDATION_ERROR');
            return;
        }

        try {
            $user = $this->userModel->findByEmail($input['email']);

            // Always return success to prevent email enumeration
            $placeholderResponse = fn() => Response::success([], 'If the email exists, a reset link has been sent');

            if (!$user) {
                $placeholderResponse();
                return;
            }

            $resetToken = bin2hex(random_bytes(32));
            $resetTokenExpiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $this->userModel->update($user['id'], [
                'reset_token' => $resetToken,
                'reset_token_expires' => $resetTokenExpiry
            ]);

            Logger::info('Password reset requested', ['user_id' => $user['id']]);

            Response::success(['reset_token' => $resetToken], 'If the email exists, a reset link has been sent');

        } catch (\Exception $e) {
            Logger::error('Forgot password error', ['error' => $e->getMessage()]);
            Response::error('Failed to process request', 'SERVER_ERROR', [], 500);
        }
    }

    public function resetPassword() {
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['token']) || empty($input['password'])) {
            Response::error('Token and new password are required', 'VALIDATION_ERROR');
            return;
        }

        if (strlen($input['password']) < 6) {
            Response::error('Password must be at least 6 characters', 'VALIDATION_ERROR');
            return;
        }

        try {
            $user = $this->userModel->findByResetToken($input['token']);

            if (!$user) {
                Response::error('Invalid or expired reset token', 'VALIDATION_ERROR');
                return;
            }

            if (strtotime($user['reset_token_expires']) < time()) {
                Response::error('Reset token has expired', 'VALIDATION_ERROR');
                return;
            }

            $hashedPassword = password_hash($input['password'], PASSWORD_BCRYPT);

            $this->userModel->update($user['id'], [
                'password_hash' => $hashedPassword,
                'reset_token' => null,
                'reset_token_expires' => null
            ]);

            Logger::info('Password reset successful', ['user_id' => $user['id']]);

            Response::success([], 'Password reset successful');

        } catch (\Exception $e) {
            Logger::error('Password reset failed', ['error' => $e->getMessage()]);
            Response::error('Failed to reset password', 'SERVER_ERROR', [], 500);
        }
    }

    public function me() {
        $user = AuthMiddleware::requireAuth();

        try {
            $userData = $this->userModel->find($user['sub']);

            if (!$userData) {
                Response::error('User not found', 'NOT_FOUND', [], 404);
                return;
            }

            unset($userData['password_hash'], $userData['reset_token'], $userData['reset_token_expires']);

            Response::success(['user' => $userData]);

        } catch (\Exception $e) {
            Logger::error('Fetch profile failed', ['error' => $e->getMessage()]);
            Response::error('Failed to fetch profile', 'SERVER_ERROR', [], 500);
        }
    }

    public function refresh() {
        $user = AuthMiddleware::requireAuth();

        try {
            $newToken = JWTHandler::generateToken([
                'sub' => $user['sub'],
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'role' => $user['role']
            ]);

            Response::success(['token' => $newToken], 'Token refreshed successfully');

        } catch (\Exception $e) {
            Logger::error('Token refresh failed', ['error' => $e->getMessage()]);
            Response::error('Failed to refresh token', 'SERVER_ERROR', [], 500);
        }
    }
}