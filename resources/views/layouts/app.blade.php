<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Movie Shelf')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
    <header class="border-b border-white/10 bg-slate-950/90">
        <nav class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4 sm:px-8">
            <a href="{{ route('home') }}" class="text-xl font-black uppercase tracking-[-0.04em] text-red-600 transition hover:text-red-500">
                Movie Shelf
            </a>
            <div class="flex gap-5 text-sm font-medium text-slate-300">
                <a href="{{ route('home') }}" class="transition hover:text-white {{ request()->routeIs('home') ? 'text-red-500' : '' }}">上映中</a>
                <a href="{{ route('movies.index') }}" class="transition hover:text-white {{ request()->routeIs('movies.*') ? 'text-red-500' : '' }}">コレクション</a>
            </div>
        </nav>
    </header>

    <main class="mx-auto max-w-7xl px-5 py-10 sm:px-8">
        @if (session('success'))
            <div class="mb-7 rounded-xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-200">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="mb-7 rounded-xl border border-red-400/30 bg-red-400/10 px-4 py-3 text-sm text-red-200">
                {{ session('error') }}
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-7 rounded-xl border border-red-400/30 bg-red-400/10 px-4 py-3 text-sm text-red-200">
                {{ $errors->first() }}
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="border-t border-white/10 py-8 text-center text-xs text-slate-500">
        This product uses the TMDB API but is not endorsed or certified by TMDB.
    </footer>
</body>
</html>
