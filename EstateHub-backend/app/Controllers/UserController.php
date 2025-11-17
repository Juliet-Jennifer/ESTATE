<?php
namespace App\Controllers;

use app\Models\User;
use app\Middleware\AuthMiddleware;
use app\Utils\Response;
use app\Services\FileUploadService;
use app\Utils\Logger;

class UserController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function getProfile() {
        AuthMiddleware::handle();
        $user = AuthMiddleware::getUser();

        $userData = $this->userModel->find($user['sub']);
        if (!$userData) {
            Response::error('User not found', 'NOT_FOUND', [], 404);
        }

        // Remove sensitive data
        unset($userData['password_hash'], $userData['reset_token'], $userData['reset_token_expiry']);

        Response::success(['user' => $userData]);
    }

    public function updateProfile() {
        AuthMiddleware::handle();
        $user = AuthMiddleware::getUser();

        $input = json_decode(file_get_contents('php://input'), true);
        $updateData = [];

        $allowedFields = ['full_name', 'phone'];
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateData[$field] = trim($input[$field]);
            }
        }

        // Handle avatar upload
        if (!empty($_FILES['avatar'])) {
            $file = $_FILES['avatar'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                $uploadResult = FileUploadService::uploadImage($file, 'avatars');
                $updateData['avatar'] = $uploadResult['url'];
                
                // Delete old avatar if exists
                $currentUser = $this->userModel->find($user['sub']);
                if ($currentUser['avatar']) {
                    $oldAvatarPath = str_replace('/uploads/avatars/', UPLOAD_PATH . 'avatars/', $currentUser['avatar']);
                    if (file_exists($oldAvatarPath)) {
                        unlink($oldAvatarPath);
                    }
                }
            }
        }

        if (!empty($updateData)) {
            $this->userModel->update($user['sub'], $updateData);
            Logger::info('User profile updated', ['user_id' => $user['sub'], 'updated_fields' => array_keys($updateData)]);
        }

        // Return updated user data
        $updatedUser = $this->userModel->find($user['sub']);
        unset($updatedUser['password_hash'], $updatedUser['reset_token'], $updatedUser['reset_token_expiry']);

        Response::success(['user' => $updatedUser], 'Profile updated successfully');
    }

    public function changePassword() {
        AuthMiddleware::handle();
        $user = AuthMiddleware::getUser();

        $input = json_decode(file_get_contents('php://input'), true);

        $required = ['current_password', 'new_password'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                Response::error("Field {$field} is required", 'VALIDATION_ERROR');
            }
        }

        // Verify current password
        $userData = $this->userModel->find($user['sub']);
        if (!$this->userModel->verifyPassword($input['current_password'], $userData['password_hash'])) {
            Response::error('Current password is incorrect', 'VALIDATION_ERROR', [], 401);
        }

        // Update password
        $this->userModel->updatePassword($user['sub'], $input['new_password']);

        Logger::info('User password changed', ['user_id' => $user['sub']]);

        Response::success([], 'Password changed successfully');
    }
}