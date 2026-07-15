<?php
// admin/mark.php - QR Code scanner page for ticket verification and partial seat attendance tracking
require_once '../db.php';

// Handle AJAX Request: Get Ticket Details
if (($_GET['action'] ?? '') === 'get_ticket') {
    header('Content-Type: application/json');
    $ref = trim($_GET['ref'] ?? '');
    
    try {
        $db = DB::getConnection();
        $stmt = $db->prepare("
            SELECT b.*, p.name as package_name 
            FROM bookings b
            JOIN packages p ON b.package_code = p.code
            WHERE b.booking_ref = ?
        ");
        $stmt->execute([$ref]);
        $booking = $stmt->fetch();
        
        if ($booking) {
            echo json_encode([
                'success' => true,
                'booking' => [
                    'booking_ref' => $booking['booking_ref'],
                    'name' => $booking['name'],
                    'nic' => $booking['nic'],
                    'package_name' => $booking['package_name'],
                    'status' => $booking['status'],
                    'seats' => intval($booking['seats']),
                    'attended_seats' => intval($booking['attended_seats'])
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Ticket reference not found.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// Handle AJAX Request: Confirm Attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'confirm_attendance') {
    header('Content-Type: application/json');
    $ref = trim($_POST['ref'] ?? '');
    $confirmSeats = intval($_POST['seats'] ?? 0);
    
    if (empty($ref) || $confirmSeats <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
        exit;
    }
    
    try {
        $db = DB::getConnection();
        $db->beginTransaction();
        
        // Fetch current attended status
        $stmt = $db->prepare("SELECT id, seats, attended_seats, status FROM bookings WHERE booking_ref = ? FOR UPDATE");
        $stmt->execute([$ref]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            echo json_encode(['success' => false, 'message' => 'Booking not found.']);
            exit;
        }
        
        if ($booking['status'] !== 'approved') {
            echo json_encode(['success' => false, 'message' => 'This ticket is not approved yet.']);
            exit;
        }
        
        $totalSeats = intval($booking['seats']);
        $alreadyAttended = intval($booking['attended_seats']);
        $remainingSeats = $totalSeats - $alreadyAttended;
        
        if ($confirmSeats > $remainingSeats) {
            echo json_encode(['success' => false, 'message' => 'Requested seats exceed remaining seats (' . $remainingSeats . ').']);
            exit;
        }
        
        // Update attended_seats
        $newAttended = $alreadyAttended + $confirmSeats;
        $upStmt = $db->prepare("UPDATE bookings SET attended_seats = ? WHERE booking_ref = ?");
        $upStmt->execute([$newAttended, $ref]);
        
        // Log in attendance table
        $logStmt = $db->prepare("INSERT INTO attendance (booking_ref, seats_confirmed) VALUES (?, ?)");
        $logStmt->execute([$ref, $confirmSeats]);
        
        $db->commit();
        echo json_encode(['success' => true, 'message' => "Successfully checked in $confirmSeats seat(s). Total: $newAttended/$totalSeats"]);
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        echo json_encode(['success' => false, 'message' => 'Verification failed: ' . $e->getMessage()]);
    }
    exit;
}

// Fetch all attendance logs
$attendanceLogs = [];
try {
    $db = DB::getConnection();
    $logsQuery = $db->query("
        SELECT a.seats_confirmed, a.scanned_at, b.booking_ref, b.name, b.nic, p.name as package_name
        FROM attendance a
        JOIN bookings b ON a.booking_ref = b.booking_ref
        JOIN packages p ON b.package_code = p.code
        ORDER BY a.scanned_at DESC
    ");
    $attendanceLogs = $logsQuery->fetchAll();
} catch (Exception $e) {
    // Fail silently
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gate Attendance Scanner | INZANITY</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    <!-- html5-qrcode scanner library -->
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                        syne: ['Syne', 'sans-serif'],
                    },
                    colors: {
                        adminPurple: '#2d0f4c',
                        adminAccent: '#8b5cf6',
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
        <div class="max-w-7xl mx-auto px-4 py-4 flex flex-col sm:flex-row justify-between items-center gap-4">
            <div class="flex items-center gap-3">
                <span class="font-syne text-2xl font-black tracking-widest text-white">INZANITY</span>
                <span class="bg-white/10 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider">Attendance Gate</span>
            </div>
            
            <div class="flex items-center gap-4">
                <a href="dashboard.php" class="bg-white/10 hover:bg-white/20 text-white text-sm font-semibold px-4 py-2 rounded-xl transition-all">
                    &larr; Admin Dashboard
                </a>
            </div>
        </div>
    </header>

    <main class="max-w-4xl mx-auto px-4 py-8">
        
        <!-- Live Camera Scanner Box -->
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 mb-8 overflow-hidden">
            <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                <div>
                    <h3 class="font-syne text-lg font-bold text-gray-900">QR Gate Scanner</h3>
                    <p class="text-xs text-gray-500">Scan ticket QR codes or input code manually below.</p>
                </div>
                <!-- Manual Code Input -->
                <div class="flex gap-2">
                    <input type="text" id="manualCode" placeholder="e.g. VP1002" class="bg-gray-50 border border-gray-200 rounded-lg px-3 py-1.5 text-xs outline-none focus:border-adminAccent uppercase">
                    <button onclick="handleManualCheck()" class="bg-adminPurple hover:bg-purple-900 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition-all">Check</button>
                </div>
            </div>

            <!-- Scanner Camera Box -->
            <div class="relative bg-black rounded-2xl overflow-hidden aspect-[4/3] max-w-lg mx-auto border border-gray-100 flex items-center justify-center">
                <div id="reader" class="w-full h-full"></div>
            </div>
            <div class="text-center mt-3">
                <button id="startScanBtn" onclick="startScanner()" class="bg-adminAccent hover:bg-violet-600 text-white text-xs font-bold px-4 py-2 rounded-full transition-all">Start Camera</button>
            </div>
        </div>

        <!-- Attendance Confirmation Modal -->
        <div id="confirmModal" class="fixed inset-0 z-50 items-center justify-center p-4 hidden">
            <div class="absolute inset-0 bg-black/75 backdrop-blur-sm" onclick="closeConfirmModal()"></div>
            <div class="relative max-w-md w-full bg-white rounded-3xl p-6 shadow-2xl z-10 animate-in fade-in zoom-in duration-200">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-syne text-lg font-bold text-gray-900">Verify Gate Entry</h3>
                    <button onclick="closeConfirmModal()" class="text-gray-400 hover:text-gray-900 bg-gray-100 p-2 rounded-full transition-all">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <!-- Ticket Details -->
                    <div class="bg-gray-50 border border-gray-100 rounded-2xl p-4 space-y-2 text-sm">
                        <div class="flex justify-between py-1 border-b border-gray-200/50">
                            <span class="text-gray-500">Ref Code:</span>
                            <strong class="text-adminPurple font-syne" id="modalRef">VP1002</strong>
                        </div>
                        <div class="flex justify-between py-1 border-b border-gray-200/50">
                            <span class="text-gray-500">Name:</span>
                            <strong class="text-gray-900" id="modalName">N/A</strong>
                        </div>
                        <div class="flex justify-between py-1 border-b border-gray-200/50">
                            <span class="text-gray-500">NIC:</span>
                            <span class="text-gray-900" id="modalNIC">N/A</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-gray-200/50">
                            <span class="text-gray-500">Package:</span>
                            <span class="font-semibold text-violet-700" id="modalPackage">N/A</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-gray-500">Attendance:</span>
                            <span class="font-bold text-gray-900" id="modalStats">0 / 0 Attended</span>
                        </div>
                    </div>

                    <!-- Seat Adjustment for Scanner -->
                    <div id="checkinSection" class="space-y-3">
                        <label class="block text-xs font-bold text-gray-500 uppercase">Select Check-in seats</label>
                        <div class="flex items-center gap-3">
                            <button type="button" onclick="changeScannerSeats(-1)" class="w-10 h-10 flex items-center justify-center text-gray-800 bg-gray-100 hover:bg-gray-200 rounded-lg text-lg font-bold transition-all select-none">-</button>
                            <input type="number" id="checkinSeats" value="1" min="1" max="1" readonly 
                                   class="w-12 text-center text-lg font-bold text-gray-900 bg-transparent border-none outline-none select-none [appearance:textfield]">
                            <button type="button" onclick="changeScannerSeats(1)" class="w-10 h-10 flex items-center justify-center text-gray-800 bg-gray-100 hover:bg-gray-200 rounded-lg text-lg font-bold transition-all select-none">+</button>
                        </div>
                        <p class="text-xs text-gray-400" id="scannerSeatsInfo"></p>
                    </div>

                    <div id="statusWarning" class="hidden p-3 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl text-xs font-semibold text-center">
                        This ticket is not approved yet!
                    </div>

                    <div id="fullyAttendedWarning" class="hidden p-3 bg-blue-50 border border-blue-200 text-blue-800 rounded-xl text-xs font-semibold text-center">
                        All seats have checked in. Entry denied.
                    </div>
                </div>

                <!-- Footer confirmation controls -->
                <div class="mt-6 flex gap-2">
                    <button id="confirmBtn" onclick="confirmCheckin()" class="flex-grow bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-3 rounded-xl transition-all">Confirm Entry</button>
                    <button onclick="closeConfirmModal()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold px-5 py-3 rounded-xl transition-all">Cancel</button>
                </div>
            </div>
        </div>

        <!-- Total Attendance Log -->
        <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-50 bg-gray-50/50">
                <h3 class="font-syne text-lg font-bold text-gray-900">Total Attended List</h3>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-gray-100 text-xs font-bold text-gray-400 uppercase tracking-wider bg-gray-50/20">
                            <th class="px-6 py-4">Ref Code</th>
                            <th class="px-6 py-4">Attendee</th>
                            <th class="px-6 py-4">NIC</th>
                            <th class="px-6 py-4">Package</th>
                            <th class="px-6 py-4 text-center">Checked-In</th>
                            <th class="px-6 py-4 text-right">Time Confirmed</th>
                        </tr>
                    </thead>
                    <tbody id="attendanceLogsBody" class="divide-y divide-gray-50 text-sm">
                        <?php if (count($attendanceLogs) > 0): ?>
                            <?php foreach ($attendanceLogs as $log): ?>
                                <tr class="hover:bg-gray-50/50 transition-colors">
                                    <td class="px-6 py-4 font-bold text-adminPurple font-syne"><?php echo htmlspecialchars($log['booking_ref']); ?></td>
                                    <td class="px-6 py-4 font-semibold text-gray-900"><?php echo htmlspecialchars($log['name']); ?></td>
                                    <td class="px-6 py-4 text-gray-600"><?php echo htmlspecialchars($log['nic']); ?></td>
                                    <td class="px-6 py-4 text-gray-500"><?php echo htmlspecialchars($log['package_name']); ?></td>
                                    <td class="px-6 py-4 text-center font-bold text-emerald-600">+<?php echo $log['seats_confirmed']; ?> seats</td>
                                    <td class="px-6 py-4 text-right text-gray-400 text-xs"><?php echo date('Y-m-d h:i A', strtotime($log['scanned_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                    No attendees checked in yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <script>
        let html5QrcodeScanner = null;
        let activeRef = '';
        let maxScannerSeats = 1;

        // Initialize and Start QR Scanner
        function startScanner() {
            document.getElementById('startScanBtn').classList.add('hidden');
            
            html5QrcodeScanner = new Html5Qrcode("reader");
            const config = { fps: 10, qrbox: { width: 250, height: 250 } };
            
            html5QrcodeScanner.start(
                { facingMode: "environment" }, 
                config, 
                onScanSuccess, 
                onScanFailure
            ).catch(err => {
                alert("Camera permission denied or camera not found: " + err);
                document.getElementById('startScanBtn').classList.remove('hidden');
            });
        }

        function onScanSuccess(decodedText, decodedResult) {
            // Stop scanner when a code is scanned to prevent double triggers
            if (html5QrcodeScanner) {
                html5QrcodeScanner.stop().then(() => {
                    document.getElementById('startScanBtn').classList.remove('hidden');
                }).catch(err => console.log(err));
            }
            
            // Clean/Trim scanned string and request details
            const cleanRef = decodedText.trim();
            loadTicketDetails(cleanRef);
        }

        function onScanFailure(error) {
            // Silence console errors as scanning loop is rapid
        }

        function handleManualCheck() {
            const manualVal = document.getElementById('manualCode').value.trim();
            if (manualVal) {
                loadTicketDetails(manualVal);
            }
        }

        function loadTicketDetails(ref) {
            activeRef = ref;
            fetch('mark.php?action=get_ticket&ref=' + encodeURIComponent(ref))
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const b = data.booking;
                    document.getElementById('modalRef').innerText = b.booking_ref;
                    document.getElementById('modalName').innerText = b.name;
                    document.getElementById('modalNIC').innerText = b.nic;
                    document.getElementById('modalPackage').innerText = b.package_name;
                    document.getElementById('modalStats').innerText = b.attended_seats + ' / ' + b.seats + ' Seats Checked-in';

                    const remaining = b.seats - b.attended_seats;
                    maxScannerSeats = remaining;

                    // Visibility controls based on check-in state
                    const checkinSection = document.getElementById('checkinSection');
                    const confirmBtn = document.getElementById('confirmBtn');
                    const statusWarning = document.getElementById('statusWarning');
                    const fullyAttendedWarning = document.getElementById('fullyAttendedWarning');

                    checkinSection.classList.add('hidden');
                    confirmBtn.classList.add('hidden');
                    statusWarning.classList.add('hidden');
                    fullyAttendedWarning.classList.add('hidden');

                    if (b.status !== 'approved') {
                        statusWarning.classList.remove('hidden');
                    } else if (remaining <= 0) {
                        fullyAttendedWarning.classList.remove('hidden');
                    } else {
                        checkinSection.classList.remove('hidden');
                        confirmBtn.classList.remove('hidden');
                        
                        // Set checkin seats input
                        document.getElementById('checkinSeats').value = remaining;
                        document.getElementById('scannerSeatsInfo').innerText = 'Remaining seats checkable: ' + remaining;
                    }

                    // Show Confirmation Modal
                    const modal = document.getElementById('confirmModal');
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                alert('Connection failure loading ticket details.');
            });
        }

        function closeConfirmModal() {
            const modal = document.getElementById('confirmModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            activeRef = '';
        }

        function changeScannerSeats(amount) {
            const input = document.getElementById('checkinSeats');
            let val = parseInt(input.value) + amount;
            if (val < 1) val = 1;
            if (val > maxScannerSeats) val = maxScannerSeats;
            input.value = val;
        }

        function confirmCheckin() {
            const seats = parseInt(document.getElementById('checkinSeats').value) || 1;
            const confirmBtn = document.getElementById('confirmBtn');
            confirmBtn.disabled = true;
            confirmBtn.innerText = 'Checking in...';

            const formData = new FormData();
            formData.append('action', 'confirm_attendance');
            formData.append('ref', activeRef);
            formData.append('seats', seats);

            fetch('mark.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                confirmBtn.disabled = false;
                confirmBtn.innerText = 'Confirm Entry';
                
                if (data.success) {
                    alert(data.message);
                    closeConfirmModal();
                    window.location.reload(); // Reload to refresh total logs table
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                confirmBtn.disabled = false;
                confirmBtn.innerText = 'Confirm Entry';
                alert('Connection failure checking in.');
            });
        }
    </script>
</body>
</html>
