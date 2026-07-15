<?php
// ticket/ticket.php - E-Ticket generator page with dark and light themes and QR code
require_once '../db.php';

$ref = $_GET['ref'] ?? '';

if (empty($ref)) {
    die("Error: Ticket reference is required.");
}

try {
    $db = DB::getConnection();
    $stmt = $db->prepare("
        SELECT b.*, p.name as package_name, p.price 
        FROM bookings b
        JOIN packages p ON b.package_code = p.code
        WHERE b.booking_ref = ? AND b.status = 'approved'
    ");
    $stmt->execute([$ref]);
    $booking = $stmt->fetch();
    
    if (!$booking) {
        die("Error: Approved booking not found for reference code: " . htmlspecialchars($ref));
    }
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

// QR Code URL via QRServer API
$qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($booking['booking_ref']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Ticket: <?php echo htmlspecialchars($booking['booking_ref']); ?> | INZANITY</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&family=Syne:wght@700;800;900&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                        syne: ['Syne', 'sans-serif'],
                    },
                    colors: {
                        darkBg: '#050508',
                        accentViolet: '#7c3aed',
                        accentDarkViolet: '#4c1d95',
                        ticketDarkBg: '#0f0e17',
                        ticketLightBg: '#ffffff',
                    }
                }
            }
        }
    </script>
    <style>
        /* Ticket Cutout/Notch design styling */
        .ticket-stub-left, .ticket-stub-right {
            position: relative;
        }
        .ticket-stub-left::after, .ticket-stub-left::before {
            content: '';
            position: absolute;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            background-color: var(--page-bg);
            transition: background-color 0.3s ease;
        }
        /* Positions of circular cutouts on ticket side */
        .ticket-stub-left::before {
            top: -12px;
            right: -12px;
        }
        .ticket-stub-left::after {
            bottom: -12px;
            right: -12px;
        }
        /* Page variables for theme switching */
        :root {
            --page-bg: #050508;
        }
        .light-theme-vars {
            --page-bg: #f3f4f6;
        }
    </style>
</head>
<body id="bodyPage" class="bg-darkBg text-gray-100 font-sans min-h-screen transition-colors duration-300 flex flex-col justify-center items-center p-4 selection:bg-accentViolet selection:text-white">

    <!-- Top Controller Actions -->
    <div class="w-full max-w-lg flex justify-between items-center mb-6 z-10">
        <a href="../seats.php" class="text-xs text-gray-400 hover:text-white flex items-center gap-1 bg-white/5 border border-white/5 px-3 py-1.5 rounded-full transition-all">
            &larr; Exit Ticket View
        </a>
        <button onclick="toggleTheme()" id="themeBtn" class="text-xs font-bold text-white bg-accentViolet hover:bg-violet-600 px-4 py-2 rounded-full shadow-lg shadow-accentViolet/25 transition-all">
            Switch to Light Theme
        </button>
    </div>

    <!-- MAIN TICKET CARD -->
    <div id="ticketContainer" class="w-full max-w-lg rounded-3xl overflow-hidden shadow-2xl transition-all duration-300 border border-white/10 bg-ticketDarkBg">
        
        <!-- Header Art Banner -->
        <div class="relative h-44 overflow-hidden bg-black flex items-center justify-center">
            <img src="../assests/images (11).jpg" alt="Concert Stage" class="absolute inset-0 w-full h-full object-cover opacity-60 filter saturate-150">
            <div class="absolute inset-0 bg-gradient-to-t from-black via-black/40 to-transparent"></div>
            
            <div class="relative text-center z-10 px-4">
                <span class="text-[10px] tracking-[0.25em] font-extrabold text-accentViolet bg-white px-2.5 py-0.5 rounded-full uppercase">Official Gate Pass</span>
                <h2 class="font-syne text-4xl font-black text-white uppercase tracking-tight mt-1">INZANITY</h2>
                <p class="text-[10px] text-gray-300 uppercase tracking-widest font-semibold mt-0.5">Zany Insane Live | Manager: H. Pubudu Eranga</p>
            </div>
        </div>

        <!-- Details Stub -->
        <div class="ticket-stub-left p-6 md:p-8 border-b border-dashed border-gray-800 transition-colors duration-300" id="detailsStub">
            <div class="grid grid-cols-2 gap-y-4 gap-x-2">
                <div>
                    <span class="text-[10px] uppercase font-bold text-gray-500 block">Attendee Name</span>
                    <span class="font-semibold text-white text-sm" id="valName"><?php echo htmlspecialchars($booking['name']); ?></span>
                </div>
                <div>
                    <span class="text-[10px] uppercase font-bold text-gray-500 block">Booking Reference</span>
                    <span class="font-syne font-black text-accentViolet text-sm" id="valRef"><?php echo htmlspecialchars($booking['booking_ref']); ?></span>
                </div>
                <div>
                    <span class="text-[10px] uppercase font-bold text-gray-500 block">NIC / Identity</span>
                    <span class="font-semibold text-white text-sm" id="valNIC"><?php echo htmlspecialchars($booking['nic']); ?></span>
                </div>
                <div>
                    <span class="text-[10px] uppercase font-bold text-gray-500 block">Package Tier</span>
                    <span class="font-bold text-emerald-400 text-sm" id="valPkg"><?php echo htmlspecialchars($booking['package_name']); ?></span>
                </div>
                <div>
                    <span class="text-[10px] uppercase font-bold text-gray-500 block">Quantity</span>
                    <span class="font-bold text-white text-sm" id="valSeats"><?php echo $booking['seats']; ?> Gate Entry Pass<?php echo $booking['seats'] > 1 ? 'es' : ''; ?></span>
                </div>
                <div>
                    <span class="text-[10px] uppercase font-bold text-gray-500 block">Gate Status</span>
                    <span class="inline-flex items-center text-[10px] font-bold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded-full border border-emerald-500/30">Verified Entry</span>
                </div>
            </div>
        </div>

        <!-- Verification/QR Stub -->
        <div class="p-6 md:p-8 flex flex-col items-center text-center" id="qrStub">
            <span class="text-[9px] uppercase font-semibold text-gray-400 tracking-wider mb-3">Scan this code at the gate</span>
            <div class="bg-white p-3 rounded-2xl shadow-inner mb-3">
                <img src="<?php echo $qrUrl; ?>" alt="Gate Check QR" class="w-40 h-40">
            </div>
            
            <p class="text-[11px] text-gray-400 max-w-[280px]">
                Valid for single entry validation. Please present this ticket on your mobile screen or printed version.
            </p>
        </div>
        
    </div>

    <!-- Organizer Banner / Ticket Footer Details -->
    <div class="w-full max-w-lg mt-6 text-center text-[10px] text-gray-500 space-y-1">
        <p>Organizer: H. Pubudu Eranga | Tel: 0778214024 | Email: Zanyinsane20@gmail.com</p>
        <p>&copy; INZANITY HipHop Concert. Valid for 2026 Live Tour event.</p>
    </div>

    <script>
        let isDark = true;

        function toggleTheme() {
            const body = document.getElementById('bodyPage');
            const ticket = document.getElementById('ticketContainer');
            const detailsStub = document.getElementById('detailsStub');
            const qrStub = document.getElementById('qrStub');
            const themeBtn = document.getElementById('themeBtn');

            // Values
            const valName = document.getElementById('valName');
            const valNIC = document.getElementById('valNIC');
            const valSeats = document.getElementById('valSeats');

            if (isDark) {
                // Switch to Light Theme
                body.classList.remove('bg-darkBg', 'text-gray-100');
                body.classList.add('bg-gray-100', 'text-gray-900', 'light-theme-vars');
                
                ticket.classList.remove('bg-ticketDarkBg', 'border-white/10');
                ticket.classList.add('bg-white', 'border-gray-200');
                
                detailsStub.classList.remove('border-gray-800');
                detailsStub.classList.add('border-gray-200');
                
                valName.classList.remove('text-white');
                valName.classList.add('text-gray-900');
                valNIC.classList.remove('text-white');
                valNIC.classList.add('text-gray-900');
                valSeats.classList.remove('text-white');
                valSeats.classList.add('text-gray-900');

                themeBtn.innerText = "Switch to Dark Theme";
                themeBtn.classList.remove('bg-accentViolet');
                themeBtn.classList.add('bg-gray-800', 'hover:bg-gray-700');
                isDark = false;
            } else {
                // Switch to Dark Theme
                body.classList.remove('bg-gray-100', 'text-gray-900', 'light-theme-vars');
                body.classList.add('bg-darkBg', 'text-gray-100');
                
                ticket.classList.remove('bg-white', 'border-gray-200');
                ticket.classList.add('bg-ticketDarkBg', 'border-white/10');
                
                detailsStub.classList.remove('border-gray-200');
                detailsStub.classList.add('border-gray-800');
                
                valName.classList.add('text-white');
                valName.classList.remove('text-gray-900');
                valNIC.classList.add('text-white');
                valNIC.classList.remove('text-gray-900');
                valSeats.classList.add('text-white');
                valSeats.classList.remove('text-gray-900');

                themeBtn.innerText = "Switch to Light Theme";
                themeBtn.classList.remove('bg-gray-800', 'hover:bg-gray-700');
                themeBtn.classList.add('bg-accentViolet', 'hover:bg-violet-600');
                isDark = true;
            }
        }
    </script>
</body>
</html>
