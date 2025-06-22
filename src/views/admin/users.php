<?php
ob_start();
?>

<h1 class="text-3xl font-bold text-gray-800 mb-6">Manage Users</h1>
<!-- Create User Form -->
<div class="bg-white p-6 rounded-lg shadow-md mb-6">
    <h2 class="text-xl font-semibold text-gray-700 mb-4">Add New User</h2>
    <form action="/admin/users/create" method="POST" class="space-y-4">
        <div>
            <label class="block text-gray-600">Username</label>
            <input type="text" name="username" required class="w-full p-2 border rounded">
        </div>
        <div>
            <label class="block text-gray-600">Password</label>
            <input type="password" name="password" required class="w-full p-2 border rounded">
        </div>
        <div>
            <label class="block text-gray-600">First Name</label>
            <input type="text" name="first_name" required class="w-full p-2 border rounded">
        </div>
        <div>
            <label class="block text-gray-600">Last Name</label>
            <input type="text" name="last_name" required class="w-full p-2 border rounded">
        </div>
        <div>
            <label class="block text-gray-600">Role</label>
            <select name="role_id" required class="w-full p-2 border rounded">
                <?php foreach ($roles as $role): ?>
                    <option value="<?php echo htmlspecialchars($role['role_id'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($role['role_name'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-gray-600">College</label>
            <select name="college_id" class="w-full p-2 border rounded">
                <option value="">None</option>
                <?php foreach ($colleges as $college): ?>
                    <option value="<?php echo htmlspecialchars($college['college_id'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($college['college_name'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-gray-600">Department</label>
            <select name="department_id" class="w-full p-2 border rounded">
                <option value="">None</option>
                <?php foreach ($departments as $department): ?>
                    <option value="<?php echo htmlspecialchars($department['department_id'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($department['department_name'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Create User</button>
    </form>
</div>
<!-- Users Table -->
<div class="bg-white p-6 rounded-lg shadow-md">
    <h2 class="text-xl font-semibold text-gray-700 mb-4">Users List</h2>
    <table class="w-full border-collapse">
        <thead>
            <tr class="bg-gray-200">
                <th class="p-2 text-left">Username</th>
                <th class="p-2 text-left">Name</th>
                <th class="p-2 text-left">Role</th>
                <th class="p-2 text-left">College</th>
                <th class="p-2 text-left">Department</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr class="border-t">
                    <td class="p-2"><?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="p-2"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="p-2"><?php echo htmlspecialchars($user['role_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="p-2"><?php echo htmlspecialchars($user['college_name'] ?? 'None', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td class="p-2"><?php echo htmlspecialchars($user['department_name'] ?? 'None', ENT_QUOTES, 'UTF-8'); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/layout.php';
?>