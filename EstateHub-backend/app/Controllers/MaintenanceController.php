<?php
namespace app\Controllers;

use app\Models\Maintenance;
use app\Models\Tenant;
use app\Models\Property;
use app\Middleware\AuthMiddleware;
use app\Middleware\RoleMiddleware;
use app\Utils\Response;
use app\Services\FileUploadService;
use app\Utils\Logger;

class MaintenanceController {
    private $maintenanceModel;
    private $tenantModel;
    private $propertyModel;

    public function __construct() {
        $this->maintenanceModel = new Maintenance();
        $this->tenantModel = new Tenant();
        $this->propertyModel = new Property();
    }

    public function index() {
        AuthMiddleware::handle();
        $user = AuthMiddleware::getUser();

        $queryParams = $_GET;
        $page = max(1, intval($queryParams['page'] ?? 1));
        $limit = min(50, max(1, intval($queryParams['limit'] ?? 10)));

        if ($user['role'] === ROLE_ADMIN) {
            $requests = $this->maintenanceModel->getAllRequests($limit, ($page - 1) * $limit, $queryParams);
            $total = $this->maintenanceModel->countAllRequests($queryParams);
        } else {
            $tenant = $this->tenantModel->getTenantByUserId($user['sub']);
            if (!$tenant) {
                Response::error('Tenant record not found', 'NOT_FOUND', [], 404);
            }
            $requests = $this->maintenanceModel->getRequestsByTenant($tenant['id'], $limit, ($page - 1) * $limit);
            $total = $this->maintenanceModel->countRequestsByTenant($tenant['id']);
        }

        Response::success([
            'requests' => $requests,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }

    public function show($id) {
        AuthMiddleware::handle();
        $user = AuthMiddleware::getUser();

        $request = $this->maintenanceModel->find($id);
        if (!$request) {
            Response::error('Maintenance request not found', 'NOT_FOUND', [], 404);
        }

        // Check permissions
        if ($user['role'] === ROLE_TENANT) {
            $tenant = $this->tenantModel->getTenantByUserId($user['sub']);
            if (!$tenant || $request['tenant_id'] != $tenant['id']) {
                Response::error('Access denied', 'FORBIDDEN', [], 403);
            }
        }

        Response::success(['request' => $request]);
    }

    public function store() {
        AuthMiddleware::handle();
        $user = AuthMiddleware::getUser();

        $input = json_decode(file_get_contents('php://input'), true);

        $required = ['property_id', 'title', 'description', 'priority', 'category'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                Response::error("Field {$field} is required", 'VALIDATION_ERROR');
            }
        }

        // For tenants, verify they're assigned to the property
        if ($user['role'] === ROLE_TENANT) {
            $tenant = $this->tenantModel->getTenantByUserId($user['sub']);
            if (!$tenant || $tenant['property_id'] != $input['property_id']) {
                Response::error('You can only submit requests for your assigned property', 'FORBIDDEN', [], 403);
            }
            $tenantId = $tenant['id'];
        } else {
            // Admin can specify tenant_id or use the current tenant of the property
            $tenantId = $input['tenant_id'] ?? $this->getCurrentTenantForProperty($input['property_id']);
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
                    
                    $uploadResult = FileUploadService::uploadImage($file, 'maintenance');
                    $uploadedImages[] = $uploadResult['url'];
                }
            }
        }

        $requestId = $this->maintenanceModel->create([
            'property_id' => $input['property_id'],
            'tenant_id' => $tenantId,
            'reported_by' => $user['sub'],
            'title' => trim($input['title']),
            'description' => trim($input['description']),
            'priority' => $input['priority'],
            'category' => $input['category'],
            'status' => 'pending',
            'images' => json_encode($uploadedImages)
        ]);

        Logger::info('Maintenance request created', [
            'request_id' => $requestId,
            'property_id' => $input['property_id'],
            'reported_by' => $user['sub']
        ]);

        Response::success(['request_id' => $requestId], 'Maintenance request submitted successfully');
    }

    public function update($id) {
        RoleMiddleware::adminOnly();

        $request = $this->maintenanceModel->find($id);
        if (!$request) {
            Response::error('Maintenance request not found', 'NOT_FOUND', [], 404);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $updateData = [];

        $allowedFields = ['status', 'assigned_to', 'estimated_cost', 'actual_cost', 'completion_date'];
        foreach ($allowedFields as $field) {
            if (isset($input[$field])) {
                $updateData[$field] = $input[$field];
            }
        }

        if (!empty($updateData)) {
            $this->maintenanceModel->update($id, $updateData);
            Logger::info('Maintenance request updated', ['request_id' => $id, 'updates' => $updateData]);
        }

        Response::success([], 'Maintenance request updated successfully');
    }

    public function destroy($id) {
        AuthMiddleware::handle();
        $user = AuthMiddleware::getUser();

        $request = $this->maintenanceModel->find($id);
        if (!$request) {
            Response::error('Maintenance request not found', 'NOT_FOUND', [], 404);
        }

        // Only creator or admin can delete
        if ($user['role'] === ROLE_TENANT && $request['reported_by'] != $user['sub']) {
            Response::error('You can only delete your own maintenance requests', 'FORBIDDEN', [], 403);
        }

        $this->maintenanceModel->delete($id);
        Logger::info('Maintenance request deleted', ['request_id' => $id, 'deleted_by' => $user['sub']]);

        Response::success([], 'Maintenance request deleted successfully');
    }

    private function getCurrentTenantForProperty($propertyId) {
        $tenants = $this->tenantModel->getActiveTenants($propertyId);
        return $tenants[0]['id'] ?? null;
    }
}