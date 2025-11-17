<?php
namespace App\Controllers;  // Changed to uppercase App

use App\Models\Property;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Utils\Response;
use App\Services\FileUploadService;
use App\Utils\Logger;

class PropertiesController {
    private $propertyModel;

    public function __construct() {
        $this->propertyModel = new Property();
    }

    public function index() {
        $queryParams = $_GET;
        
        $page = max(1, intval($queryParams['page'] ?? 1));
        $limit = min(50, max(1, intval($queryParams['limit'] ?? 10)));
        $offset = ($page - 1) * $limit;

        $filters = [
            'city' => $queryParams['city'] ?? null,
            'min_price' => $queryParams['min_price'] ?? null,
            'max_price' => $queryParams['max_price'] ?? null,
            'bedrooms' => $queryParams['bedrooms'] ?? null,
            'sort_by' => $queryParams['sort_by'] ?? 'created_at',
            'sort_order' => $queryParams['sort_order'] ?? 'desc'
        ];

        // Clean filters
        foreach ($filters as $key => $value) {
            if (empty($value)) {
                unset($filters[$key]);
            }
        }

        $properties = $this->propertyModel->getAvailableProperties($filters, $limit, $offset);
        $total = $this->propertyModel->countAvailableProperties($filters);

        Response::success([
            'properties' => $properties,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    public function show($id) {
        $property = $this->propertyModel->find($id);
        
        if (!$property) {
            Response::error('Property not found', 'NOT_FOUND', [], 404);
            return; // Added return for safety
        }

        Response::success(['property' => $property]);
    }

    public function store() {
        RoleMiddleware::adminOnly();
        $user = AuthMiddleware::getUser();

        // Check if this is multipart/form-data or JSON
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'multipart/form-data') !== false) {
            // Handle multipart form data
            $input = $_POST;
        } else {
            // Handle JSON
            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Response::error('Invalid JSON data', 'VALIDATION_ERROR');
                return;
            }
        }

        // Validate required fields
        $required = ['name', 'description', 'location', 'city', 'bedrooms', 'bathrooms', 'size', 'price'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                Response::error("Field {$field} is required", 'VALIDATION_ERROR');
                return;
            }
        }

        // Validate numeric fields
        if ($input['bedrooms'] < 0 || $input['bathrooms'] < 0 || $input['size'] < 0 || $input['price'] < 0) {
            Response::error('Numeric fields must be positive', 'VALIDATION_ERROR');
            return;
        }

        // Handle file uploads
        $uploadedImages = [];
        if (!empty($_FILES['images'])) {
            foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['images']['name'][$key],
                        'type' => $_FILES['images']['type'][$key],
                        'tmp_name' => $tmpName,
                        'error' => $_FILES['images']['error'][$key],
                        'size' => $_FILES['images']['size'][$key]
                    ];
                    
                    try {
                        $uploadResult = FileUploadService::uploadImage($file);
                        $uploadedImages[] = $uploadResult['url'];
                    } catch (\Exception $e) {
                        Response::error('Image upload failed: ' . $e->getMessage(), 'UPLOAD_ERROR');
                        return;
                    }
                }
            }
        }

        // Handle amenities - check if it's already an array or JSON string
        $amenities = $input['amenities'] ?? [];
        if (is_string($amenities)) {
            $amenities = json_decode($amenities, true) ?? [];
        }

        // Create property
        try {
            $propertyId = $this->propertyModel->create([
                'owner_id' => $user['sub'],
                'name' => trim($input['name']),
                'description' => trim($input['description']),
                'location' => trim($input['location']),
                'city' => trim($input['city']),
                'bedrooms' => intval($input['bedrooms']),
                'bathrooms' => intval($input['bathrooms']),
                'size' => intval($input['size']),
                'price' => floatval($input['price']),
                'amenities' => json_encode($amenities),
                'images' => json_encode($uploadedImages),
                'featured_image' => $uploadedImages[0] ?? null,
                'status' => PROPERTY_AVAILABLE
            ]);

            Logger::info('Property created', ['property_id' => $propertyId, 'owner_id' => $user['sub']]);

            Response::success(['property_id' => $propertyId], 'Property created successfully', 201);
        } catch (\Exception $e) {
            Logger::error('Property creation failed', ['error' => $e->getMessage()]);
            Response::error('Failed to create property', 'SERVER_ERROR', [], 500);
        }
    }

    public function update($id) {
        RoleMiddleware::adminOnly();
        $user = AuthMiddleware::getUser();

        $property = $this->propertyModel->find($id);
        if (!$property) {
            Response::error('Property not found', 'NOT_FOUND', [], 404);
            return;
        }

        // Check if user owns the property
        if ($property['owner_id'] != $user['sub']) {
            Response::error('You can only update your own properties', 'FORBIDDEN', [], 403);
            return;
        }

        // Check content type
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'multipart/form-data') !== false) {
            $input = $_POST;
        } else {
            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Response::error('Invalid JSON data', 'VALIDATION_ERROR');
                return;
            }
        }

        $updateData = [];

        $allowedFields = ['name', 'description', 'location', 'city', 'bedrooms', 'bathrooms', 'size', 'price', 'amenities', 'status'];
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                if ($field === 'amenities' && is_string($input[$field])) {
                    $updateData[$field] = json_encode(json_decode($input[$field], true) ?? []);
                } else if ($field === 'amenities' && is_array($input[$field])) {
                    $updateData[$field] = json_encode($input[$field]);
                } else {
                    $updateData[$field] = $input[$field];
                }
            }
        }

        // Handle image uploads
        if (!empty($_FILES['images'])) {
            $uploadedImages = json_decode($property['images'], true) ?? [];
            
            foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file = [
                        'name' => $_FILES['images']['name'][$key],
                        'type' => $_FILES['images']['type'][$key],
                        'tmp_name' => $tmpName,
                        'error' => $_FILES['images']['error'][$key],
                        'size' => $_FILES['images']['size'][$key]
                    ];
                    
                    try {
                        $uploadResult = FileUploadService::uploadImage($file);
                        $uploadedImages[] = $uploadResult['url'];
                    } catch (\Exception $e) {
                        Response::error('Image upload failed: ' . $e->getMessage(), 'UPLOAD_ERROR');
                        return;
                    }
                }
            }
            $updateData['images'] = json_encode($uploadedImages);
            
            // Update featured image if not set or if first image changed
            if (!empty($uploadedImages)) {
                $updateData['featured_image'] = $uploadedImages[0];
            }
        }

        if (!empty($updateData)) {
            try {
                $this->propertyModel->update($id, $updateData);
                Logger::info('Property updated', ['property_id' => $id, 'owner_id' => $user['sub']]);
            } catch (\Exception $e) {
                Logger::error('Property update failed', ['error' => $e->getMessage()]);
                Response::error('Failed to update property', 'SERVER_ERROR', [], 500);
                return;
            }
        }

        Response::success([], 'Property updated successfully');
    }

    public function destroy($id) {
        RoleMiddleware::adminOnly();
        $user = AuthMiddleware::getUser();

        $property = $this->propertyModel->find($id);
        if (!$property) {
            Response::error('Property not found', 'NOT_FOUND', [], 404);
            return;
        }

        // Check ownership
        if ($property['owner_id'] != $user['sub']) {
            Response::error('You can only delete your own properties', 'FORBIDDEN', [], 403);
            return;
        }

        // Check if property has active tenants
        // TODO: Implement this check with TenantsModel
        // $activeTenants = $this->tenantsModel->getActiveTenantsForProperty($id);
        // if (!empty($activeTenants)) {
        //     Response::error('Cannot delete property with active tenants', 'VALIDATION_ERROR');
        //     return;
        // }

        try {
            $this->propertyModel->delete($id);
            Logger::info('Property deleted', ['property_id' => $id, 'owner_id' => $user['sub']]);

            Response::success([], 'Property deleted successfully');
        } catch (\Exception $e) {
            Logger::error('Property deletion failed', ['error' => $e->getMessage()]);
            Response::error('Failed to delete property', 'SERVER_ERROR', [], 500);
        }
    }
}