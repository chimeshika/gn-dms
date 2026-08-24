<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Abhaya+Libre:wght@400;600;700&family=Inter:wght@400;500;600;700&family=Noto+Sans+Tamil:wght@400;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/css/app.scss', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-800 antialiased">

    <header class="brand-gradient text-white shadow-lg">
        <nav class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-white/15 font-bold">GN</div>
                <div>
                    <div class="text-sm font-bold leading-tight sm:text-base">Grama Niladhari & Public Officers</div>
                    <div class="text-[11px] uppercase tracking-widest text-white/70 sm:text-xs">Management System</div>
                </div>
            </a>

            <div class="flex items-center gap-3">
                @auth
                    <span class="hidden text-sm text-white/80 sm:block">{{ auth()->user()->name }}</span>
                    @if (auth()->user()->isOfficer())
                        <a href="{{ route('dashboard') }}" class="rounded-lg bg-white/15 px-3 py-2 text-sm font-semibold hover:bg-white/25">Dashboard</a>
                    @else
                        <a href="/admin" class="rounded-lg bg-white/15 px-3 py-2 text-sm font-semibold hover:bg-white/25">Admin Panel</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-lg bg-white/15 px-3 py-2 text-sm font-semibold hover:bg-white/25">Logout</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="rounded-lg bg-white/15 px-3 py-2 text-sm font-semibold hover:bg-white/25">Login</a>
                    <a href="{{ route('register.create') }}" class="rounded-lg bg-white px-3 py-2 text-sm font-semibold text-slate-900 hover:bg-white/90">Register</a>
                @endauth
            </div>
        </nav>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        @if (session('status'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
                {{ session('status') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
                {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3">
                <ul class="list-disc space-y-1 pl-5 text-sm text-red-800">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="border-t border-slate-200 bg-white py-6 text-center text-xs text-slate-500">
        {{ config('app.name') }} &middot; Ministry of Home Affairs
    </footer>

    @stack('scripts')
</body>
</html>
