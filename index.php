<?php
// index.php - Home Page for INZANITY HipHop Concert
require_once 'db.php';

$packages = [
    'GN' => ['name' => 'General Package', 'price' => 1500, 'available_seats' => 200, 'description' => 'Standard entry to the main arena with dynamic sound and visuals experience.'],
    'VP' => ['name' => 'VIP Package', 'price' => 3500, 'available_seats' => 50, 'description' => 'Exclusive front rows access, official event lanyard, and 1 free beverage.'],
    'VV' => ['name' => 'VVIP Package', 'price' => 5000, 'available_seats' => 30, 'description' => 'Backstage pass, meet & greet session, premium front lounge seating, and exclusive merch bundle.']
];

try {
    $db = DB::getConnection();
    $stmt = $db->query("SELECT * FROM packages");
    while ($row = $stmt->fetch()) {
        $packages[$row['code']] = $row;
    }
} catch (Exception $e) {
    // Fail silently, use fallback defaults defined above
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>INZANITY | HipHop Concert</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts: Syne & Outfit -->
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
        *, *::before, *::after { box-sizing: border-box; }
        html, body { max-width: 100%; overflow-x: hidden; }
        .neon-glow {
            text-shadow: 0 0 10px rgba(124, 58, 237, 0.6), 0 0 20px rgba(124, 58, 237, 0.4);
        }
        .neon-border {
            box-shadow: 0 0 15px rgba(124, 58, 237, 0.2);
        }
        .glass-panel {
            background: rgba(13, 10, 25, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .glass-modal {
            background: rgba(8, 6, 16, 0.95);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }
        .hero-bg-strip {
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 100%;
            display: flex;
            gap: 3px;
            overflow: hidden;
            pointer-events: none;
            opacity: 0.55;
            filter: blur(1px);
        }
        .hero-bg-strip img {
            flex: 1 1 0;
            min-width: 0;
            height: 100%;
            object-fit: cover;
        }
    </style>
</head>
<body class="bg-darkBg text-gray-100 font-sans min-h-screen selection:bg-accentViolet selection:text-white" style="overflow-x:hidden;max-width:100vw;">

    <!-- Radial Background Gradient for Concert Vibe -->
    <div class="fixed inset-0 pointer-events-none z-0 bg-[radial-gradient(circle_at_top_right,rgba(124,58,237,0.15),transparent_45%)] bg-[radial-gradient(circle_at_bottom_left,rgba(76,29,149,0.15),transparent_50%)]"></div>

    <!-- Navigation Header -->
    <header class="relative z-50 glass-panel border-b border-white/5 sticky top-0">
        <div class="max-w-6xl mx-auto px-3 sm:px-4 py-4 flex items-center justify-between">
            <a href="index.php" class="font-syne text-xl sm:text-2xl font-extrabold tracking-wider text-white neon-glow">
                INZANITY
            </a>
            <div class="flex items-center gap-2 sm:gap-4">
                <a href="seats.php" class="px-3 py-1.5 sm:px-4 sm:py-2 rounded-full border border-accentViolet/50 bg-accentViolet/10 hover:bg-accentViolet/25 text-accentNeon text-xs sm:text-sm font-semibold transition-all duration-300 whitespace-nowrap">
                    Receive Your Seat
                </a>
            </div>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="relative z-10 w-full overflow-hidden min-h-[85vh] flex flex-col items-center justify-center text-center px-5 py-16">
        <!-- Visual Grid Overlay -->
        <div class="absolute inset-0 bg-[linear-gradient(rgba(255,255,255,0.01)_1px,transparent_1px),linear-gradient(90deg,rgba(255,255,255,0.01)_1px,transparent_1px)] bg-[size:40px_40px] pointer-events-none opacity-50"></div>

        <!-- Big Concert Images Collage/Background Grid -->
        <div class="hero-bg-strip">
            <img src="assests/images (11).jpg" alt=""  class="hidden sm:block">
              <img src="assests/images (15).jpg" alt=""   class="hidden sm:block">
            <img src="assests/images (12).jpg" alt="" >

            <img src="assests/images (13).jpg" alt="">
            <img src="assests/channels4_profile.jpg" alt="">
            <img src="assests/images (14).jpg" alt="" class="hidden sm:block">
          
        </div>

        <!-- Dark gradient overlay so text stays readable -->
        <div class="absolute inset-0 bg-gradient-to-b from-black/60 via-black/30 to-black/60 pointer-events-none"></div>

        <div class="relative w-full max-w-2xl mx-auto">
            <span class="inline-block text-accentNeon font-semibold tracking-widest text-[9px] sm:text-xs uppercase bg-accentViolet/20 px-3 py-1.5 rounded-full border border-accentViolet/30 mb-5">
               Zany Insane Presents 
            </span>
            <h1 class="font-syne text-[2.6rem] min-[390px]:text-5xl sm:text-7xl md:text-8xl font-black tracking-tight text-white mb-3 uppercase leading-[0.95]">
                INZANITY
            </h1>
            <p class="text-accentNeon font-syne text-xs min-[390px]:text-sm sm:text-xl md:text-2xl font-extrabold tracking-widest uppercase mb-5">
                Performs by Zany Insane
            </p>
            <p class="text-sm sm:text-base md:text-lg text-gray-300 font-light mx-auto mb-8 leading-relaxed max-w-xs sm:max-w-md">
                Experience the raw energy of the year's biggest hiphop &amp; rap concert. High bass, neon lights, and insane performances.
            </p>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-center gap-3 w-full">
                <a href="#packages" class="w-full sm:w-auto px-8 py-4 bg-accentViolet hover:bg-violet-500 text-white font-bold text-base rounded-xl shadow-lg shadow-accentViolet/30 hover:shadow-accentViolet/50 transition-all duration-300 hover:-translate-y-1 text-center">
                    Book Tickets Now
                </a>
                <a href="seats.php" class="w-full sm:w-auto px-8 py-4 bg-white/5 hover:bg-white/10 text-white font-semibold text-base rounded-xl border border-white/10 transition-all duration-300 text-center">
                    Receive Your Seat
                </a>
            </div>
        </div>
    </section>

    <!-- Packages Section -->
    <section id="packages" class="relative z-10 max-w-6xl mx-auto px-4 py-20">
        <div class="text-center mb-16">
            <h2 class="font-syne text-4xl md:text-5xl font-extrabold text-white mb-4 uppercase">
                Choose Your Experience
            </h2>
            <p class="text-gray-400 max-w-lg mx-auto">
                Secure your spot today. Limited capacity for VVIP & VIP areas to ensure premium vibing.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-stretch">
            <?php 
            $packageImages = [
                'GN' => 'assests/images (14).jpg',
                'VP' => 'assests/images (11).jpg',
                'VV' => 'assests/images (15).jpg'
            ];
            foreach ($packages as $code => $pkg): 
            ?>
                <?php
                // Design settings based on package
                $accentColor = 'text-accentNeon';
                $borderColor = 'border-white/5 hover:border-accentViolet/30';
                $btnColor = 'bg-white/10 hover:bg-white/20 text-white';
                $badge = '';

                if ($code === 'VP') {
                    $accentColor = 'text-violet-400';
                    $borderColor = 'border-violet-500/30 hover:border-violet-500/70 shadow-lg shadow-violet-500/5';
                    $btnColor = 'bg-accentViolet hover:bg-violet-500 text-white';
                    $badge = '<span class="z-10  absolute top-4 right-4 px-3 py-1 bg-violet-500/25 border border-violet-500/40 text-violet-300 text-xs font-semibold rounded-full uppercase">Most Popular</span>';
                } elseif ($code === 'VV') {
                    $accentColor = 'text-amber-400';
                    $borderColor = 'border-amber-500/20 hover:border-amber-500/50 shadow-lg shadow-amber-500/5';
                    $btnColor = 'bg-gradient-to-r from-amber-500 to-violet-600 hover:from-amber-400 hover:to-violet-500 text-black font-extrabold';
                    $badge = '<span class="z-10  absolute top-4 right-4 px-3 py-1 bg-amber-500/20 border border-amber-500/40 text-amber-300 text-xs font-semibold rounded-full uppercase opacity-100">Ultra Premium</span>';
                }
                ?>
                <div class="relative flex flex-col justify-between rounded-3xl glass-panel p-6 sm:p-8 border <?php echo $borderColor; ?> transition-all duration-300 hover:-translate-y-2">
                    <?php echo $badge; ?>
                    <div>
                        <!-- Package Card Image Banner -->
                        <div class="h-44 sm:h-48 w-full overflow-hidden rounded-2xl mb-6 relative">
                            <img src="<?php echo $packageImages[$code]; ?>" alt="<?php echo htmlspecialchars($pkg['name']); ?>" class="w-full h-full object-cover filter saturate-125">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent"></div>
                        </div>
                        <span class="text-sm font-semibold tracking-widest text-gray-500 uppercase"><?php echo $code; ?> Pass</span>
                        <h3 class="font-syne text-3xl font-extrabold text-white mt-1 mb-4"><?php echo htmlspecialchars($pkg['name']); ?></h3>
                        <div class="flex items-baseline gap-1 mb-6">
                            <span class="text-5xl font-black text-white">Rs. <?php echo number_format($pkg['price'], 0); ?></span>
                        </div>
                        <p class="text-gray-400 text-sm leading-relaxed mb-6">
                            <?php echo htmlspecialchars($pkg['description']); ?>
                        </p>
                    </div>

                    <div>
                        <button 
                            onclick="openBookingModal('<?php echo $code; ?>', '<?php echo htmlspecialchars($pkg['name']); ?>', <?php echo $pkg['price']; ?>, <?php echo $pkg['available_seats']; ?>)"
                            class="w-full py-4 px-6 rounded-xl font-bold transition-all duration-300 <?php echo $btnColor; ?>"
                            <?php echo $pkg['available_seats'] <= 0 ? 'disabled' : ''; ?>>
                            <?php echo $pkg['available_seats'] > 0 ? 'Book Your Seat' : 'Sold Out'; ?>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Booking Modal (Creative Pop-Up) -->
    <div id="bookingModal" class="fixed inset-0 z-50 items-center justify-center p-4 hidden">
        <!-- Backdrop Blur -->
        <div class="absolute inset-0 bg-black/80 backdrop-blur-md" onclick="closeBookingModal()"></div>
        
        <!-- Modal Content Container -->
        <div class="relative w-full max-w-xl rounded-3xl glass-modal border border-white/10 p-6 md:p-8 overflow-y-auto max-h-[90vh] shadow-2xl z-10 animate-in fade-in zoom-in duration-300">
            <!-- Close Button -->
            <button onclick="closeBookingModal()" class="absolute top-4 right-4 text-gray-400 hover:text-white bg-white/5 hover:bg-white/10 p-2 rounded-full transition-all">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <!-- Modal Header -->
            <div class="mb-6">
                <span class="text-xs font-semibold tracking-wider text-accentNeon uppercase">Secure Entry Pass</span>
                <h3 id="modalTitle" class="font-syne text-2xl md:text-3xl font-extrabold text-white mt-1">Book Ticket</h3>
                <p id="modalSub" class="text-sm text-gray-400 mt-1"></p>
            </div>

            <!-- Booking Form -->
            <form id="bookingForm" onsubmit="submitBooking(event)" enctype="multipart/form-data">
                <input type="hidden" name="package_code" id="inputPackageCode">
                
                <div class="space-y-4">
                    <!-- Full Name -->
                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-400 mb-1.5" for="name">Your Name</label>
                        <input type="text" name="name" id="name" required placeholder="e.g. John Doe" 
                               class="w-full bg-white/5 border border-white/10 focus:border-accentViolet focus:ring-1 focus:ring-accentViolet rounded-xl px-4 py-3 text-white placeholder-gray-500 outline-none transition-all">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- NIC -->
                        <div>
                            <label class="block text-xs font-semibold uppercase text-gray-400 mb-1.5" for="nic">NIC Number</label>
                            <input type="text" name="nic" id="nic" required placeholder="e.g. 199912345678" 
                                   class="w-full bg-white/5 border border-white/10 focus:border-accentViolet focus:ring-1 focus:ring-accentViolet rounded-xl px-4 py-3 text-white placeholder-gray-500 outline-none transition-all">
                        </div>

                        <!-- WhatsApp -->
                        <div>
                            <label class="block text-xs font-semibold uppercase text-gray-400 mb-1.5" for="whatsapp">WhatsApp Number</label>
                            <input type="tel" name="whatsapp" id="whatsapp" required placeholder="e.g. 0778214024" 
                                   class="w-full bg-white/5 border border-white/10 focus:border-accentViolet focus:ring-1 focus:ring-accentViolet rounded-xl px-4 py-3 text-white placeholder-gray-500 outline-none transition-all">
                        </div>
                    </div>

                    <!-- Creative Seat Picker (+ / - Button) -->
                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-400 mb-1.5">How many seats?</label>
                        <div class="flex items-center gap-3 bg-white/5 border border-white/10 rounded-2xl p-2 max-w-[200px]">
                            <button type="button" onclick="changeSeats(-1)" class="w-12 h-12 flex items-center justify-center text-white bg-white/5 hover:bg-accentViolet hover:text-white rounded-xl text-2xl font-bold transition-all select-none">-</button>
                            <input type="number" name="seats" id="seatsVal" value="1" min="1" max="10" readonly 
                                   class="w-12 text-center text-xl font-bold text-white bg-transparent border-none outline-none select-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                            <button type="button" onclick="changeSeats(1)" class="w-12 h-12 flex items-center justify-center text-white bg-white/5 hover:bg-accentViolet hover:text-white rounded-xl text-2xl font-bold transition-all select-none">+</button>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Maximum 10 tickets per booking.</p>
                    </div>

                    <!-- Total Amount Display -->
                    <div class="bg-accentViolet/10 border border-accentViolet/25 rounded-2xl p-4 flex items-center justify-between">
                        <span class="text-sm font-semibold text-gray-300">Total Payable:</span>
                        <span id="totalAmountText" class="text-2xl font-extrabold text-white">Rs. 0</span>
                    </div>

                    <!-- Bank Details Section -->
                    <div class="bg-black/40 border border-white/5 rounded-2xl p-4">
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-xs font-semibold uppercase tracking-wider text-accentNeon">Bank Transfer Details</span>
                            <span class="text-[10px] text-gray-500">Copy details below</span>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between items-center py-1 border-b border-white/5">
                                <span class="text-gray-400">Bank:</span>
                                <span class="font-semibold text-white select-all">Bank of Ceylon (BOC)</span>
                            </div>
                            <div class="flex justify-between items-center py-1 border-b border-white/5">
                                <span class="text-gray-400">Account Name:</span>
                                <span class="font-semibold text-white select-all">H. Pubudu Eranga</span>
                            </div>
                            <div class="flex justify-between items-center py-1 border-b border-white/5">
                                <span class="text-gray-400">Account No:</span>
                                <div class="flex items-center gap-1.5">
                                    <span class="font-semibold text-white select-all" id="bankAcc">0778214024</span>
                                    <button type="button" onclick="copyAccountNo()" class="text-accentNeon hover:text-white text-xs">Copy</button>
                                </div>
                            </div>
                            <div class="flex justify-between items-center py-1">
                                <span class="text-gray-400">WhatsApp Support:</span>
                                <span class="font-semibold text-white">0778214024</span>
                            </div>
                        </div>
                    </div>

                    <!-- Upload Receipt Section -->
                    <div>
                        <label class="block text-xs font-semibold uppercase text-gray-400 mb-1.5">Upload Payment Receipt</label>
                        <div class="relative border-2 border-dashed border-white/10 hover:border-accentViolet/50 rounded-2xl p-6 text-center cursor-pointer transition-all bg-white/5" id="dropzone" onclick="document.getElementById('receipt').click()">
                            <input type="file" name="receipt" id="receipt" required accept="image/*,application/pdf" class="hidden" onchange="handleFileSelected(this)">
                            <div id="dropzonePrompt" class="space-y-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-8 w-8 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span class="block text-sm font-semibold text-gray-300">Tap to upload receipt image</span>
                                <span class="block text-xs text-gray-500">Supports JPG, PNG, WEBP, PDF</span>
                            </div>
                            <div id="dropzoneFeedback" class="hidden space-y-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-8 w-8 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span id="fileName" class="block text-sm font-semibold text-emerald-400 truncate"></span>
                                <span class="block text-xs text-gray-400">Click to change receipt</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="mt-8">
                    <button type="submit" id="submitBtn" class="w-full bg-accentViolet hover:bg-violet-500 text-white font-bold py-4 rounded-xl shadow-lg transition-all duration-300">
                        Confirm Registration
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Success Modal -->
    <div id="successModal" class="fixed inset-0 z-50 items-center justify-center p-4 hidden">
        <div class="absolute inset-0 bg-black/90 backdrop-blur-md"></div>
        <div class="relative w-full max-w-md rounded-3xl glass-modal border border-emerald-500/20 p-6 md:p-8 text-center shadow-2xl z-10">
            <div class="w-16 h-16 bg-emerald-500/10 border border-emerald-500/30 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h3 class="font-syne text-2xl font-black text-white mb-2">Booking Submitted!</h3>
            <p class="text-sm text-gray-400 mb-6">
                Your payment receipt is uploaded. Our team is verifying your registration. Save your Reference Code below.
            </p>

            <div class="bg-emerald-500/5 border border-emerald-500/25 rounded-2xl p-4 mb-6">
                <span class="text-xs text-gray-400 block uppercase font-semibold">Your Reference Code</span>
                <span id="successRef" class="text-3xl font-syne font-black text-emerald-400 block tracking-widest mt-1">VP1002</span>
            </div>

            <div class="space-y-3">
                <a href="seats.php" class="block w-full py-3.5 bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl text-white font-semibold text-sm transition-all">
                    Track Seat Status
                </a>
                <button onclick="closeSuccessModal()" class="block w-full text-xs text-gray-500 hover:text-white transition-all">
                    Close Window
                </button>
            </div>
        </div>
    </div>

    <!-- Footer Section -->
    <footer class="relative z-10 border-t border-white/5 bg-black/60 backdrop-blur-md mt-20">
        <div class="max-w-6xl mx-auto px-4 py-12">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Branding -->
                <div>
                    <h4 class="font-syne text-xl font-black text-white tracking-widest uppercase mb-4">INZANITY</h4>
                    <p class="text-sm text-gray-400 leading-relaxed max-w-sm">
                        Witness the loudest bass, fastest flows, and pure hiphop underground culture. 
                    </p>
                </div>
                <!-- Contact Details -->
                <div>
                    <h4 class="font-syne text-sm font-semibold tracking-wider text-accentNeon uppercase mb-4">Concert Details</h4>
                    <p class="text-sm text-gray-300 mb-2">Organizer: <strong class="text-white">H. Pubudu Eranga</strong></p>
                    <p class="text-sm text-gray-300 mb-2">Email: <a href="mailto:Zanyinsane20@gmail.com" class="text-accentNeon hover:underline">Zanyinsane20@gmail.com</a></p>
                    <p class="text-sm text-gray-300">Hotline: 0778214024 / 0781395267</p>
                </div>
                <!-- Quick Info -->
                <div>
                    <h4 class="font-syne text-sm font-semibold tracking-wider text-accentNeon uppercase mb-4">Gate Times</h4>
                    <p class="text-sm text-gray-400 mb-2">Gates Open: 05:30 PM onwards</p>
                </div>
            </div>
            
            <div class="border-t border-white/5 mt-10 pt-6 flex flex-col sm:flex-row items-center justify-between text-xs text-gray-500">
                <span>&copy; 2026 INZANITY HipHop Concert. All rights reserved.</span>
                <div class="flex items-center gap-4 mt-2 sm:mt-0">
                    <span>Powered by Zanyinsane Productions</span>
                    <a href="admin/login.php" class="inline-flex items-center gap-1.5 text-gray-600 hover:text-accentNeon transition-colors duration-200 group">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 group-hover:text-accentNeon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                        System Login
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Interactive JS -->
    <script>
        let currentPackageCode = '';
        let currentPrice = 0;
        let maxSeats = 10;

        function openBookingModal(code, name, price, available) {
            currentPackageCode = code;
            currentPrice = price;
            maxSeats = Math.min(10, available);

            document.getElementById('inputPackageCode').value = code;
            document.getElementById('modalTitle').innerText = name;
            document.getElementById('modalSub').innerText = 'Ticket price: Rs. ' + price.toLocaleString();
            
            // Reset fields
            document.getElementById('bookingForm').reset();
            document.getElementById('seatsVal').value = 1;
            document.getElementById('dropzonePrompt').classList.remove('hidden');
            document.getElementById('dropzoneFeedback').classList.add('hidden');
            
            updateTotal();
            
            const modal = document.getElementById('bookingModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeBookingModal() {
            const modal = document.getElementById('bookingModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }

        function changeSeats(amount) {
            const input = document.getElementById('seatsVal');
            let val = parseInt(input.value) + amount;
            if (val < 1) val = 1;
            if (val > maxSeats) val = maxSeats;
            input.value = val;
            updateTotal();
        }

        function updateTotal() {
            const seats = parseInt(document.getElementById('seatsVal').value) || 1;
            const total = seats * currentPrice;
            document.getElementById('totalAmountText').innerText = 'Rs. ' + total.toLocaleString();
        }

        function handleFileSelected(input) {
            const feedback = document.getElementById('dropzoneFeedback');
            const prompt = document.getElementById('dropzonePrompt');
            const nameSpan = document.getElementById('fileName');

            if (input.files && input.files[0]) {
                const file = input.files[0];
                nameSpan.innerText = file.name;
                prompt.classList.add('hidden');
                feedback.classList.remove('hidden');
            }
        }

        function copyAccountNo() {
            const acc = document.getElementById('bankAcc').innerText;
            navigator.clipboard.writeText(acc).then(() => {
                alert('Account number copied!');
            });
        }

        function submitBooking(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerText = 'Processing registration...';

            const form = document.getElementById('bookingForm');
            const formData = new FormData(form);

            fetch('process_booking.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.innerText = 'Confirm Registration';
                
                if (data.success) {
                    closeBookingModal();
                    
                    // Show success
                    document.getElementById('successRef').innerText = data.booking_ref;
                    const successModal = document.getElementById('successModal');
                    successModal.classList.remove('hidden');
                    successModal.classList.add('flex');
                    document.body.classList.add('overflow-hidden');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                submitBtn.disabled = false;
                submitBtn.innerText = 'Confirm Registration';
                alert('Something went wrong. Please check connection and try again.');
            });
        }

        function closeSuccessModal() {
            const modal = document.getElementById('successModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
            window.location.reload();
        }
    </script>
</body>
</html>
