<?php
namespace App\Controllers;

use App\Models\User;
use App\Middleware\AuthMiddleware;
use App\Utils\Response;
use App\Utils\Logger;

class AuthController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    /**
     * Register a new user
     */
    public function register() {
        $input = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::error('Invalid JSON data', 'VALIDATION_ERROR');
            return;
        }

        // Validate required fields
        $required = ['email', 'password', 'full_name'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                Response::error("Field {$field} is required", 'VALIDATION_ERROR');
                return;
            }
        }

        // Validate email
        if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
            Response::error('Invalid email format', 'VALIDATION_ERROR');
            return;
        }

        // Validate password strength
        if (strlen($input['password']) < 6) {
            Response::error('Password must be at least 6 characters', 'VALIDATION_ERROR');
            return;
        }

        // Check if email already exists
        $existingUser = $this->userModel->findByEmail($input['email']);
        if ($existingUser) {
            Response::error('Email already registered', 'VALIDATION_ERROR');
            return;
        }

        try {
            // Hash password
            $hashedPassword = password_hash($input['password'], PASSWORD_BCRYPT);

            // Create user
            $userId = $this->userModel->create([
                'email' => trim($input['email']),
                'password' => $hashedPassword,
                'full_name' => trim($input['full_name']),
                'phone' => $input['phone'] ?? null,
                'role' => $input['role'] ?? 'tenant',
                'status' => 'active'
            ]);

            // Generate JWT token
            $token = AuthMiddleware::generateToken([
                'sub' => $userId,
                'email' => $input['email'],
                'full_name' => $input['full_name'],
                'role' => $input['role'] ?? 'tenant'
            ]);

            Logger::info('User registered', ['user_id' => $userId, 'email' => $input['email']]);

            Response::success([
                'user' => [
                    'id' => $userId,
                    'email' => $input['email'],
                    'full_name' => $input['full_name'],
                    'role' => $input['role'] ?? 'tenant'
                ],
                'token' => $token
            ], 'Registration successful', 201);

        } catch (\Exception $e) {
            Logger::error('Registration failed', ['error' => $e->getMessage()]);
            Response::error('Registration failed', 'SERVER_ERROR', [], 500);
        }
    }

    /**
     * Login user
     */
    public function login() {
        $input = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::error('Invalid JSON data', 'VALIDATION_ERROR');
            return;
        }

        // Validate required fields
        if (empty($input['email']) || empty($input['password'])) {
            Response::error('Email and password are required', 'VALIDATION_ERROR');
            return;
        }

        try {
            // Find user by email
            $user = $this->userModel->findByEmail($input['email']);

            if (!$user) {
                Logger::warning('Login attempt with non-existent email', ['email' => $input['email']]);
                Response::error('Invalid credentials', 'UNAUTHORIZED', [], 401);
                return;
            }

    
            // Verify password using the correct column
if (!password_verify($input['password'], $user['password_hash'])) {
    Logger::warning('Login attempt with incorrect password', ['email' => $input['email']]);
    Response::error('Invalid credentials', 'UNAUTHORIZED', [], 401);
    return;
}


            // Check if user is active
            if ($user['status'] !== 'active') {
                Response::error('Account is not active', 'FORBIDDEN', [], 403);
                return;
            }

            // Generate JWT token
            $token = AuthMiddleware::generateToken([
                'sub' => $user['id'],
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'role' => $user['role']
            ]);

            // Update last login
            $this->userModel->updateLastLogin($user['id']);

            Logger::info('User logged in', ['user_id' => $user['id'], 'email' => $user['email']]);

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

    /**
     * Logout user (client-side token removal, server-side logging)
     */
    public function logout() {
        $user = AuthMiddleware::getUser();
        
        if ($user) {
            Logger::info('User logged out', ['user_id' => $user['sub']]);
        }

        Response::success([], 'Logout successful');
    }

    /**
     * Forgot password
     */
    public function forgotPassword() {
        $input = json_decode(file_get_contents('php://input'), true);

        if (empty($input['email'])) {
            Response::error('Email is required', 'VALIDATION_ERROR');
            return;
        }

        try {
            $user = $this->userModel->findByEmail($input['email']);

            if (!$user) {
                // Don't reveal if email exists or not
                Response::success([], 'If the email exists, a reset link has been sent');
                return;
            }

            // Generate reset token
            $resetToken = bin2hex(random_bytes(32));
            $resetTokenExpiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $this->userModel->update($user['id'], [
                'reset_token' => $resetToken,
                'reset_token_expires' => $resetTokenExpiry
            ]);

            // TODO: Send email with reset link
            // $resetLink = $_ENV['APP_URL'] . "/reset-password?token={$resetToken}";
            // EmailService::send($user['email'], 'Password Reset', $resetLink);

            Logger::info('Password reset requested', ['user_id' => $user['id'], 'email' => $user['email']]);

            Response::success(['reset_token' => $resetToken], 'If the email exists, a reset link has been sent');

        } catch (\Exception $e) {
            Logger::error('Forgot password failed', ['error' => $e->getMessage()]);
            Response::error('Failed to process request', 'SERVER_ERROR', [], 500);
        }
    }

    /**
     * Reset password
     */
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

            // Check if token has expired
            if (strtotime($user['reset_token_expires']) < time()) {
                Response::error('Reset token has expired', 'VALIDATION_ERROR');
                return;
            }

            // Hash new password
            $hashedPassword = password_hash($input['password'], PASSWORD_BCRYPT);

            // Update password and clear reset token
            $this->userModel->update($user['id'], [
                'password' => $hashedPassword,
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

    /**
     * Get current user profile
     */
    public function me() {
        $user = AuthMiddleware::requireAuth();

        try {
            $userData = $this->userModel->find($user['sub']);

            if (!$userData) {
                Response::error('User not found', 'NOT_FOUND', [], 404);
                return;
            }

            unset($userData['password']);
            unset($userData['reset_token']);
            unset($userData['reset_token_expires']);

            Response::success(['user' => $userData]);

        } catch (\Exception $e) {
            Logger::error('Failed to fetch user profile', ['error' => $e->getMessage()]);
            Response::error('Failed to fetch profile', 'SERVER_ERROR', [], 500);
        }
    }

    /**
     * Refresh token
     */
    public function refresh() {
        $user = AuthMiddleware::requireAuth();

        try {
            $newToken = AuthMiddleware::generateToken([
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