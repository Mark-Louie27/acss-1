<?php
ob_start();

// Fetch current college details
$collegeId = $controller->getDeanCollegeId($_SESSION['user_id']);
$query = "SELECT college_name, logo_path FROM colleges WHERE college_id = :college_id";
$stmt = $controller->db->prepare($query);
$stmt->execute([':college_id' => $collegeId]);
$college = $stmt->fetch(PDO::FETCH_ASSOC);

// Check for success/error messages from DeanController
$success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : null;
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : null;
?>

<?php if ($success): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toast = document.createElement('div');
            toast.className = 'toast bg-green-500 text-white px-4 py-2 rounded-lg';
            toast.textContent = '<?php echo $success; ?>';
            document.getElementById('toast-container').appendChild(toast);
            setTimeout(() => toast.remove(), 5000);
        });
    </script>
<?php endif; ?>
<?php if ($error): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toast = document.createElement('div');
            toast.className = 'toast bg-red-500 text-white px-4 py-2 rounded-lg';
            toast.textContent = '<?php echo $error; ?>';
            document.getElementById('toast-container').appendChild(toast);
            setTimeout(() => toast.remove(), 5000);
        });
    </script>
<?php endif; ?>

<h2 class="text-3xl font-bold text-gray-600 mb-6 slide-in-left">Settings</h2>

<!-- Settings Form -->
<div class="bg-white p-6 rounded-lg shadow-md card">
    <h3 class="text-xl font-semibold text-gray-600 mb-4">Update College Settings</h3>
    <form action="/dean/settings" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="md:col-span-2">
            <label for="college_name" class="block text-sm font-medium text-gray-600">College Name</label>
            <input type="text" id="college_name" name="college_name" value="<?php echo htmlspecialchars($college['college_name'] ?? ''); ?>" required maxlength="100" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-gold-400 focus:ring focus:ring-gold-400 focus:ring-opacity-50" placeholder="e.g., College of Engineering">
        </div>
        <div class="md:col-span-2">
            <label for="college_logo" class="block text-sm font-medium text-gray-600">College Logo</label>
            <input type="file" id="college_logo" name="college_logo" accept="image/png,image/jpeg,image/gif" class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-gold-400 file:text-white hover:file:bg-gold-500">
            <p class="mt-1 text-xs text-gray-500">Accepted formats: PNG, JPEG, GIF. Max size: 2MB.</p>
        </div>
        <div class="md:col-span-2">
            <button type="submit" name="update_settings" class="bg-gold-400 text-white px-4 py-2 rounded hover:bg-gold-500 btn">Update Settings</button>
        </div>
    </form>
</div>

<!-- Current Logo Preview -->
<?php if ($college['logo_path']): ?>
    <div class="bg-white p-6 rounded-lg shadow-md card mt-8">
        <h3 class="text-xl font-semibold text-gray-600 mb-4">Current College Logo</h3>
        <img src="<?php echo htmlspecialchars($college['logo_path']); ?>" alt="College Logo" class="h-32 w-auto object-contain border-2 border-gold-400 rounded-lg">
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>