<?php
ob_start();
?>

<!DOCTYPE html>
<html lang="en" class="dark bg-gray-900 text-white">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Program Chair</title>
    <link rel="stylesheet" href="/css/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>

<body class=" min-h-screen">
    <div class="container mx-auto p-6">
        <h1 class="text-3xl font-bold mb-6 text-yellow-400">Settings</h1>

        <?php
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            echo '<div class="mb-4 p-4 rounded ' . ($flash['type'] === 'success' ? 'bg-yellow-500' : 'bg-red-700') . ' text-white">' . htmlspecialchars($flash['message']) . '</div>';
        }
        ?>

        <form method="POST" action="/chair/settings" class="bg-gray-800 p-6 rounded-lg shadow-lg">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

            <h2 class="text-2xl font-semibold mb-4 mt-6 text-white">Update Email</h2>
            <div class="mb-4">
                <label for="new_email" class="block text-sm font-medium mb-2 text-yellow-300">New Email</label>
                <input type="email" id="new_email" name="new_email" class="w-full p-2 rounded bg-gray-700 border border-gray-600 text-white focus:outline-none focus:ring-2 focus:ring-yellow-400" placeholder="Enter new email">
            </div>

            <h2 class="text-2xl font-semibold mb-4 mt-6 text-white">Change Password</h2>
            <div class="mb-4">
                <label for="current_password" class="block text-sm font-medium mb-2 text-yellow-300">Current Password</label>
                <input type="password" id="current_password" name="current_password" class="w-full p-2 rounded bg-gray-700 border border-gray-600 text-white focus:outline-none focus:ring-2 focus:ring-yellow-400" placeholder="Enter current password">
            </div>
            <div class="mb-4">
                <label for="new_password" class="block text-sm font-medium mb-2 text-yellow-300">New Password</label>
                <input type="password" id="new_password" name="new_password" class="w-full p-2 rounded bg-gray-700 border border-gray-600 text-white focus:outline-none focus:ring-2 focus:ring-yellow-400" placeholder="Enter new password">
            </div>
            <div class="mb-6">
                <label for="confirm_password" class="block text-sm font-medium mb-2 text-yellow-300">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="w-full p-2 rounded bg-gray-700 border border-gray-600 text-white focus:outline-none focus:ring-2 focus:ring-yellow-400" placeholder="Confirm new password">
            </div>

            <button type="submit" class="w-full bg-yellow-500 hover:bg-yellow-600 text-gray-900 font-bold py-2 px-4 rounded focus:outline-none focus:ring-2 focus:ring-yellow-400 transition duration-300">Save Changes</button>
        </form>
    </div>
</body>

</html>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>