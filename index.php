<?php
require_once 'db.php';

// Fetch settings
$settingsStmt = $pdo->query("SELECT setting_key, setting_value FROM store_settings");
$settingsData = $settingsStmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Fetch promos
$promosStmt = $pdo->query("SELECT * FROM promos WHERE is_active = 1 AND valid_until >= NOW()");
$promos = $promosStmt->fetchAll();

// Fetch branches
$branchesStmt = $pdo->query("SELECT * FROM branches WHERE is_active = 1");
$branches = $branchesStmt->fetchAll();

// Fetch social links
$socialStmt = $pdo->query("SELECT * FROM social_links");
$socialLinks = $socialStmt->fetchAll();

// Helper for social icons
function getSocialIcon($platform) {
    $icons = [
        'facebook' => 'fa-facebook-f',
        'instagram' => 'fa-instagram',
        'twitter' => 'fa-twitter',
        'tiktok' => 'fa-tiktok',
        'youtube' => 'fa-youtube'
    ];
    return $icons[strtolower($platform)] ?? 'fa-share-nodes';
}

// Fetch schedule and determine status
date_default_timezone_set('Asia/Manila'); 
$currentDay = date('w'); // 0 (for Sunday) through 6 (for Saturday)
$currentTime = date('H:i:s');

$scheduleStmt = $pdo->prepare("SELECT * FROM store_schedule WHERE day_of_week = ?");
$scheduleStmt->execute([$currentDay]);
$todaySchedule = $scheduleStmt->fetch();

$isOpen = false;
$statusMessage = "Closed";

if ($todaySchedule && $todaySchedule['is_open']) {
    if ($currentTime >= $todaySchedule['open_time'] && $currentTime <= $todaySchedule['close_time']) {
        $isOpen = true;
        $statusMessage = "Open Now";
    } else {
        $statusMessage = "Closed (Opens at " . date("g:i A", strtotime($todaySchedule['open_time'])) . ( $currentTime > $todaySchedule['close_time'] ? " tomorrow" : "") . ")";
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($settingsData['store_name'] ?? 'Archer Grocery') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.3);
        }
        .dark .glass {
            background: rgba(17, 24, 39, 0.8);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50 text-gray-900 dark:bg-gray-900 dark:text-gray-100 transition-colors duration-300">

    <!-- Header -->
    <header class="fixed w-full z-50 glass transition-all duration-300" id="header">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <div class="flex-shrink-0 flex items-center">
                    <a href="#" class="text-2xl font-bold text-primary-600 dark:text-primary-500 tracking-tighter">
                        <i class="fa-solid fa-leaf mr-2"></i><?= htmlspecialchars($settingsData['store_name'] ?? 'Archer Grocery') ?>
                    </a>
                </div>
                
                <!-- Desktop Nav -->
                <nav class="hidden md:flex space-x-8 items-center">
                    <a href="#home" class="text-gray-700 hover:text-primary-600 dark:text-gray-300 dark:hover:text-primary-400 font-medium transition-colors">Home</a>
                    <a href="#about" class="text-gray-700 hover:text-primary-600 dark:text-gray-300 dark:hover:text-primary-400 font-medium transition-colors">About Us</a>
                    <?php if (!empty($promos)): ?>
                    <a href="#promos" class="text-gray-700 hover:text-primary-600 dark:text-gray-300 dark:hover:text-primary-400 font-medium transition-colors">Promos</a>
                    <?php endif; ?>
                    <a href="#branches" class="text-gray-700 hover:text-primary-600 dark:text-gray-300 dark:hover:text-primary-400 font-medium transition-colors">Branches</a>
                    <a href="#contact" class="text-gray-700 hover:text-primary-600 dark:text-gray-300 dark:hover:text-primary-400 font-medium transition-colors">Contact</a>
                    
                    <!-- Status Badge -->
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold shadow-sm <?= $isOpen ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' ?>">
                        <span class="w-2 h-2 rounded-full mr-2 <?= $isOpen ? 'bg-green-500 animate-pulse' : 'bg-red-500' ?>"></span>
                        <?= $statusMessage ?>
                    </span>

                    <!-- Dark Mode Toggle -->
                    <button id="theme-toggle" class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors focus:outline-none">
                        <i id="theme-toggle-dark-icon" class="hidden fa-solid fa-moon text-gray-500 dark:text-gray-400 text-xl"></i>
                        <i id="theme-toggle-light-icon" class="hidden fa-solid fa-sun text-yellow-500 text-xl"></i>
                    </button>
                </nav>

                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center gap-4">
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold <?= $isOpen ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                        <span class="w-1.5 h-1.5 rounded-full mr-1 <?= $isOpen ? 'bg-green-500' : 'bg-red-500' ?>"></span>
                        <?= $isOpen ? 'Open' : 'Closed' ?>
                    </span>
                    <button id="mobile-menu-btn" class="text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white focus:outline-none">
                        <i class="fa-solid fa-bars text-2xl"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-800 shadow-lg">
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                <a href="#home" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-primary-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800">Home</a>
                <a href="#about" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-primary-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800">About Us</a>
                <?php if (!empty($promos)): ?>
                <a href="#promos" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-primary-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800">Promos</a>
                <?php endif; ?>
                <a href="#branches" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-primary-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800">Branches</a>
                <a href="#contact" class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-primary-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800">Contact</a>
                
                <div class="px-3 py-2 flex items-center justify-between">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Theme</span>
                    <button id="theme-toggle-mobile" class="p-2 rounded-full hover:bg-gray-200 dark:hover:bg-gray-800 transition-colors focus:outline-none">
                        <i id="theme-toggle-dark-icon-mobile" class="hidden fa-solid fa-moon text-gray-500 dark:text-gray-400"></i>
                        <i id="theme-toggle-light-icon-mobile" class="hidden fa-solid fa-sun text-yellow-500"></i>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <main>
        <!-- Hero Section -->
        <section id="home" class="relative h-screen flex items-center justify-center pt-20">
            <div class="absolute inset-0 z-0">
                <img src="assets/images/<?= htmlspecialchars($settingsData['hero_image'] ?? 'hero.png') ?>" alt="Grocery Store" class="w-full h-full object-cover" onerror="this.src='https://images.unsplash.com/photo-1542838132-92c53300491e?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80'">
                <div class="absolute inset-0 bg-black/60 dark:bg-black/70 mix-blend-multiply"></div>
            </div>
            <div class="relative z-10 text-center px-4 sm:px-6 lg:px-8 max-w-5xl mx-auto">
                <h1 class="text-5xl md:text-7xl font-extrabold text-white tracking-tight mb-6 drop-shadow-lg">
                    <?= htmlspecialchars($settingsData['hero_title'] ?? 'Welcome to Archer Grocery') ?>
                </h1>
                <p class="mt-4 text-xl md:text-2xl text-gray-200 max-w-3xl mx-auto font-light drop-shadow">
                    <?= htmlspecialchars($settingsData['hero_subtitle'] ?? 'Locally sourced, organic, and everyday essentials delivered to your community.') ?>
                </p>
                <div class="mt-10 flex justify-center gap-4">
                    <a href="#about" class="px-8 py-4 bg-primary-600 hover:bg-primary-700 text-white rounded-full font-semibold transition-all transform hover:scale-105 shadow-lg hover:shadow-primary-500/30">
                        Explore Store
                    </a>
                    <?php if (!empty($promos)): ?>
                    <a href="#promos" class="px-8 py-4 bg-white/10 hover:bg-white/20 backdrop-blur-md text-white rounded-full font-semibold transition-all border border-white/30 transform hover:scale-105">
                        View Promos
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="absolute bottom-10 left-1/2 transform -translate-x-1/2 animate-bounce">
                <a href="#about" class="text-white/70 hover:text-white transition-colors">
                    <i class="fa-solid fa-chevron-down text-3xl drop-shadow-md"></i>
                </a>
            </div>
        </section>

        <!-- About Section -->
        <section id="about" class="py-24 bg-white dark:bg-gray-800 transition-colors duration-300 relative overflow-hidden">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="lg:grid lg:grid-cols-2 lg:gap-16 items-center">
                    <div class="mb-12 lg:mb-0 relative group">
                        <div class="absolute -inset-4 bg-gradient-to-r from-primary-500 to-green-300 rounded-2xl opacity-30 group-hover:opacity-50 blur-lg transition duration-1000 group-hover:duration-200"></div>
                        <img src="assets/images/<?= htmlspecialchars($settingsData['about_image'] ?? 'about.png') ?>" alt="Our Story" class="relative rounded-2xl shadow-2xl object-cover h-[500px] w-full transform transition duration-500 group-hover:scale-[1.02]" onerror="this.src='https://images.unsplash.com/photo-1578916171728-46686eac8d58?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'">
                    </div>
                    <div>
                        <div class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold bg-primary-100 text-primary-800 dark:bg-primary-900/30 dark:text-primary-400 mb-6">
                            <i class="fa-solid fa-store mr-2"></i> About Us
                        </div>
                        <h2 class="text-4xl font-extrabold text-gray-900 dark:text-white mb-6">
                            <?= htmlspecialchars($settingsData['about_title'] ?? 'Our Story') ?>
                        </h2>
                        <div class="prose prose-lg dark:prose-invert text-gray-600 dark:text-gray-300 mb-8 leading-relaxed">
                            <p><?= nl2br(htmlspecialchars($settingsData['about_text'] ?? '')) ?></p>
                        </div>
                        <div class="grid grid-cols-2 gap-6 mt-8 border-t border-gray-100 dark:border-gray-700 pt-8">
                            <div>
                                <h4 class="text-3xl font-bold text-primary-600 dark:text-primary-400">25+</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Years of Service</p>
                            </div>
                            <div>
                                <h4 class="text-3xl font-bold text-primary-600 dark:text-primary-400">10k+</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Fresh Products</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Promos Section -->
        <?php if (!empty($promos)): ?>
        <section id="promos" class="py-24 bg-gray-50 dark:bg-gray-900 transition-colors duration-300">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <h2 class="text-4xl font-extrabold text-gray-900 dark:text-white mb-4">Special Offers</h2>
                    <p class="text-lg text-gray-600 dark:text-gray-400">Discover our latest deals and seasonal discounts exclusively for our community.</p>
                </div>
                
                <div class="grid md:grid-cols-2 gap-10">
                    <?php foreach ($promos as $promo): ?>
                    <div class="group bg-white dark:bg-gray-800 rounded-3xl overflow-hidden shadow-xl hover:shadow-2xl transition-all duration-300 border border-gray-100 dark:border-gray-700 flex flex-col sm:flex-row">
                        <div class="sm:w-2/5 h-64 sm:h-auto relative overflow-hidden">
                            <img src="assets/images/<?= htmlspecialchars($promo['image_url']) ?>" alt="<?= htmlspecialchars($promo['title']) ?>" class="w-full h-full object-cover transform transition duration-700 group-hover:scale-110" onerror="this.src='https://images.unsplash.com/photo-1608686207856-001b95cf60ca?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80'">
                            <div class="absolute top-4 left-4 bg-red-500 text-white text-xs font-bold px-3 py-1 rounded-full uppercase tracking-wide shadow-lg">Promo</div>
                        </div>
                        <div class="p-8 sm:w-3/5 flex flex-col justify-center">
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-3 group-hover:text-primary-600 dark:group-hover:text-primary-400 transition-colors"><?= htmlspecialchars($promo['title']) ?></h3>
                            <p class="text-gray-600 dark:text-gray-300 mb-6"><?= htmlspecialchars($promo['description']) ?></p>
                            <div class="mt-auto flex items-center text-sm text-gray-500 dark:text-gray-400">
                                <i class="fa-regular fa-clock mr-2"></i> Valid until <?= date('M d, Y', strtotime($promo['valid_until'])) ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Branches Section -->
        <section id="branches" class="py-24 bg-white dark:bg-gray-800 transition-colors duration-300">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center max-w-3xl mx-auto mb-16">
                    <h2 class="text-4xl font-extrabold text-gray-900 dark:text-white mb-4">Our Locations</h2>
                    <p class="text-lg text-gray-600 dark:text-gray-400">Find the nearest Archer Grocery branch to your neighborhood.</p>
                </div>

                <div class="grid lg:grid-cols-2 gap-12">
                    <?php foreach ($branches as $branch): ?>
                    <div class="bg-gray-50 dark:bg-gray-900 rounded-3xl overflow-hidden shadow-lg border border-gray-100 dark:border-gray-700 hover:shadow-xl transition-shadow duration-300">
                        <div class="h-64 w-full bg-gray-200 dark:bg-gray-700">
                            <!-- Map Embed -->
                            <?= $branch['google_maps_embed'] ?>
                        </div>
                        <div class="p-8">
                            <div class="flex items-start">
                                <div class="flex-shrink-0 mt-1">
                                    <div class="w-10 h-10 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center text-primary-600 dark:text-primary-400">
                                        <i class="fa-solid fa-location-dot text-xl"></i>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <h3 class="text-xl font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($branch['name']) ?></h3>
                                    <p class="mt-2 text-gray-600 dark:text-gray-400"><?= htmlspecialchars($branch['address']) ?></p>
                                    <a href="https://maps.google.com/?q=<?= urlencode($branch['address']) ?>" target="_blank" class="inline-flex items-center mt-4 text-primary-600 dark:text-primary-400 font-medium hover:underline">
                                        Get Directions <i class="fa-solid fa-arrow-right ml-2 text-sm"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Contact Section -->
        <section id="contact" class="py-24 bg-primary-900 text-white relative overflow-hidden">
            <!-- Decorative circles -->
            <div class="absolute top-0 left-0 w-96 h-96 bg-primary-800 rounded-full mix-blend-multiply filter blur-3xl opacity-50 transform -translate-x-1/2 -translate-y-1/2"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 bg-primary-700 rounded-full mix-blend-multiply filter blur-3xl opacity-50 transform translate-x-1/2 translate-y-1/2"></div>
            
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
                <div class="grid lg:grid-cols-2 gap-16">
                    <div>
                        <h2 class="text-4xl font-extrabold mb-6">Get In Touch</h2>
                        <p class="text-primary-200 text-lg mb-10">Have questions about our products, sourcing, or want to join our team? We'd love to hear from you.</p>
                        
                        <div class="space-y-6">
                            <div class="flex items-center">
                                <div class="w-12 h-12 rounded-full bg-primary-800 flex items-center justify-center mr-4">
                                    <i class="fa-solid fa-phone text-xl text-primary-300"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-primary-300 font-medium uppercase tracking-wider">Phone</p>
                                    <p class="text-xl font-semibold"><?= htmlspecialchars($settingsData['phone_number'] ?? '') ?></p>
                                </div>
                            </div>
                            <div class="flex items-center">
                                <div class="w-12 h-12 rounded-full bg-primary-800 flex items-center justify-center mr-4">
                                    <i class="fa-solid fa-envelope text-xl text-primary-300"></i>
                                </div>
                                <div>
                                    <p class="text-sm text-primary-300 font-medium uppercase tracking-wider">Email</p>
                                    <p class="text-xl font-semibold"><?= htmlspecialchars($settingsData['email'] ?? '') ?></p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-12">
                            <h3 class="text-lg font-semibold mb-4">Follow Us</h3>
                            <div class="flex flex-wrap gap-4">
                                <?php foreach ($socialLinks as $link): ?>
                                <a href="<?= htmlspecialchars($link['url']) ?>" target="_blank" class="w-12 h-12 rounded-full bg-white/10 hover:bg-white/20 flex items-center justify-center transition-all hover:scale-110" title="<?= htmlspecialchars($link['platform']) ?>">
                                    <i class="fa-brands <?= getSocialIcon($link['platform']) ?> text-xl"></i>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white dark:bg-gray-900 rounded-3xl p-8 sm:p-10 shadow-2xl">
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Send us a Message</h3>
                        <form action="#" method="POST" class="space-y-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Full Name</label>
                                <input type="text" id="name" name="name" class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors outline-none" required>
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email Address</label>
                                <input type="email" id="email" name="email" class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors outline-none" required>
                            </div>
                            <div>
                                <label for="message" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Message</label>
                                <textarea id="message" name="message" rows="4" class="w-full px-4 py-3 rounded-lg border border-gray-300 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-transparent transition-colors outline-none" required></textarea>
                            </div>
                            <button type="submit" class="w-full bg-primary-600 hover:bg-primary-700 text-white font-bold py-4 rounded-lg transition-colors shadow-lg shadow-primary-500/30">
                                Send Message
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-gray-900 text-white py-12 border-t border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-gray-400">&copy; <?= date('Y') ?> <?= htmlspecialchars($settingsData['store_name'] ?? 'Archer Grocery') ?>. All rights reserved.</p>
        </div>
    </footer>

    <!-- Scripts -->
    <script>
        // Dark Mode Logic
        const themeToggleBtn = document.getElementById('theme-toggle');
        const themeToggleBtnMobile = document.getElementById('theme-toggle-mobile');
        
        const darkIcon = document.getElementById('theme-toggle-dark-icon');
        const lightIcon = document.getElementById('theme-toggle-light-icon');
        const darkIconMobile = document.getElementById('theme-toggle-dark-icon-mobile');
        const lightIconMobile = document.getElementById('theme-toggle-light-icon-mobile');

        // Check local storage or system preference
        if (localStorage.getItem('color-theme') === 'dark' || (!('color-theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
            lightIcon.classList.remove('hidden');
            lightIconMobile.classList.remove('hidden');
        } else {
            document.documentElement.classList.remove('dark');
            darkIcon.classList.remove('hidden');
            darkIconMobile.classList.remove('hidden');
        }

        const toggleTheme = () => {
            darkIcon.classList.toggle('hidden');
            lightIcon.classList.toggle('hidden');
            darkIconMobile.classList.toggle('hidden');
            lightIconMobile.classList.toggle('hidden');

            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.setItem('color-theme', 'light');
            } else {
                document.documentElement.classList.add('dark');
                localStorage.setItem('color-theme', 'dark');
            }
        };

        themeToggleBtn.addEventListener('click', toggleTheme);
        themeToggleBtnMobile.addEventListener('click', toggleTheme);

        // Mobile Menu Toggle
        const btn = document.getElementById('mobile-menu-btn');
        const menu = document.getElementById('mobile-menu');
        
        btn.addEventListener('click', () => {
            menu.classList.toggle('hidden');
        });

        // Header scroll effect
        const header = document.getElementById('header');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 20) {
                header.classList.add('shadow-md');
            } else {
                header.classList.remove('shadow-md');
            }
        });
    </script>
</body>
</html>
