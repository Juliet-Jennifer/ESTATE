<?php
namespace App\Controllers;

use app\Models\Tenant;
use app\Models\Property;
use app\Models\User;
use app\Middleware\AuthMiddleware;
use app\Middleware\RoleMiddleware;
use app\Utils\Response;
use app\Utils\Logger;

class TenantsController {
    private $tenantModel;
    private $propertyModel;
    private $userModel;

    public function __construct() {
        $this->tenantModel = new Tenant();
        $this->propertyModel = new Property();
        $this->userModel = new User();
    }

    public function index() {
        RoleMiddleware::adminOnly();

        $queryParams = $_GET;
        $page = max(1, intval($queryParams['page'] ?? 1));
        $limit = min(50, max(1, intval($queryParams['limit'] ?? 10)));
        $status = $queryParams['status'] ?? 'active';
        $propertyId = $queryParams['property_id'] ?? null;

        $tenants = $this->tenantModel->getActiveTenants($propertyId);

        Response::success(['tenants' => $tenants]);
    }

    public function show($id) {
        $user = AuthMiddleware::getUser();
        
        $tenant = $this->tenantModel->find($id);
        if (!$tenant) {
            Response::error('Tenant not found', 'NOT_FOUND', [], 404);
        }

        // Tenants can only view their own data, admins can view all
        if ($user['role'] === ROLE_TENANT && $tenant['user_id'] != $user['sub']) {
            Response::error('Access denied', 'FORBIDDEN', [], 403);
        }

        Response::success(['tenant' => $tenant]);
    }

    public function store() {
        RoleMiddleware::adminOnly();

        $input = json_decode(file_get_contents('php://input'), true);

        $required = ['user_id', 'property_id', 'lease_start_date', 'lease_end_date', 'monthly_rent', 'deposit_amount', 'emergency_contact_name', 'emergency_contact_phone'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                Response::error("Field {$field} is required", 'VALIDATION_ERROR');
            }
        }

        // Check if property exists and is available
        $property = $this->propertyModel->find($input['property_id']);
        if (!$property) {
            Response::error('Property not found', 'VALIDATION_ERROR');
        }

        if ($property['status'] !== PROPERTY_AVAILABLE) {
            Response::error('Property is not available', 'VALIDATION_ERROR');
        }

        // Check if user exists
        $user = $this->userModel->find($input['user_id']);
        if (!$user || $user['role'] !== ROLE_TENANT) {
            Response::error('Invalid tenant user', 'VALIDATION_ERROR');
        }

        // Check if user is already a tenant elsewhere
        $existingTenant = $this->tenantModel->getTenantByUserId($input['user_id']);
        if ($existingTenant) {
            Response::error('User is already a tenant at another property', 'VALIDATION_ERROR');
        }

        // Validate lease dates
        $leaseStart = strtotime($input['lease_start_date']);
        $leaseEnd = strtotime($input['lease_end_date']);
        
        if ($leaseEnd <= $leaseStart) {
            Response::error('Lease end date must be after start date', 'VALIDATION_ERROR');
        }

        // Create tenant record
        $tenantId = $this->tenantModel->create([
            'user_id' => $input['user_id'],
            'property_id' => $input['property_id'],
            'lease_start_date' => $input['lease_start_date'],
            'lease_end_date' => $input['lease_end_date'],
            'monthly_rent' => floatval($input['monthly_rent']),
            'deposit_amount' => floatval($input['deposit_amount']),
            'emergency_contact_name' => trim($input['emergency_contact_name']),
            'emergency_contact_phone' => trim($input['emergency_contact_phone']),
            'move_in_date' => $input['lease_start_date'],
            'status' => 'active'
        ]);

        // Update property status
        $this->propertyModel->updateStatus($input['property_id'], PROPERTY_OCCUPIED);

        Logger::info('Tenant assigned to property', [
            'tenant_id' => $tenantId,
            'property_id' => $input['property_id'],
            'user_id' => $input['user_id']
        ]);

        Response::success(['tenant_id' => $tenantId], 'Tenant assigned successfully');
    }

    public function getCurrentTenant() {
        AuthMiddleware::handle();
        $user = AuthMiddleware::getUser();

        if ($user['role'] !== ROLE_TENANT) {
            Response::error('User is not a tenant', 'VALIDATION_ERROR');
        }

        $tenant = $this->tenantModel->getTenantByUserId($user['sub']);
        if (!$tenant) {
            Response::error('Tenant record not found', 'NOT_FOUND', [], 404);
        }

        Response::success(['tenant' => $tenant]);
    }
}