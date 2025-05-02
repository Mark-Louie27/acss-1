<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Company Portal</title>
    <link rel="stylesheet" href="/css/output.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        gold: {
                            50: '#FEF9E8',
                            100: '#FDF0C4',
                            200: '#FAE190',
                            300: '#F7D15C',
                            400: '#F4C029',
                            500: '#E5AD0F',
                            600: '#B98A0C',
                            700: '#8E6809',
                            800: '#624605',
                            900: '#352503',
                        },
                    }
                }
            }
        }
    </script>
</head>

<body class="min-h-screen bg-gray-100 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <!-- Card -->
        <div class="bg-white rounded-xl shadow-xl overflow-hidden">
            <!-- Logo Area -->
            <div class="bg-gradient-to-r from-gray-800 to-gray-900 p-6 flex flex-col items-center">
                <div class="rounded-full bg-white/10 p-3 mb-3">
                    <svg class="w-8 h-8 text-gold-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-white">Welcome Back</h1>
                <p class="text-gray-300 text-sm">Sign in to your account</p>
            </div>

            <!-- Form Area -->
            <div class="p-6">
                <?php if (isset($error)): ?>
                    <div class="bg-red-50 text-red-700 p-3 rounded-lg mb-4 text-sm">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['success'])): ?>
                    <div class="bg-green-50 text-green-700 p-3 rounded-lg mb-4 text-sm">
                        <?php echo htmlspecialchars($_GET['success']); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/login" class="space-y-5">
                    <div>
                        <label for="employee_id" class="block text-sm font-medium text-gray-700 mb-1">Employee ID</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <input type="text" id="employee_id" name="employee_id" required
                                class="pl-10 w-full rounded-lg border border-gray-300 focus:ring-2 focus:ring-gold-300 focus:border-gold-500 py-2.5 transition duration-200 outline-none"
                                placeholder="Enter your employee ID">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input type="password" id="password" name="password" required
                                class="pl-10 w-full rounded-lg border border-gray-300 focus:ring-2 focus:ring-gold-300 focus:border-gold-500 py-2.5 transition duration-200 outline-none"
                                placeholder="Enter your password">
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input id="remember-me" name="remember-me" type="checkbox"
                                class="h-4 w-4 text-gold-500 focus:ring-gold-400 border-gray-300 rounded">
                            <label for="remember-me" class="ml-2 block text-sm text-gray-600">
                                Remember me
                            </label>
                        </div>
                        <a href="#" class="text-sm font-medium text-gold-600 hover:text-gold-700">Forgot password?</a>
                    </div>

                    <button type="submit"
                        class="w-full bg-gradient-to-r from-gold-400 to-gold-500 text-white font-medium py-2.5 px-4 rounded-lg shadow hover:from-gold-500 hover:to-gold-600 focus:outline-none focus:ring-2 focus:ring-gold-300 transform transition duration-200 hover:scale-[1.02]">
                        Sign In
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600">
                        Don't have an account?
                        <a href="/register" class="font-medium text-gold-600 hover:text-gold-700">Register here</a>
                    </p>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <p class="mt-6 text-center text-xs text-gray-500">
            &copy; 2025 PRMSU Iba Campus. All rights reserved.
        </p>
    </div>
</body>

</html>