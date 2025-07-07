<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'LinkedIn Job Scraper Dashboard')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .stat-card { transition: transform 0.2s; }
        .stat-card:hover { transform: translateY(-5px); }
        .navbar-brand { font-weight: bold; }
    </style>
    @livewireStyles
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/"><i class="fas fa-briefcase me-2"></i>LinkedIn Job Scraper</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="/"><i class="fas fa-chart-line me-1"></i>Dashboard</a>
                <a class="nav-link" href="/jobs"><i class="fas fa-briefcase me-1"></i>Jobs</a>
                <a class="nav-link" href="/companies"><i class="fas fa-building me-1"></i>Companies</a>
                <a class="nav-link" href="/queue"><i class="fas fa-list me-1"></i>Queue</a>
                <a class="nav-link" href="/ratings"><i class="fas fa-star me-1"></i>Ratings</a>
            </div>
        </div>
    </nav>

    <main>
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @livewireScripts
</body>
</html>
