<?php
// admin/login.php - Admin Login Page
session_start();

// If already logged in, redirect to dashboard
if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: dashboard.php');
    exit;
}

require_once '../db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        try {
            $db = DB::getConnection();
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $user['username'];
                $_SESSION['admin_id'] = $user['id'];
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } catch (Exception $e) {
            $error = 'Login failed. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>INZANITY | Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
        .glass-card {
            background: rgba(13, 10, 25, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(167, 139, 250, 0.15);
            box-shadow: 0 0 60px rgba(124, 58, 237, 0.15), 0 25px 50px rgba(0,0,0,0.5);
        }
        .input-field {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(167, 139, 250, 0.2);
            color: #fff;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .input-field:focus {
            outline: none;
            border-color: rgba(167, 139, 250, 0.6);
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.15);
        }
        .input-field::placeholder { color: rgba(255,255,255,0.3); }
        .login-btn {
            background: linear-gradient(135deg, #7c3aed, #5b21b6);
            transition: all 0.2s;
        }
        .login-btn:hover {
            background: linear-gradient(135deg, #8b5cf6, #6d28d9);
            box-shadow: 0 0 25px rgba(124, 58, 237, 0.5);
            transform: translateY(-1px);
        }
        .login-btn:active { transform: translateY(0); }

        /* Animated background orbs */
        .orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.25;
            pointer-events: none;
            animation: float 8s ease-in-out infinite;
        }
        .orb-1 { width: 500px; height: 500px; background: #7c3aed; top: -150px; right: -150px; animation-delay: 0s; }
        .orb-2 { width: 400px; height: 400px; background: #4c1d95; bottom: -100px; left: -100px; animation-delay: -4s; }
        @keyframes float {
            0%, 100% { transform: translateY(0px) scale(1); }
            50% { transform: translateY(-30px) scale(1.05); }
        }
    </style>
</head>
<body class="bg-darkBg text-gray-100 font-sans min-h-screen flex items-center justify-center">

    <!-- Background orbs -->
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>

    <!-- Background grid -->
    <div class="fixed inset-0 pointer-events-none z-0" style="background-image: linear-gradient(rgba(124,58,237,0.04) 1px, transparent 1px), linear-gradient(90deg, rgba(124,58,237,0.04) 1px, transparent 1px); background-size: 50px 50px;"></div>

    <div class="relative z-10 w-full max-w-md mx-auto px-4 py-8">

        <!-- Logo -->
        <div class="text-center mb-8">
            <a href="../index.php" class="font-syne text-3xl font-extrabold tracking-widest text-white neon-glow">
                INZANITY
            </a>
            <p class="text-xs text-accentNeon tracking-widest uppercase mt-2 font-semibold">System Administration</p>
        </div>

        <!-- Login Card -->
        <div class="glass-card rounded-3xl p-8">
            <div class="mb-7">
                <h1 class="font-syne text-xl font-bold text-white mb-1">Welcome back</h1>
                <p class="text-sm text-gray-400">Sign in to access the admin panel.</p>
            </div>

            <?php if (!empty($error)): ?>
                <div id="errorAlert" class="mb-5 p-4 bg-rose-500/10 border border-rose-500/30 rounded-2xl flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-rose-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span class="text-sm text-rose-300"><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" id="loginForm">

                <!-- Username -->
                <div class="mb-5">
                    <label for="username" class="block text-xs font-semibold text-accentNeon uppercase tracking-wider mb-2">Username</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </span>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            placeholder="Enter username"
                            class="input-field w-full rounded-xl px-4 py-3 pl-11 text-sm"
                            value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                            required
                            autocomplete="username"
                        >
                    </div>
                </div>

                <!-- Password -->
                <div class="mb-7">
                    <label for="password" class="block text-xs font-semibold text-accentNeon uppercase tracking-wider mb-2">Password</label>
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </span>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter password"
                            class="input-field w-full rounded-xl px-4 py-3 pl-11 pr-11 text-sm"
                            required
                            autocomplete="current-password"
                        >
                        <button type="button" onclick="togglePassword()" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-accentNeon transition-colors" id="eyeBtn">
                            <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Submit -->
                <button type="submit" id="loginBtn" class="login-btn w-full py-3.5 rounded-xl text-white font-bold text-sm tracking-wider uppercase">
                    Sign In to Dashboard
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="../index.php" class="text-xs text-gray-500 hover:text-accentNeon transition-colors">
                    ← Back to Concert Page
                </a>
            </div>
        </div>

        <p class="text-center text-xs text-gray-600 mt-6">© 2026 INZANITY. Restricted Access.</p>
    </div>

    <script>
        function togglePassword() {
            const pwField = document.getElementById('password');
            const icon = document.getElementById('eyeIcon');
            if (pwField.type === 'password') {
                pwField.type = 'text';
                icon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />`;
            } else {
                pwField.type = 'password';
                icon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />`;
            }
        }

        // Add loading state on submit
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            btn.disabled = true;
            btn.innerText = 'Signing in...';
        });
    </script>
</body>
</html>
