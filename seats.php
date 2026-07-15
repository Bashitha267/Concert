<?php
// seats.php - Search and view booking status (Receive Your Seat)
require_once 'db.php';

$search = $_GET['search'] ?? '';
$bookings = [];
$searched = false;
$error = '';

if (!empty($search)) {
    $search = trim($search);
    $searched = true;
    try {
        $db = DB::getConnection();
        // Allow searching by NIC, WhatsApp, or Booking Reference
        $stmt = $db->prepare("
            SELECT b.*, p.name as package_name, p.price 
            FROM bookings b
            JOIN packages p ON b.package_code = p.code
            WHERE b.nic = ? OR b.whatsapp = ? OR b.booking_ref = ?
            ORDER BY b.created_at DESC
        ");
        $stmt->execute([$search, $search, $search]);
        $bookings = $stmt->fetchAll();
    } catch (Exception $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receive Your Seat | INZANITY</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&family=Syne:wght@700;800&display=swap" rel="stylesheet">
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
                        accentNeon: '#a78bfa',
                    }
                }
            }
        }
    </script>
    <style>
        .neon-glow {
            text-shadow: 0 0 10px rgba(124, 58, 237, 0.6);
        }
        .glass-panel {
            background: rgba(13, 10, 25, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
    </style>
</head>
<body class="bg-darkBg text-gray-100 font-sans min-h-screen flex flex-col justify-between selection:bg-accentViolet selection:text-white">

    <!-- Radial Background Gradient for Concert Vibe -->
    <div class="fixed inset-0 pointer-events-none z-0 bg-[radial-gradient(circle_at_top_right,rgba(124,58,237,0.12),transparent_40%)] bg-[radial-gradient(circle_at_bottom_left,rgba(76,29,149,0.12),transparent_45%)]"></div>

    <!-- Navigation Header -->
    <header class="relative z-50 glass-panel border-b border-white/5 sticky top-0">
        <div class="max-w-6xl mx-auto px-4 py-4 flex items-center justify-between">
            <a href="index.php" class="font-syne text-2xl font-extrabold tracking-wider text-white neon-glow">
                INZANITY
            </a>
            <a href="index.php" class="text-sm text-gray-400 hover:text-white transition-all">
                &larr; Back to Home
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main class="relative z-10 max-w-3xl mx-auto w-full px-4 py-12 flex-grow">
        <div class="text-center mb-10">
            <span class="text-xs font-semibold tracking-widest text-accentNeon uppercase bg-accentViolet/20 px-3 py-1 rounded-full border border-accentViolet/30">
                Ticket Desk
            </span>
            <h1 class="font-syne text-4xl md:text-5xl font-extrabold text-white mt-3 mb-4 uppercase">
                Receive Your Seat
            </h1>
            <p class="text-gray-400 text-sm max-w-md mx-auto">
                Enter your NIC number, WhatsApp number, or Booking Reference code to locate your approved ticket.
            </p>
        </div>

        <!-- Search Form -->
        <div class="glass-panel rounded-3xl p-6 border border-white/10 mb-8">
            <form method="GET" class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-grow">
                    <input type="text" name="search" required value="<?php echo htmlspecialchars($search); ?>"
                           placeholder="NIC, WhatsApp (e.g. 0778214024), or Ref Code"
                           class="w-full bg-white/5 border border-white/10 focus:border-accentViolet focus:ring-1 focus:ring-accentViolet rounded-2xl px-5 py-4 text-white placeholder-gray-500 outline-none transition-all">
                </div>
                <button type="submit" class="bg-accentViolet hover:bg-violet-500 text-white font-bold py-4 px-8 rounded-2xl transition-all duration-300">
                    Find Booking
                </button>
            </form>
            <?php if (!empty($error)): ?>
                <p class="text-red-400 text-xs mt-3"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>
        </div>

        <!-- Results Section -->
        <?php if ($searched): ?>
            <div class="space-y-6">
                <?php if (count($bookings) > 0): ?>
                    <h3 class="text-sm font-semibold tracking-wider text-gray-500 uppercase px-1">Bookings Found (<?php echo count($bookings); ?>)</h3>
                    
                    <?php foreach ($bookings as $b): ?>
                        <div class="glass-panel rounded-3xl p-6 border border-white/10 flex flex-col md:flex-row justify-between gap-6 items-start md:items-center">
                            <div class="space-y-2">
                                <div class="flex items-center gap-3">
                                    <span class="font-syne text-2xl font-black tracking-wider text-white"><?php echo htmlspecialchars($b['booking_ref']); ?></span>
                                    
                                    <!-- Dynamic Badge Status -->
                                    <?php if ($b['status'] === 'pending'): ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-amber-500/10 text-amber-400 border border-amber-500/25 animate-pulse">
                                            Verification Pending
                                        </span>
                                    <?php elseif ($b['status'] === 'approved'): ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-emerald-500/10 text-emerald-400 border border-emerald-500/25">
                                            Approved
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-red-500/10 text-red-400 border border-red-500/25">
                                            Rejected
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <p class="text-sm text-gray-300">
                                    Attendee Name: <strong class="text-white"><?php echo htmlspecialchars($b['name']); ?></strong>
                                </p>
                                <p class="text-xs text-gray-400">
                                    NIC: <?php echo htmlspecialchars($b['nic']); ?> | WhatsApp: <?php echo htmlspecialchars($b['whatsapp']); ?>
                                </p>
                                <div class="flex items-center gap-6 mt-1 text-xs text-gray-400">
                                    <span>Package: <strong class="text-white"><?php echo htmlspecialchars($b['package_name']); ?></strong></span>
                                    <span>Seats: <strong class="text-white"><?php echo $b['seats']; ?> <?php echo $b['seats'] > 1 ? 'Seats' : 'Seat'; ?></strong></span>
                                </div>
                            </div>

                            <div class="w-full md:w-auto">
                                <?php if ($b['status'] === 'approved'): ?>
                                    <a href="ticket/ticket.php?ref=<?php echo urlencode($b['booking_ref']); ?>" target="_blank"
                                       class="block text-center bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-black font-extrabold px-6 py-3.5 rounded-xl text-sm transition-all shadow-md shadow-emerald-500/10">
                                        Get E-Ticket
                                    </a>
                                <?php elseif ($b['status'] === 'pending'): ?>
                                    <div class="text-xs text-gray-500 md:text-right max-w-[200px]">
                                        We are validating your bank receipt. Please check back in a few minutes.
                                    </div>
                                <?php else: ?>
                                    <div class="text-xs text-red-400 md:text-right max-w-[200px]">
                                        Registration rejected. Please contact Support: <strong class="text-white">0778214024</strong>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>

                <?php else: ?>
                    <!-- No bookings found screen -->
                    <div class="glass-panel rounded-3xl p-12 text-center border border-white/5">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-gray-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <h3 class="text-lg font-bold text-white mb-2">No Bookings Found</h3>
                        <p class="text-gray-400 text-sm max-w-sm mx-auto mb-6">
                            We couldn't find any tickets matching "<span class="text-white font-semibold"><?php echo htmlspecialchars($search); ?></span>". Check spelling or register below.
                        </p>
                        <a href="index.php#packages" class="inline-block bg-accentViolet hover:bg-violet-500 text-white font-semibold px-6 py-3 rounded-xl text-sm transition-all">
                            Book Tickets Now
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer Section -->
    <footer class="relative z-10 border-t border-white/5 bg-black/60 backdrop-blur-md">
        <div class="max-w-6xl mx-auto px-4 py-8 flex flex-col sm:flex-row items-center justify-between text-xs text-gray-500">
            <span>&copy; 2026 INZANITY HipHop Concert. All rights reserved.</span>
            <span>Organizer: H. Pubudu Eranga | Hotline: 0778214024</span>
        </div>
    </footer>

</body>
</html>
