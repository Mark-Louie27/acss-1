<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | PRMSU Main Campus Portal</title>
    <meta name="description" content="Login to the President Ramon Magsaysay State University Main Campus online portal.">
    <link href="/css/output.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-image: url('/assets/logo/main_logo/campus.jpg'); /* Replace with your campus image */
            /* Replace with your campus image */
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            font-family: 'Inter', sans-serif;
        }

        .gold-gradient {
            background: linear-gradient(135deg, #F5A800 0%, #FFD055 100%);
        }

        .login-card {
            backdrop-filter: blur(10px);
            background-color: rgba(255, 255, 255, 0.85);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 1rem;
            overflow: hidden;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .campus-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(to bottom right, rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.3));
            z-index: -1;
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 0.75rem 0.75rem 3rem;
            border: 1px solid #e5e5e5;
            border-radius: 0.5rem;
            outline: none;
            transition: all 0.2s;
        }

        .form-input:focus {
            border-color: #F5A800;
            box-shadow: 0 0 0 3px rgba(245, 168, 0, 0.15);
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #F5A800;
            width: 1.25rem;
            height: 1.25rem;
        }

        .submit-button {
            width: 100%;
            background: linear-gradient(135deg, #F5A800 0%, #FFD055 100%);
            color: white;
            font-weight: 500;
            padding: 0.75rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .submit-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .submit-button::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 200%;
            height: 100%;
            background: linear-gradient(90deg,
                    rgba(255, 255, 255, 0) 0%,
                    rgba(255, 255, 255, 0.2) 50%,
                    rgba(255, 255, 255, 0) 100%);
            animation: shimmer 2.5s infinite;
        }

        @keyframes shimmer {
            0% {
                background-position: -100% 0;
            }

            100% {
                background-position: 200% 0;
            }
        }

        .logo-container {
            width: 4rem;
            height: 4rem;
            border-radius: 50%;
            background: linear-gradient(135deg, #F5A800 0%, #FFD055 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.25rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .divider {
            width: 3rem;
            height: 0.25rem;
            background: linear-gradient(135deg, #F5A800 0%, #FFD055 100%);
            border-radius: 1rem;
            margin: 0.75rem auto 0.5rem;
        }

        .footer-dot {
            color: white;
            margin: 0 0.25rem;
        }

        .footer-text {
            color: white;
            font-size: 0.75rem;
            font-weight: 500;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
            text-align: center;
        }

        .checkbox-custom {
            width: 1rem;
            height: 1rem;
            accent-color: #F5A800;
        }

        .link-gold {
            color: #F5A800;
            font-weight: 500;
            text-decoration: none;
            transition: color 0.2s;
        }

        .link-gold:hover {
            color: #D68E00;
        }
    </style>
</head>

<body style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1rem; position: relative;">
    <!-- Campus Background Overlay -->
    <div class="campus-overlay"></div>

    <div style="width: 100%; max-width: 28rem;">
        <!-- Card -->
        <div class="login-card">
            <!-- Header with Logo -->
            <div style="padding: 2rem 2rem 1.5rem; display: flex; flex-direction: column; align-items: center;">
                <div class="logo-container">
                    <img src="/assets/logo/main_logo/PRMSUlogo.png" alt="PRMSU Logo" style="width: 2rem; height: 2rem; border-radius: 50%;">
                </div>
                <h1 style="font-size: 1.5rem; font-weight: 700; color: #262626; letter-spacing: -0.025em;">Welcome to PRMSU</h1>
                <div class="divider"></div>
                <p style="color: #737373; font-size: 0.875rem;">Sign in to access your campus portal</p>
            </div>

            <!-- Form Area -->
            <div style="padding: 0 2rem 2rem;">
                <!-- Alert Messages (hidden by default) -->
                <div style="background-color: #FFFAEB; border-left: 4px solid #F5A800; color: #7C5100; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem; display: none;">
                    <div style="display: flex;">
                        <div style="flex-shrink: 0;">
                            <svg style="height: 1.25rem; width: 1.25rem; color: #F5A800;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div style="margin-left: 0.75rem;">
                            <p style="font-size: 0.875rem;">Session timeout. Please sign in again.</p>
                        </div>
                    </div>
                </div>

                <form method="POST" action="/login" style="display: flex; flex-direction: column; gap: 1.25rem;">
                    <div>
                        <label for="employee_id" style="display: block; font-size: 0.875rem; font-weight: 500; color: #404040; margin-bottom: 0.5rem;">Employee ID</label>
                        <div style="position: relative;">
                            <div class="input-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" />
                                </svg>
                            </div>
                            <input type="text" id="employee_id" name="employee_id" required
                                class="form-input"
                                placeholder="Enter your employee ID">
                        </div>
                    </div>

                    <div>
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 0.5rem;">
                            <label for="password" style="font-size: 0.875rem; font-weight: 500; color: #404040;">Password</label>
                        </div>
                        <div style="position: relative;">
                            <div class="input-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input type="password" id="password" name="password" required
                                class="form-input"
                                placeholder="Enter your password">
                        </div>
                    </div>

                    <div style="display: flex; align-items: center; justify-content: space-between; padding-top: 0.25rem;">
                        <div style="display: flex; align-items: center;">
                            <input id="remember-me" name="remember-me" type="checkbox" class="checkbox-custom">
                            <label for="remember-me" style="margin-left: 0.5rem; font-size: 0.875rem; color: #525252;">
                                Remember me
                            </label>
                        </div>
                        <a href="#" class="link-gold" style="font-size: 0.875rem;">Forgot password?</a>
                    </div>

                    <button type="submit" class="submit-button" style="margin-top: 1.5rem;">
                        Sign In
                    </button>
                </form>

                <div style="margin-top: 2rem; text-align: center;">
                    <p style="font-size: 0.875rem; color: #525252;">
                        Need assistance with your account?
                        <a href="/help" class="link-gold">Get Help</a>
                    </p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div style="margin-top: 1.5rem; display: flex; flex-direction: column; align-items: center;">
            <div style="display: flex; margin-bottom: 0.5rem;">
                <span class="footer-dot">•</span>
                <span class="footer-dot">•</span>
                <span class="footer-dot">•</span>
            </div>
            <p class="footer-text">
                &copy; 2025 President Ramon Magsaysay State University - Main Campus. All rights reserved.
            </p>
        </div>
    </div>
</body>

</html>