<?php
namespace App\Controllers;

use App\Models\Report;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Utils\Response;
use App\Utils\Logger;

class ReportsController {
    private $reportModel;

    public function __construct() {
        $this->reportModel = new Report();
    }

    /**
     * Get revenue report
     */
    public function revenue() {
        RoleMiddleware::adminOnly();
        
        $queryParams = $_GET;
        
        // Validate date parameters
        $startDate = $queryParams['start_date'] ?? date('Y-m-01'); // First day of current month
        $endDate = $queryParams['end_date'] ?? date('Y-m-t'); // Last day of current month
        $groupBy = $queryParams['group_by'] ?? 'month'; // day, week, month, year

        // Validate dates
        if (!$this->isValidDate($startDate) || !$this->isValidDate($endDate)) {
            Response::error('Invalid date format. Use YYYY-MM-DD', 'VALIDATION_ERROR');
            return;
        }

        if (strtotime($startDate) > strtotime($endDate)) {
            Response::error('Start date must be before end date', 'VALIDATION_ERROR');
            return;
        }

        // Validate groupBy
        $allowedGroupBy = ['day', 'week', 'month', 'year'];
        if (!in_array($groupBy, $allowedGroupBy)) {
            Response::error('Invalid group_by parameter. Use: day, week, month, or year', 'VALIDATION_ERROR');
            return;
        }

        try {
            $reportData = $this->reportModel->getRevenueReport($startDate, $endDate, $groupBy);
            $summary = $this->reportModel->getRevenueSummary($startDate, $endDate);

            Response::success([
                'summary' => $summary,
                'detailed_data' => $reportData,
                'filters' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'group_by' => $groupBy
                ]
            ]);
        } catch (\Exception $e) {
            Logger::error('Revenue report generation failed', ['error' => $e->getMessage()]);
            Response::error('Failed to generate revenue report', 'SERVER_ERROR', [], 500);
        }
    }

    /**
     * Get occupancy report
     */
    public function occupancy() {
        RoleMiddleware::adminOnly();
        
        $queryParams = $_GET;
        $startDate = $queryParams['start_date'] ?? null;
        $endDate = $queryParams['end_date'] ?? null;

        // Validate dates if provided
        if ($startDate && !$this->isValidDate($startDate)) {
            Response::error('Invalid start_date format. Use YYYY-MM-DD', 'VALIDATION_ERROR');
            return;
        }

        if ($endDate && !$this->isValidDate($endDate)) {
            Response::error('Invalid end_date format. Use YYYY-MM-DD', 'VALIDATION_ERROR');
            return;
        }

        if ($startDate && $endDate && strtotime($startDate) > strtotime($endDate)) {
            Response::error('Start date must be before end date', 'VALIDATION_ERROR');
            return;
        }

        try {
            $reportData = $this->reportModel->getOccupancyReport($startDate, $endDate);
            $summary = $this->reportModel->getOccupancySummary();

            Response::success([
                'summary' => $summary,
                'properties' => $reportData,
                'filters' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ]
            ]);
        } catch (\Exception $e) {
            Logger::error('Occupancy report generation failed', ['error' => $e->getMessage()]);
            Response::error('Failed to generate occupancy report', 'SERVER_ERROR', [], 500);
        }
    }

    /**
     * Get maintenance report
     */
    public function maintenance() {
        RoleMiddleware::adminOnly();
        
        $queryParams = $_GET;
        
        $startDate = $queryParams['start_date'] ?? date('Y-m-01');
        $endDate = $queryParams['end_date'] ?? date('Y-m-t');
        $status = $queryParams['status'] ?? null;

        // Validate dates
        if (!$this->isValidDate($startDate) || !$this->isValidDate($endDate)) {
            Response::error('Invalid date format. Use YYYY-MM-DD', 'VALIDATION_ERROR');
            return;
        }

        if (strtotime($startDate) > strtotime($endDate)) {
            Response::error('Start date must be before end date', 'VALIDATION_ERROR');
            return;
        }

        // Validate status if provided
        $allowedStatuses = ['pending', 'in_progress', 'completed', 'cancelled'];
        if ($status && !in_array($status, $allowedStatuses)) {
            Response::error('Invalid status. Use: pending, in_progress, completed, or cancelled', 'VALIDATION_ERROR');
            return;
        }

        try {
            $reportData = $this->reportModel->getMaintenanceReport($startDate, $endDate, $status);
            $summary = $this->reportModel->getMaintenanceSummary($startDate, $endDate);

            Response::success([
                'summary' => $summary,
                'requests' => $reportData,
                'filters' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'status' => $status
                ]
            ]);
        } catch (\Exception $e) {
            Logger::error('Maintenance report generation failed', ['error' => $e->getMessage()]);
            Response::error('Failed to generate maintenance report', 'SERVER_ERROR', [], 500);
        }
    }

    /**
     * Export report data
     */
    public function export() {
        RoleMiddleware::adminOnly();
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Response::error('Invalid JSON data', 'VALIDATION_ERROR');
            return;
        }

        $reportType = $input['report_type'] ?? null;
        $format = $input['format'] ?? 'csv'; // csv, json, pdf
        $startDate = $input['start_date'] ?? date('Y-m-01');
        $endDate = $input['end_date'] ?? date('Y-m-t');

        // Validate report type
        $allowedReportTypes = ['revenue', 'occupancy', 'maintenance', 'property_performance'];
        if (!in_array($reportType, $allowedReportTypes)) {
            Response::error('Invalid report_type. Use: revenue, occupancy, maintenance, or property_performance', 'VALIDATION_ERROR');
            return;
        }

        // Validate format
        $allowedFormats = ['csv', 'json'];
        if (!in_array($format, $allowedFormats)) {
            Response::error('Invalid format. Use: csv or json', 'VALIDATION_ERROR');
            return;
        }

        // Validate dates
        if (!$this->isValidDate($startDate) || !$this->isValidDate($endDate)) {
            Response::error('Invalid date format. Use YYYY-MM-DD', 'VALIDATION_ERROR');
            return;
        }

        try {
            $data = $this->getReportData($reportType, $startDate, $endDate);
            
            if ($format === 'csv') {
                $this->exportCSV($data, $reportType);
            } else {
                $this->exportJSON($data, $reportType);
            }
        } catch (\Exception $e) {
            Logger::error('Report export failed', [
                'report_type' => $reportType,
                'error' => $e->getMessage()
            ]);
            Response::error('Failed to export report', 'SERVER_ERROR', [], 500);
        }
    }

    /**
     * Get report data based on type
     */
    private function getReportData($reportType, $startDate, $endDate) {
        return match($reportType) {
            'revenue' => $this->reportModel->getRevenueReport($startDate, $endDate),
            'occupancy' => $this->reportModel->getOccupancyReport($startDate, $endDate),
            'maintenance' => $this->reportModel->getMaintenanceReport($startDate, $endDate),
            'property_performance' => $this->reportModel->getPropertyPerformance($startDate, $endDate),
            default => []
        };
    }

    /**
     * Export data as CSV
     */
    private function exportCSV($data, $reportType) {
        if (empty($data)) {
            Response::error('No data available for export', 'NOT_FOUND', [], 404);
            return;
        }

        $filename = "{$reportType}_report_" . date('Y-m-d_His') . ".csv";
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        $output = fopen('php://output', 'w');
        
        // Write headers
        fputcsv($output, array_keys($data[0]));
        
        // Write data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }

    /**
     * Export data as JSON
     */
    private function exportJSON($data, $reportType) {
        $filename = "{$reportType}_report_" . date('Y-m-d_His') . ".json";
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');

        echo json_encode([
            'report_type' => $reportType,
            'generated_at' => date('Y-m-d H:i:s'),
            'data' => $data
        ], JSON_PRETTY_PRINT);
        
        exit;
    }

    /**
     * Validate date format
     */
    private function isValidDate($date) {
        $d = \DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
}