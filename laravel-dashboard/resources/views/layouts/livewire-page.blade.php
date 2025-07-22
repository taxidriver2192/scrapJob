<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'ScrapJob Dashboard') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        
        <!-- Flux Appearance Directive for Dark Mode -->
        @fluxAppearance
    </head>
    <body class="min-h-screen font-sans antialiased">
        <livewire:layout.navigation />

        <!-- Page Heading -->
        @isset($header)
            <header class="bg-white dark:bg-zinc-900 shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </div>
            </header>
        @endisset

        <!-- Page Content -->
        <main>
            @if(isset($component))
                @if(isset($parameters))
                    @livewire($component, $parameters)
                @else
                    @livewire($component)
                @endif
            @else
                @yield('content')
            @endif
        </main>

        @fluxScripts
    </body>
</html>
