<?php
// setup_users.php - One-time script to create the users table and seed admin account
// DELETE this file after running it!
require_once 'db.php';

$messages = [];

try {
    $db = DB::getConnection();

    // Create users table
    $db->exec("
        CREATE TABLE IF NOT EXISTS `users` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `username` VARCHAR(50) NOT NULL UNIQUE,
          `password` VARCHAR(255) NOT NULL,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $messages[] = ['ok', 'users table created (or already exists).'];

    // Insert default admin (password: admin123)
    $hashed = password_hash('admin123', PASSWORD_BCRYPT);
    $stmt = $db->prepare("INSERT IGNORE INTO users (username, password) VALUES (?, ?)");
    $stmt->execute(['admin', $hashed]);

    if ($stmt->rowCount() > 0) {
        $messages[] = ['ok', 'Admin account created: username=<strong>admin</strong>, password=<strong>admin123</strong>'];
    } else {
        $messages[] = ['info', 'Admin account already exists. Skipped insert.'];
    }

} catch (Exception $e) {
    $messages[] = ['error', 'Error: ' . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>INZANITY Setup</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Outfit', sans-serif; } </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-white rounded-3xl shadow-lg p-8">
        <h1 class="text-xl font-bold text-gray-900 mb-2">INZANITY — Database Setup</h1>
        <p class="text-sm text-gray-400 mb-6">Users table setup results:</p>

        <?php foreach ($messages as [$type, $msg]): ?>
            <div class="mb-3 p-4 rounded-xl text-sm flex items-start gap-3
                <?= $type === 'ok' ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : '' ?>
                <?= $type === 'error' ? 'bg-rose-50 border border-rose-200 text-rose-800' : '' ?>
                <?= $type === 'info' ? 'bg-blue-50 border border-blue-200 text-blue-800' : '' ?>
            ">
                <?= $type === 'ok' ? '✅' : ($type === 'error' ? '❌' : 'ℹ️') ?>
                <span><?= $msg ?></span>
            </div>
        <?php endforeach; ?>

        <div class="mt-6 p-4 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-800">
            ⚠️ <strong>Security:</strong> Please delete this file (<code>setup_users.php</code>) after running it.
        </div>

        <div class="mt-6 flex gap-3">
            <a href="admin/login.php" class="flex-1 text-center bg-violet-600 hover:bg-violet-500 text-white font-semibold py-3 rounded-xl text-sm transition-all">
                Go to Admin Login
            </a>
            <a href="index.php" class="flex-1 text-center bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-3 rounded-xl text-sm transition-all">
                Go to Site
            </a>
        </div>
    </div>
</body>
</html>
