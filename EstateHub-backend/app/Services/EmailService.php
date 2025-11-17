<?php
namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use app\Utils\Logger;

class EmailService {
    private $mailer;

    public function __construct() {
        $this->mailer = new PHPMailer(true);
        $this->configure();
    }

    private function configure() {
        try {
            // Server settings
            $this->mailer->isSMTP();
            $this->mailer->Host = $_ENV['MAIL_HOST'] ?? 'smtp.mailtrap.io';
            $this->mailer->Port = $_ENV['MAIL_PORT'] ?? 2525;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $_ENV['MAIL_USERNAME'] ?? '';
            $this->mailer->Password = $_ENV['MAIL_PASSWORD'] ?? '';
            
            // Recipient and sender
            $this->mailer->setFrom(
                $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@estatehub.com',
                $_ENV['MAIL_FROM_NAME'] ?? 'EstateHub'
            );
            
            $this->mailer->isHTML(true);
        } catch (Exception $e) {
            Logger::error('Email configuration failed', ['error' => $e->getMessage()]);
        }
    }

    public function sendWelcomeEmail($toEmail, $toName) {
        $subject = 'Welcome to EstateHub!';
        $body = $this->getWelcomeTemplate($toName);
        
        return $this->send($toEmail, $toName, $subject, $body);
    }

    public function sendPasswordReset($toEmail, $toName, $resetToken) {
        $subject = 'Password Reset Request - EstateHub';
        $resetUrl = $_ENV['App_URL'] . "/reset-password?token={$resetToken}";
        $body = $this->getPasswordResetTemplate($toName, $resetUrl);
        
        return $this->send($toEmail, $toName, $subject, $body);
    }

    public function sendPaymentReceipt($toEmail, $toName, $paymentDetails) {
        $subject = 'Payment Receipt - EstateHub';
        $body = $this->getPaymentReceiptTemplate($toName, $paymentDetails);
        
        return $this->send($toEmail, $toName, $subject, $body);
    }

    private function send($toEmail, $toName, $subject, $body) {
        try {
            $this->mailer->addAddress($toEmail, $toName);
            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags($body);

            $this->mailer->send();
            Logger::info('Email sent successfully', ['to' => $toEmail, 'subject' => $subject]);
            return true;
        } catch (Exception $e) {
            Logger::error('Email sending failed', [
                'to' => $toEmail,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function getWelcomeTemplate($name) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #4F46E5; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Welcome to EstateHub!</h1>
                </div>
                <div class='content'>
                    <p>Hello {$name},</p>
                    <p>Thank you for joining EstateHub! Your account has been successfully created.</p>
                    <p>You can now access your dashboard and manage your properties or tenancy.</p>
                    <p>If you have any questions, please contact our support team.</p>
                </div>
                <div class='footer'>
                    <p>&copy; 2025 EstateHub. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    private function getPasswordResetTemplate($name, $resetUrl) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #DC2626; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .button { display: inline-block; padding: 12px 24px; background: #4F46E5; color: white; text-decoration: none; border-radius: 4px; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Password Reset</h1>
                </div>
                <div class='content'>
                    <p>Hello {$name},</p>
                    <p>You requested to reset your password. Click the button below to create a new password:</p>
                    <p style='text-align: center;'>
                        <a href='{$resetUrl}' class='button'>Reset Password</a>
                    </p>
                    <p>This link will expire in 1 hour. If you didn't request this reset, please ignore this email.</p>
                </div>
                <div class='footer'>
                    <p>&copy; 2025 EstateHub. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    private function getPaymentReceiptTemplate($name, $paymentDetails) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #059669; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .receipt { background: white; padding: 20px; border-radius: 4px; border: 1px solid #ddd; }
                .footer { text-align: center; padding: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Payment Receipt</h1>
                </div>
                <div class='content'>
                    <p>Hello {$name},</p>
                    <p>Thank you for your payment. Here's your receipt:</p>
                    
                    <div class='receipt'>
                        <h3>Payment Details</h3>
                        <p><strong>Amount:</strong> KES {$paymentDetails['amount']}</p>
                        <p><strong>Payment Date:</strong> {$paymentDetails['payment_date']}</p>
                        <p><strong>Receipt Number:</strong> {$paymentDetails['receipt_number']}</p>
                        <p><strong>Property:</strong> {$paymentDetails['property_name']}</p>
                        <p><strong>Payment Method:</strong> {$paymentDetails['payment_method']}</p>
                    </div>
                    
                    <p>This receipt confirms your payment has been processed successfully.</p>
                </div>
                <div class='footer'>
                    <p>&copy; 2025 EstateHub. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}