<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../models/UserModel.php';

class EmailService
{
    private $mailer;
    private $userModel;
    private $db;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->mailer->isSMTP();
        $this->mailer->Host = 'smtp.gmail.com';
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $_ENV['USERNAME']; // Replace with your Gmail address
        $this->mailer->Password = $_ENV['PASSWORD']; // Replace with Gmail App Password
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $this->mailer->Port = 465;

        $this->db;
        $this->userModel = new UserModel();
    }

    /**
     * Send account approval email
     * @param string $toEmail
     * @param string $name
     * @param string $role
     * @return bool
     */
    public function sendApprovalEmail($toEmail, $name, $role)
    {
        try {
            $this->mailer->setFrom('mlbausa84@gmail.com','ACSS System');
            $this->mailer->addAddress($toEmail, $name);
            $this->mailer->isHTML(true);
            $this->mailer->Subject = '✅ Welcome to ACSS - Your Account is Now Active';

            $this->mailer->Body = "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Account Approved</title>
        </head>
        <body style='margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, \"Helvetica Neue\", Arial, sans-serif; line-height: 1.6; color: #333333; background-color: #f8fafc;'>
            <!-- Email Container -->
            <div style='max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 16px; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1); overflow: hidden;'>
                
                <!-- Header -->
                <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 30px; text-align: center;'>
                    <div style='background-color: rgba(255, 255, 255, 0.2); width: 80px; height: 80px; border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center; border: 3px solid rgba(255, 255, 255, 0.3);'>
                        <span style='font-size: 36px; color: #ffffff;'>✅</span>
                    </div>
                    <h1 style='color: #ffffff; margin: 0; font-size: 28px; font-weight: 700; text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);'>Account Approved!</h1>
                    <p style='color: rgba(255, 255, 255, 0.9); margin: 10px 0 0 0; font-size: 16px; font-weight: 300;'>Welcome to the ACSS System</p>
                </div>
                
                <!-- Main Content -->
                <div style='padding: 40px 30px;'>
                    <div style='text-align: center; margin-bottom: 30px;'>
                        <h2 style='color: #2d3748; margin: 0 0 10px 0; font-size: 24px; font-weight: 600;'>Hello, $name! 👋</h2>
                        <p style='color: #718096; margin: 0; font-size: 16px;'>Great news! Your account has been successfully approved.</p>
                    </div>
                    
                    <!-- Approval Details Card -->
                    <div style='background-color: #f7fafc; border-left: 4px solid #38a169; padding: 20px 25px; margin: 25px 0; border-radius: 8px;'>
                        <div style='display: flex; align-items: center; margin-bottom: 15px;'>
                            <span style='background-color: #38a169; color: white; padding: 8px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;'>Approved</span>
                        </div>
                        <p style='margin: 0 0 10px 0; color: #4a5568; font-size: 16px;'>
                            <strong>Role:</strong> <span style='color: #2d3748; font-weight: 600;'>$role</span>
                        </p>
                        <p style='margin: 0; color: #4a5568; font-size: 14px;'>
                            <strong>Approved by:</strong> Dean's Office
                        </p>
                    </div>
                    
                    <!-- Call to Action -->
                    <div style='text-align: center; margin: 35px 0;'>
                        <a href='http://localhost:8000/login' 
                           style='display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; padding: 16px 32px; text-decoration: none; border-radius: 50px; font-weight: 600; font-size: 16px; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); transition: all 0.3s ease; border: none; cursor: pointer;'>
                            🚀 Access Your Account
                        </a>
                        <p style='margin: 15px 0 0 0; color: #a0aec0; font-size: 13px;'>
                            Or copy this link: <br>
                            <span style='background-color: #edf2f7; padding: 4px 8px; border-radius: 4px; font-family: monospace; font-size: 12px; color: #4a5568;'>http://localhost:8000/login</span>
                        </p>
                    </div>
                    
                    <!-- Features Section -->
                    <div style='background-color: #f7fafc; border-radius: 12px; padding: 25px; margin: 30px 0;'>
                        <h3 style='color: #2d3748; margin: 0 0 20px 0; font-size: 18px; font-weight: 600; text-align: center;'>What's Next?</h3>
                        <div style='display: grid; gap: 15px;'>
                            <div style='display: flex; align-items: center;'>
                                <span style='background-color: #e6fffa; color: #319795; width: 32px; height: 32px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-right: 15px; font-weight: bold; font-size: 14px;'>1</span>
                                <div>
                                    <p style='margin: 0; color: #2d3748; font-weight: 500;'>Complete your profile setup</p>
                                    <p style='margin: 0; color: #718096; font-size: 13px;'>Add your personal information and preferences</p>
                                </div>
                            </div>
                            <div style='display: flex; align-items: center;'>
                                <span style='background-color: #e6fffa; color: #319795; width: 32px; height: 32px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-right: 15px; font-weight: bold; font-size: 14px;'>2</span>
                                <div>
                                    <p style='margin: 0; color: #2d3748; font-weight: 500;'>Explore the dashboard</p>
                                    <p style='margin: 0; color: #718096; font-size: 13px;'>Familiarize yourself with the available features</p>
                                </div>
                            </div>
                            <div style='display: flex; align-items: center;'>
                                <span style='background-color: #e6fffa; color: #319795; width: 32px; height: 32px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-right: 15px; font-weight: bold; font-size: 14px;'>3</span>
                                <div>
                                    <p style='margin: 0; color: #2d3748; font-weight: 500;'>Start using ACSS features</p>
                                    <p style='margin: 0; color: #718096; font-size: 13px;'>Begin managing your academic activities</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Support Section -->
                    <div style='background-color: #fffaf0; border: 1px solid #fbd38d; border-radius: 8px; padding: 20px; margin: 25px 0; text-align: center;'>
                        <p style='margin: 0 0 10px 0; color: #744210; font-weight: 500;'>Need help getting started?</p>
                        <p style='margin: 0; color: #975a16; font-size: 14px;'>
                            Contact our support team at <a href='mailto:support@acss.com' style='color: #c05621; text-decoration: none; font-weight: 500;'>support@acss.com</a>
                            <br>or visit our <a href='#' style='color: #c05621; text-decoration: none; font-weight: 500;'>Help Center</a>
                        </p>
                    </div>
                </div>
                
                <!-- Footer -->
                <div style='background-color: #2d3748; padding: 30px; text-align: center;'>
                    <div style='margin-bottom: 20px;'>
                        <h3 style='color: #ffffff; margin: 0; font-size: 20px; font-weight: 700;'>ACSS System</h3>
                        <p style='color: #a0aec0; margin: 5px 0 0 0; font-size: 14px;'>Academic Coordination & Support System</p>
                    </div>
                    <div style='border-top: 1px solid #4a5568; padding-top: 20px;'>
                        <p style='color: #a0aec0; margin: 0; font-size: 12px;'>
                            This email was sent to $toEmail<br>
                            © 2024 ACSS System. All rights reserved.
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Mobile Responsiveness -->
            <style>
                @media only screen and (max-width: 600px) {
                    .email-container {
                        margin: 20px auto !important;
                        border-radius: 8px !important;
                    }
                    .email-content {
                        padding: 25px 20px !important;
                    }
                    .cta-button {
                        padding: 14px 24px !important;
                        font-size: 15px !important;
                    }
                }
            </style>
        </body>
        </html>";

            $this->mailer->AltBody = "
            🎉 ACCOUNT APPROVED - Welcome to ACSS System!

            Hello $name,

            Great news! Your account for the role of $role has been successfully approved by the Dean's office.

            🚀 GET STARTED:
            You can now access your account at: http://your-domain.com/login

            📋 WHAT'S NEXT:
            1. Complete your profile setup
            2. Explore the dashboard features  
            3. Start using ACSS system tools

            💡 NEED HELP?
            Contact support: support@acss.com
            Visit our Help Center for guides and tutorials

            Thank you for joining ACSS System!

            Best regards,
            The ACSS Team

            ---
            This email was sent to $toEmail
            © 2024 ACSS System. All rights reserved.
        ";

            $this->mailer->send();
            return true;
        } catch (Exception $e) {
            error_log("Error sending approval email to $toEmail: " . $this->mailer->ErrorInfo);
            return false;
        }
    }

    public function sendVerificationEmail($userId, $token, $newPassword)
    {
        $stmt = $this->db->prepare("SELECT email FROM users WHERE user_id = :user_id");
        $stmt->execute([':user_id' => $userId]);
        $email = $stmt->fetchColumn();

        $verificationLink = "http://yourdomain.com/chair/verify-password?token={$token}&user_id={$userId}";
        $subject = "Verify Your Password Change";
        $message = "Click the link to verify your password change: {$verificationLink}\n\nNew Password: {$newPassword}\nThis link expires in 1 hour.";
        $headers = "From: no-reply@yourdomain.com";

        mail($email, $subject, $message, $headers);
    }
}
