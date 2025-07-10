<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'LinkedIn Job Scraper Dashboard')</title>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600&display=swap" rel="stylesheet" />

    @livewireStyles
    @fluxAppearance
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')

    <style>
        /* Custom styles for formatted job description content */
        .formatted-content {
            line-height: 1.6;
        }
        .formatted-content p {
            margin-bottom: 1rem;
        }
        .formatted-content h1, .formatted-content h2, .formatted-content h3,
        .formatted-content h4, .formatted-content h5, .formatted-content h6 {
            font-weight: 600;
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            color: inherit;
        }
        .formatted-content h1 { font-size: 1.5rem; }
        .formatted-content h2 { font-size: 1.375rem; }
        .formatted-content h3 { font-size: 1.25rem; }
        .formatted-content h4 { font-size: 1.125rem; }
        .formatted-content h5 { font-size: 1rem; }
        .formatted-content h6 { font-size: 0.875rem; }
        .formatted-content ul, .formatted-content ol {
            margin-bottom: 1rem;
            padding-left: 1.5rem;
        }
        .formatted-content li {
            margin-bottom: 0.25rem;
        }
        .formatted-content strong, .formatted-content b {
            font-weight: 600;
        }
        .formatted-content em, .formatted-content i {
            font-style: italic;
        }
        .formatted-content a {
            color: #2563eb;
            text-decoration: underline;
        }
        .formatted-content a:hover {
            color: #1d4ed8;
        }
        .dark .formatted-content a {
            color: #60a5fa;
        }
        .dark .formatted-content a:hover {
            color: #93c5fd;
        }
    </style>
</head>
<body class="min-h-screen bg-white dark:bg-zinc-900 text-zinc-900 dark:text-zinc-100">
    <flux:header class="bg-blue-600 dark:bg-blue-800 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Brand Logo -->
                <div class="flex items-center">
                    <a href="/" class="font-bold text-xl text-white hover:text-blue-200 transition-colors">
                        <i class="fas fa-briefcase mr-2"></i>LinkedIn Job Scraper
                    </a>
                </div>

                <!-- Navigation Items -->
                <flux:navbar class="hidden md:flex">
                    <flux:navbar.item href="/" icon="chart-line">Dashboard</flux:navbar.item>
                    <flux:navbar.item href="/jobs" icon="briefcase">Jobs</flux:navbar.item>
                    <flux:navbar.item href="/companies" icon="building">Companies</flux:navbar.item>
                    <flux:navbar.item href="/queue" icon="list">Queue</flux:navbar.item>
                    <flux:navbar.item href="/ratings" icon="star">Ratings</flux:navbar.item>
                </flux:navbar>

                <!-- Mobile menu button -->
                <div class="md:hidden">
                    <button type="button" class="text-white hover:text-blue-200 p-2" onclick="toggleMobileMenu()">
                        <i class="fas fa-bars"></i>
                    </button>
                </div>
            </div>

            <!-- Mobile Navigation -->
            <div id="mobile-menu" class="hidden md:hidden">
                <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
                    <a href="/" class="block px-3 py-2 text-white hover:text-blue-200"><i class="fas fa-chart-line mr-2"></i>Dashboard</a>
                    <a href="/jobs" class="block px-3 py-2 text-white hover:text-blue-200"><i class="fas fa-briefcase mr-2"></i>Jobs</a>
                    <a href="/companies" class="block px-3 py-2 text-white hover:text-blue-200"><i class="fas fa-building mr-2"></i>Companies</a>
                    <a href="/queue" class="block px-3 py-2 text-white hover:text-blue-200"><i class="fas fa-list mr-2"></i>Queue</a>
                    <a href="/ratings" class="block px-3 py-2 text-white hover:text-blue-200"><i class="fas fa-star mr-2"></i>Ratings</a>
                </div>
            </div>
        </div>
    </flux:header>

    <main class="py-6">
        @yield('content')
    </main>

    @livewireScripts
    @fluxScripts
    @stack('scripts')
    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('mobile-menu');
            menu.classList.toggle('hidden');
        }
    </script>
</body>
</html>
