<?php
namespace App\Controllers;

use app\Models\Payment;
use app\Models\Tenant;
use app\Middleware\AuthMiddleware;
use app\Middleware\RoleMiddleware;
use app\Utils\Response;
use app\Services\EmailService;
use app\Utils\Logger;

class PaymentsController {
    private $paymentModel;
    private $tenantModel;
    private $emailService;

    public function __construct() {
        $this->paymentModel = new Payment();
        $this->tenantModel = new Tenant();
        $this->emailService = new EmailService();
    }

    public function index() {
        AuthMiddleware::handle();
        $user = AuthMiddleware::getUser();

        $queryParams = $_GET;
        $page = max(1, intval($queryParams['page'] ?? 1));
        $limit = min(50, max(1, intval($queryParams['limit'] ?? 10)));

        if ($user['role'] === ROLE_ADMIN) {
            $payments = $this->paymentModel->getAllPayments($limit, ($page - 1) * $limit, $queryParams);
            $total = $this->paymentModel->countAllPayments($queryParams);
        } else {
            $tenant = $this->tenantModel->getTenantByUserId($user['sub']);
            if (!$tenant) {
                Response::error('Tenant record not found', 'NOT_FOUND', [], 404);
            }
            $payments = $this->paymentModel->getPaymentsByTenant($tenant['id'], $limit, ($page - 1) * $limit);
            $total = $this->paymentModel->countPaymentsByTenant($tenant['id']);
        }

        Response::success([
            'payments' => $payments,
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

        $payment = $this->paymentModel->find($id);
        if (!$payment) {
            Response::error('Payment not found', 'NOT_FOUND', [], 404);
        }

        // Check permissions
        if ($user['role'] === ROLE_TENANT) {
            $tenant = $this->tenantModel->getTenantByUserId($user['sub']);
            if (!$tenant || $payment['tenant_id'] != $tenant['id']) {
                Response::error('Access denied', 'FORBIDDEN', [], 403);
            }
        }

        Response::success(['payment' => $payment]);
    }

    public function store() {
        RoleMiddleware::adminOnly();

        $input = json_decode(file_get_contents('php://input'), true);

        $required = ['tenant_id', 'property_id', 'amount', 'payment_type', 'payment_method', 'transaction_reference', 'payment_date'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                Response::error("Field {$field} is required", 'VALIDATION_ERROR');
            }
        }

        // Validate tenant and property relationship
        $tenant = $this->tenantModel->find($input['tenant_id']);
        if (!$tenant || $tenant['property_id'] != $input['property_id']) {
            Response::error('Tenant is not assigned to this property', 'VALIDATION_ERROR');
        }

        // Generate receipt number
        $receiptNumber = 'RCP' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);

        $paymentId = $this->paymentModel->create([
            'tenant_id' => $input['tenant_id'],
            'property_id' => $input['property_id'],
            'amount' => floatval($input['amount']),
            'payment_type' => $input['payment_type'],
            'payment_method' => $input['payment_method'],
            'transaction_reference' => $input['transaction_reference'],
            'payment_date' => $input['payment_date'],
            'due_date' => $input['due_date'] ?? $input['payment_date'],
            'status' => PAYMENT_PAID,
            'receipt_number' => $receiptNumber,
            'created_by' => AuthMiddleware::getUser()['sub'],
            'notes' => $input['notes'] ?? null
        ]);

        // Send receipt email
        $this->sendPaymentReceipt($paymentId);

        Logger::info('Payment recorded', [
            'payment_id' => $paymentId,
            'tenant_id' => $input['tenant_id'],
            'amount' => $input['amount']
        ]);

        Response::success(['payment_id' => $paymentId, 'receipt_number' => $receiptNumber], 'Payment recorded successfully');
    }

    public function generateReceipt($id) {
        AuthMiddleware::handle();
        $user = AuthMiddleware::getUser();

        $payment = $this->paymentModel->getPaymentWithDetails($id);
        if (!$payment) {
            Response::error('Payment not found', 'NOT_FOUND', [], 404);
        }

        // Check permissions
        if ($user['role'] === ROLE_TENANT) {
            $tenant = $this->tenantModel->getTenantByUserId($user['sub']);
            if (!$tenant || $payment['tenant_id'] != $tenant['id']) {
                Response::error('Access denied', 'FORBIDDEN', [], 403);
            }
        }

        // Generate PDF receipt (simplified - in production, use a PDF library)
        $receiptData = [
            'receipt_number' => $payment['receipt_number'],
            'payment_date' => $payment['payment_date'],
            'tenant_name' => $payment['tenant_name'],
            'property_name' => $payment['property_name'],
            'amount' => $payment['amount'],
            'payment_method' => $payment['payment_method'],
            'transaction_reference' => $payment['transaction_reference']
        ];

        Response::success(['receipt' => $receiptData]);
    }

    private function sendPaymentReceipt($paymentId) {
        $payment = $this->paymentModel->getPaymentWithDetails($paymentId);
        if ($payment && $payment['tenant_email']) {
            $this->emailService->sendPaymentReceipt(
                $payment['tenant_email'],
                $payment['tenant_name'],
                $payment
            );
        }
    }
}