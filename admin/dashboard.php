<?php
// admin/dashboard.php - Admin dashboard for managing tickets, approvals, and status filters
session_start();

// Guard: must be logged in
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

require_once '../db.php';

// Handle Edit Credentials
$credSuccess = '';
$credError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_credentials') {
    $newUsername = trim($_POST['new_username'] ?? '');
    $newPassword = trim($_POST['new_password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');

    if (empty($newUsername)) {
        $credError = 'Username cannot be empty.';
    } elseif (!empty($newPassword) && $newPassword !== $confirmPassword) {
        $credError = 'Passwords do not match.';
    } else {
        try {
            $db = DB::getConnection();
            if (!empty($newPassword)) {
                $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
                $upStmt = $db->prepare("UPDATE users SET username = ?, password = ? WHERE id = ?");
                $upStmt->execute([$newUsername, $hashed, $_SESSION['admin_id']]);
            } else {
                $upStmt = $db->prepare("UPDATE users SET username = ? WHERE id = ?");
                $upStmt->execute([$newUsername, $_SESSION['admin_id']]);
            }
            $_SESSION['admin_username'] = $newUsername;
            $credSuccess = 'Credentials updated successfully!';
        } catch (Exception $e) {
            $credError = 'Failed to update credentials: ' . $e->getMessage();
        }
    }
}

// Handle Action POST Requests (Approve, Deapprove, Reject, Delete)
$action = $_POST['action'] ?? '';
$bookingId = intval($_POST['booking_id'] ?? 0);
$actionError = '';
$actionSuccess = '';

if (!empty($action) && $bookingId > 0) {
    try {
        $db = DB::getConnection();
        
        // Fetch current booking details
        $bStmt = $db->prepare("SELECT * FROM bookings WHERE id = ?");
        $bStmt->execute([$bookingId]);
        $booking = $bStmt->fetch();

        if ($booking) {
            $seats = intval($booking['seats']);
            $pkgCode = $booking['package_code'];
            $currentStatus = $booking['status'];

            if ($action === 'approve') {
                if ($currentStatus !== 'approved') {
                    // Update status to approved
                    $upStmt = $db->prepare("UPDATE bookings SET status = 'approved' WHERE id = ?");
                    $upStmt->execute([$bookingId]);
                    
                    // Decrement available seats in package
                    $decStmt = $db->prepare("UPDATE packages SET available_seats = available_seats - ? WHERE code = ?");
                    $decStmt->execute([$seats, $pkgCode]);
                    
                    $actionSuccess = "Booking {$booking['booking_ref']} approved and seats registered!";
                }
            } 
            elseif ($action === 'deapprove') {
                if ($currentStatus === 'approved') {
                    // Set back to pending
                    $upStmt = $db->prepare("UPDATE bookings SET status = 'pending' WHERE id = ?");
                    $upStmt->execute([$bookingId]);
                    
                    // Increment available seats back
                    $incStmt = $db->prepare("UPDATE packages SET available_seats = available_seats + ? WHERE code = ?");
                    $incStmt->execute([$seats, $pkgCode]);
                    
                    $actionSuccess = "Booking {$booking['booking_ref']} deapproved. Status set to Pending.";
                }
            } 
            elseif ($action === 'reject') {
                if ($currentStatus === 'approved') {
                    // Increment seats first if they were previously approved
                    $incStmt = $db->prepare("UPDATE packages SET available_seats = available_seats + ? WHERE code = ?");
                    $incStmt->execute([$seats, $pkgCode]);
                }
                
                $upStmt = $db->prepare("UPDATE bookings SET status = 'rejected' WHERE id = ?");
                $upStmt->execute([$bookingId]);
                $actionSuccess = "Booking {$booking['booking_ref']} rejected.";
            } 
            elseif ($action === 'delete') {
                if ($currentStatus === 'approved') {
                    // Increment seats back
                    $incStmt = $db->prepare("UPDATE packages SET available_seats = available_seats + ? WHERE code = ?");
                    $incStmt->execute([$seats, $pkgCode]);
                }
                
                // Delete image file
                if (file_exists('../' . $booking['receipt_path'])) {
                    @unlink('../' . $booking['receipt_path']);
                }
                
                $delStmt = $db->prepare("DELETE FROM bookings WHERE id = ?");
                $delStmt->execute([$bookingId]);
                $actionSuccess = "Booking reference record deleted successfully.";
            }
        } else {
            $actionError = "Booking record not found.";
        }
    } catch (Exception $e) {
        $actionError = "Action failed: " . $e->getMessage();
    }
}

// 2. Fetch Packages Statistics (VIP, VVIP, General counts)
$stats = [
    'VV' => ['total' => 0, 'approved' => 0],
    'VP' => ['total' => 0, 'approved' => 0],
    'GN' => ['total' => 0, 'approved' => 0]
];

try {
    $db = DB::getConnection();
    // Query stats per package
    $statQuery = $db->query("
        SELECT package_code, 
               COUNT(*) as total_requests,
               SUM(CASE WHEN status = 'approved' THEN seats ELSE 0 END) as approved_seats
        FROM bookings
        GROUP BY package_code
    ");
    while ($row = $statQuery->fetch()) {
        $code = $row['package_code'];
        if (isset($stats[$code])) {
            $stats[$code]['total'] = $row['total_requests'];
            $stats[$code]['approved'] = intval($row['approved_seats']);
        }
    }
} catch (Exception $e) {
    // Fail silently
}

// 3. Filters
$filterStatus = $_GET['status'] ?? '';
$filterPackage = $_GET['package'] ?? '';

// Build Query
$queryStr = "SELECT b.*, p.name as package_name FROM bookings b JOIN packages p ON b.package_code = p.code WHERE 1=1";
$params = [];

if (!empty($filterStatus)) {
    $queryStr .= " AND b.status = ?";
    $params[] = $filterStatus;
}
if (!empty($filterPackage)) {
    $queryStr .= " AND b.package_code = ?";
    $params[] = $filterPackage;
}

$queryStr .= " ORDER BY b.id DESC";

$bookingsList = [];
try {
    $listStmt = $db->prepare($queryStr);
    $listStmt->execute($params);
    $bookingsList = $listStmt->fetchAll();
} catch (Exception $e) {
    $actionError = "Failed to load bookings: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>INZANITY | Admin Dashboard</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                        syne: ['Syne', 'sans-serif'],
                    },
                    colors: {
                        adminPurple: '#2d0f4c', // Dark Purple
                        adminAccent: '#8b5cf6', // Violet
                        adminLightBg: '#f9f9fb',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-adminLightBg text-gray-800 font-sans min-h-screen">

    <!-- Top Admin Header -->
    <header class="bg-gradient-to-r from-adminPurple to-purple-900 text-white shadow-lg sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 py-3 flex justify-between items-center">
            <div class="flex items-center gap-3">
                <span class="font-syne text-xl font-black tracking-widest text-white">INZANITY</span>
                <span class="bg-white/10 px-2.5 py-1 rounded-full text-xs font-semibold uppercase tracking-wider hidden sm:inline">Control Panel</span>
            </div>

            <div class="flex items-center gap-2">
                <!-- Scanner (Camera Icon) -->
                <a href="mark.php" title="Scanner" class="group flex items-center justify-center w-10 h-10 bg-violet-600 hover:bg-violet-500 rounded-xl transition-all shadow-md">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </a>

                <!-- Settings (Gear Icon) -->
                <button onclick="openCredModal()" title="Settings" class="flex items-center justify-center w-10 h-10 bg-white/10 hover:bg-white/20 rounded-xl transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                </button>

                <!-- Logout Icon -->
                <a href="logout.php" title="Logout" class="flex items-center justify-center w-10 h-10 bg-rose-600/80 hover:bg-rose-600 rounded-xl transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8">

        <!-- Credential Success/Error banners -->
        <?php if (!empty($credSuccess)): ?>
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl flex items-center gap-3 text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span><?php echo htmlspecialchars($credSuccess); ?></span>
            </div>
        <?php endif; ?>
        <?php if (!empty($credError)): ?>
            <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl flex items-center gap-3 text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span><?php echo htmlspecialchars($credError); ?></span>
            </div>
        <?php endif; ?>

        <!-- Status Feedback Toasts -->
        <?php if (!empty($actionSuccess)): ?>
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl flex items-center gap-3 text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span><?php echo htmlspecialchars($actionSuccess); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($actionError)): ?>
            <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl flex items-center gap-3 text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span><?php echo htmlspecialchars($actionError); ?></span>
            </div>
        <?php endif; ?>

        <!-- Compact Stats Row -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 mb-6 flex divide-x divide-gray-100 overflow-hidden">
            <!-- VVIP -->
            <div class="flex-1 flex items-center gap-3 px-4 py-4">
                <span class="w-2 h-8 rounded-full bg-amber-400 shrink-0"></span>
                <div class="min-w-0">
                    <p class="text-xs font-bold text-amber-500 uppercase tracking-wider truncate">VVIP</p>
                    <p class="text-2xl font-extrabold text-gray-900 leading-none"><?php echo $stats['VV']['approved']; ?><span class="text-xs font-normal text-gray-400 ml-1">/ 30</span></p>
                </div>
            </div>
            <!-- VIP -->
            <div class="flex-1 flex items-center gap-3 px-4 py-4">
                <span class="w-2 h-8 rounded-full bg-violet-500 shrink-0"></span>
                <div class="min-w-0">
                    <p class="text-xs font-bold text-violet-600 uppercase tracking-wider truncate">VIP</p>
                    <p class="text-2xl font-extrabold text-gray-900 leading-none"><?php echo $stats['VP']['approved']; ?><span class="text-xs font-normal text-gray-400 ml-1">/ 50</span></p>
                </div>
            </div>
            <!-- General -->
            <div class="flex-1 flex items-center gap-3 px-4 py-4">
                <span class="w-2 h-8 rounded-full bg-blue-400 shrink-0"></span>
                <div class="min-w-0">
                    <p class="text-xs font-bold text-blue-500 uppercase tracking-wider truncate">General</p>
                    <p class="text-2xl font-extrabold text-gray-900 leading-none"><?php echo $stats['GN']['approved']; ?><span class="text-xs font-normal text-gray-400 ml-1">/ 200</span></p>
                </div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 mb-8">
            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">Filter Bookings</h4>
            <form method="GET" class="flex flex-wrap items-end gap-4">
                <!-- Status Filter -->
                <div class="flex-grow min-w-[200px]">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                    <select name="status" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 outline-none focus:border-adminAccent transition-all text-sm">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="approved" <?php echo $filterStatus === 'approved' ? 'selected' : ''; ?>>Approved</option>
                        <option value="rejected" <?php echo $filterStatus === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                </div>

                <!-- Package Filter -->
                <div class="flex-grow min-w-[200px]">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Package Code</label>
                    <select name="package" class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-2.5 outline-none focus:border-adminAccent transition-all text-sm">
                        <option value="">All Packages</option>
                        <option value="GN" <?php echo $filterPackage === 'GN' ? 'selected' : ''; ?>>General (GN)</option>
                        <option value="VP" <?php echo $filterPackage === 'VP' ? 'selected' : ''; ?>>VIP (VP)</option>
                        <option value="VV" <?php echo $filterPackage === 'VV' ? 'selected' : ''; ?>>VVIP (VV)</option>
                    </select>
                </div>

                <!-- Filter Actions -->
                <div class="flex gap-2">
                    <button type="submit" class="bg-adminPurple hover:bg-purple-900 text-white font-semibold text-sm px-6 py-3 rounded-xl transition-all">
                        Apply Filters
                    </button>
                    <a href="dashboard.php" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold text-sm px-6 py-3 rounded-xl transition-all text-center">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Bookings Section -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-4 sm:px-6 py-4 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                <h3 class="font-syne text-base sm:text-lg font-bold text-gray-900">Registration Requests</h3>
                <span class="bg-gray-100 text-gray-600 text-xs px-3 py-1 rounded-full font-medium">Total: <?php echo count($bookingsList); ?></span>
            </div>

            <?php if (count($bookingsList) > 0): ?>

                <!-- DESKTOP TABLE (hidden on mobile) -->
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-gray-100 text-xs font-bold text-gray-400 uppercase tracking-wider bg-gray-50/20">
                                <th class="px-6 py-4">Ref ID</th>
                                <th class="px-6 py-4">Name</th>
                                <th class="px-6 py-4">NIC</th>
                                <th class="px-6 py-4">WhatsApp</th>
                                <th class="px-6 py-4">Package</th>
                                <th class="px-6 py-4 text-center">Seats</th>
                                <th class="px-6 py-4">Receipt</th>
                                <th class="px-6 py-4">Status</th>
                                <th class="px-6 py-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50 text-sm">
                            <?php foreach ($bookingsList as $b): ?>
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-4 font-bold text-adminPurple font-syne"><?php echo htmlspecialchars($b['booking_ref']); ?></td>
                                    <td class="px-6 py-4 font-semibold text-gray-900"><?php echo htmlspecialchars($b['name']); ?></td>
                                    <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($b['nic']); ?></td>
                                    <td class="px-6 py-4 text-gray-600">
                                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $b['whatsapp']); ?>" target="_blank" class="text-adminAccent hover:underline font-medium">
                                            <?php echo htmlspecialchars($b['whatsapp']); ?>
                                        </a>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="text-xs px-2.5 py-1 rounded-md font-semibold
                                            <?php
                                            if ($b['package_code'] === 'VV') echo 'bg-amber-100 text-amber-800';
                                            elseif ($b['package_code'] === 'VP') echo 'bg-violet-100 text-violet-800';
                                            else echo 'bg-blue-100 text-blue-800';
                                            ?>">
                                            <?php echo htmlspecialchars($b['package_name']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center font-bold text-gray-900"><?php echo $b['seats']; ?></td>
                                    <td class="px-6 py-4">
                                        <button onclick="viewReceipt('../<?php echo htmlspecialchars($b['receipt_path']); ?>', '<?php echo htmlspecialchars($b['booking_ref']); ?>')"
                                                class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold px-3 py-1.5 rounded-lg transition-all">
                                            View
                                        </button>
                                    </td>
                                    <td class="px-6 py-4">
                                        <?php if ($b['status'] === 'pending'): ?>
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>
                                                Pending
                                            </span>
                                        <?php elseif ($b['status'] === 'approved'): ?>
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                                Approved
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">
                                                <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                                Rejected
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-1.5">
                                            <?php if ($b['status'] === 'pending'): ?>
                                                <!-- Approve Form -->
                                                <form method="POST" onsubmit="return confirm('Approve this request?');">
                                                    <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-3 py-1.5 rounded-lg transition-all shadow-sm">
                                                        Approve
                                                    </button>
                                                </form>
                                                <!-- Reject Form -->
                                                <form method="POST" onsubmit="return confirm('Reject this request?');">
                                                    <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold px-3 py-1.5 rounded-lg transition-all">
                                                        Reject
                                                    </button>
                                                </form>
                                            <?php elseif ($b['status'] === 'approved'): ?>
                                                <!-- Deapprove Form -->
                                                <form method="POST" onsubmit="return confirm('Revoke approval status? Available seats will be restored.');">
                                                    <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                                    <input type="hidden" name="action" value="deapprove">
                                                    <button type="submit" class="bg-yellow-500 hover:bg-yellow-600 text-black text-xs font-bold px-3 py-1.5 rounded-lg transition-all">
                                                        Deapprove
                                                    </button>
                                                </form>
                                                <!-- E-Ticket Link -->
                                                <a href="../ticket/ticket.php?ref=<?php echo urlencode($b['booking_ref']); ?>" target="_blank"
                                                   class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-3 py-1.5 rounded-lg transition-all flex items-center">
                                                    Ticket
                                                </a>
                                            <?php else: ?>
                                                <!-- If rejected, allow back to pending or approve -->
                                                <form method="POST" onsubmit="return confirm('Set back to pending?');">
                                                    <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                                    <input type="hidden" name="action" value="deapprove">
                                                    <button type="submit" class="bg-gray-500 hover:bg-gray-600 text-white text-xs font-bold px-3 py-1.5 rounded-lg transition-all">
                                                        Reset
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <!-- Delete Form -->
                                            <form method="POST" onsubmit="return confirm('Permanently delete this record? This action cannot be undone.');">
                                                <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" class="text-rose-600 hover:bg-rose-50 hover:text-rose-700 p-1.5 rounded-lg transition-all">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- MOBILE CARDS (shown only on mobile) -->
                <div class="md:hidden divide-y divide-gray-100">
                    <?php foreach ($bookingsList as $b): ?>
                        <div class="p-4">
                            <!-- Card Header -->
                            <div class="flex items-start justify-between mb-3">
                                <div>
                                    <p class="font-syne font-bold text-adminPurple text-sm"><?php echo htmlspecialchars($b['booking_ref']); ?></p>
                                    <p class="font-semibold text-gray-900 text-sm mt-0.5"><?php echo htmlspecialchars($b['name']); ?></p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs px-2 py-1 rounded-md font-semibold
                                        <?php
                                        if ($b['package_code'] === 'VV') echo 'bg-amber-100 text-amber-800';
                                        elseif ($b['package_code'] === 'VP') echo 'bg-violet-100 text-violet-800';
                                        else echo 'bg-blue-100 text-blue-800';
                                        ?>">
                                        <?php echo htmlspecialchars($b['package_name']); ?>
                                    </span>
                                    <?php if ($b['status'] === 'pending'): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span>Pending
                                        </span>
                                    <?php elseif ($b['status'] === 'approved'): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Approved
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-semibold bg-rose-100 text-rose-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>Rejected
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Card Details -->
                            <div class="grid grid-cols-2 gap-x-4 gap-y-1 text-xs text-gray-500 mb-3">
                                <div><span class="font-semibold text-gray-700">NIC:</span> <?php echo htmlspecialchars($b['nic']); ?></div>
                                <div><span class="font-semibold text-gray-700">Seats:</span> <?php echo $b['seats']; ?></div>
                                <div class="col-span-2">
                                    <span class="font-semibold text-gray-700">WhatsApp:</span>
                                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $b['whatsapp']); ?>" target="_blank" class="text-adminAccent hover:underline ml-1"><?php echo htmlspecialchars($b['whatsapp']); ?></a>
                                </div>
                            </div>

                            <!-- Card Actions -->
                            <div class="flex flex-wrap gap-2">
                                <button onclick="viewReceipt('../<?php echo htmlspecialchars($b['receipt_path']); ?>', '<?php echo htmlspecialchars($b['booking_ref']); ?>')"
                                        class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold px-3 py-2 rounded-lg transition-all">
                                    View Receipt
                                </button>

                                <?php if ($b['status'] === 'pending'): ?>
                                    <form method="POST" onsubmit="return confirm('Approve this request?');">
                                        <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="bg-emerald-600 text-white text-xs font-bold px-3 py-2 rounded-lg">Approve</button>
                                    </form>
                                    <form method="POST" onsubmit="return confirm('Reject this request?');">
                                        <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="bg-amber-500 text-white text-xs font-bold px-3 py-2 rounded-lg">Reject</button>
                                    </form>
                                <?php elseif ($b['status'] === 'approved'): ?>
                                    <form method="POST" onsubmit="return confirm('Revoke approval?');">
                                        <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                        <input type="hidden" name="action" value="deapprove">
                                        <button type="submit" class="bg-yellow-400 text-black text-xs font-bold px-3 py-2 rounded-lg">Deapprove</button>
                                    </form>
                                    <a href="../ticket/ticket.php?ref=<?php echo urlencode($b['booking_ref']); ?>" target="_blank"
                                       class="bg-blue-600 text-white text-xs font-bold px-3 py-2 rounded-lg">Ticket</a>
                                <?php else: ?>
                                    <form method="POST" onsubmit="return confirm('Set back to pending?');">
                                        <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                        <input type="hidden" name="action" value="deapprove">
                                        <button type="submit" class="bg-gray-500 text-white text-xs font-bold px-3 py-2 rounded-lg">Reset</button>
                                    </form>
                                <?php endif; ?>

                                <form method="POST" onsubmit="return confirm('Permanently delete this record?');">
                                    <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="bg-rose-100 text-rose-700 text-xs font-bold px-3 py-2 rounded-lg">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php else: ?>
                <div class="px-6 py-12 text-center text-gray-400">No booking requests found matching filters.</div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Receipt Viewer Modal -->
    <div id="receiptModal" class="fixed inset-0 z-50 items-center justify-center p-4 hidden">
        <div class="absolute inset-0 bg-black/75 backdrop-blur-sm" onclick="closeReceipt()"></div>
        <div class="relative max-w-lg w-full bg-white rounded-3xl p-6 shadow-2xl z-10 animate-in fade-in zoom-in duration-200">
            <div class="flex justify-between items-center mb-4">
                <h3 class="font-syne text-lg font-bold text-gray-900" id="receiptModalTitle">Receipt</h3>
                <button onclick="closeReceipt()" class="text-gray-400 hover:text-gray-900 bg-gray-100 p-2 rounded-full transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            
            <div class="border border-gray-100 rounded-2xl overflow-hidden bg-gray-50 flex items-center justify-center min-h-[300px]">
                <img id="receiptModalImg" src="" alt="Payment Receipt" class="max-h-[65vh] w-auto object-contain">
            </div>
        </div>
    </div>

    <!-- Edit Credentials Modal -->
    <div id="credModal" class="fixed inset-0 z-50 items-center justify-center p-4 hidden">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" onclick="closeCredModal()"></div>
        <div class="relative max-w-md w-full bg-white rounded-3xl p-8 shadow-2xl z-10">
            <div class="flex justify-between items-center mb-6">
                <h3 class="font-syne text-lg font-bold text-gray-900">Edit Admin Credentials</h3>
                <button onclick="closeCredModal()" class="text-gray-400 hover:text-gray-900 bg-gray-100 p-2 rounded-full transition-all">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form method="POST" action="dashboard.php">
                <input type="hidden" name="action" value="update_credentials">

                <div class="mb-4">
                    <label class="block text-xs font-semibold text-violet-700 uppercase tracking-wider mb-1.5">New Username</label>
                    <input type="text" name="new_username" value="<?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'admin'); ?>" required
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-violet-400 focus:ring-2 focus:ring-violet-100">
                </div>

                <div class="mb-4">
                    <label class="block text-xs font-semibold text-violet-700 uppercase tracking-wider mb-1.5">New Password</label>
                    <input type="password" name="new_password" placeholder="Leave blank to keep current"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-violet-400 focus:ring-2 focus:ring-violet-100">
                    <p class="text-xs text-gray-400 mt-1">Leave empty to keep existing password.</p>
                </div>

                <div class="mb-6">
                    <label class="block text-xs font-semibold text-violet-700 uppercase tracking-wider mb-1.5">Confirm New Password</label>
                    <input type="password" name="confirm_password" placeholder="Re-enter new password"
                        class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-violet-400 focus:ring-2 focus:ring-violet-100">
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-violet-600 to-purple-700 hover:from-violet-500 hover:to-purple-600 text-white font-bold py-3 rounded-xl text-sm transition-all shadow-md">
                    Save Changes
                </button>
            </form>
        </div>
    </div>

    <script>
        function viewReceipt(path, ref) {
            document.getElementById('receiptModalImg').src = path;
            document.getElementById('receiptModalTitle').innerText = 'Receipt for Reference: ' + ref;
            
            const modal = document.getElementById('receiptModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeReceipt() {
            const modal = document.getElementById('receiptModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }

        function openCredModal() {
            const modal = document.getElementById('credModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeCredModal() {
            const modal = document.getElementById('credModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }
    </script>
</body>
</html>
