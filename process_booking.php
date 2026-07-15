<?php
// process_booking.php - Handle registration AJAX POST requests
header('Content-Type: application/json');
require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// 1. Inputs
$name = trim($_POST['name'] ?? '');
$nic = trim($_POST['nic'] ?? '');
$whatsapp = trim($_POST['whatsapp'] ?? '');
$package_code = trim($_POST['package_code'] ?? '');
$seats = intval($_POST['seats'] ?? 0);

// Validate
if (empty($name) || empty($nic) || empty($whatsapp) || empty($package_code) || $seats <= 0) {
    echo json_encode(['success' => false, 'message' => 'Please fill in all fields correctly.']);
    exit;
}

// 2. Upload Receipt Validation
if (!isset($_FILES['receipt']) || $_FILES['receipt']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Payment receipt is required.']);
    exit;
}

$file = $_FILES['receipt'];
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Receipt must be a JPG, PNG, WEBP image or PDF file.']);
    exit;
}

try {
    $db = DB::getConnection();
    
    // Check package availability
    $stmt = $db->prepare("SELECT * FROM packages WHERE code = ?");
    $stmt->execute([$package_code]);
    $package = $stmt->fetch();
    
    if (!$package) {
        echo json_encode(['success' => false, 'message' => 'Invalid package selected.']);
        exit;
    }
    
    if ($package['available_seats'] < $seats) {
        echo json_encode(['success' => false, 'message' => 'Not enough seats available. Only ' . $package['available_seats'] . ' left.']);
        exit;
    }

    // Begin database transaction to ensure safety on concurrent IDs
    $db->beginTransaction();

    // 3. Generate booking reference: e.g. VP1000
    // Query the highest reference code for this package code
    $refQuery = $db->prepare("SELECT booking_ref FROM bookings WHERE package_code = ? ORDER BY booking_ref DESC LIMIT 1 FOR UPDATE");
    $refQuery->execute([$package_code]);
    $latestRef = $refQuery->fetch();

    if ($latestRef) {
        // Strip the 2-letter package code and get number
        $numPart = intval(substr($latestRef['booking_ref'], 2));
        $newNum = $numPart + 1;
        $booking_ref = $package_code . $newNum;
    } else {
        // If first booking for this package, start at 1000
        $booking_ref = $package_code . '1000';
    }

    // 4. Save uploaded file to uploads/ directory
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = $booking_ref . '_' . time() . '.' . $fileExt;
    $targetPath = $uploadDir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception("Failed to save uploaded receipt.");
    }

    // 5. Save booking record
    $insStmt = $db->prepare("INSERT INTO bookings (booking_ref, name, nic, whatsapp, package_code, seats, receipt_path, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
    $insStmt->execute([
        $booking_ref,
        $name,
        $nic,
        $whatsapp,
        $package_code,
        $seats,
        $targetPath
    ]);

    // Commit transaction
    $db->commit();

    echo json_encode([
        'success' => true,
        'booking_ref' => $booking_ref
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
