@extends('layouts.app')

@section('title', '上映中の映画 | Movie Shelf')

@section('content')
    @if (!empty($trailerMovies))
        <section
            class="trailer-carousel relative mb-10 h-[300px] overflow-hidden rounded-2xl border border-white/10 bg-slate-900 sm:h-[420px] lg:h-[min(540px,42vw)]"
            data-trailers='@json($trailerMovies)'
            aria-labelledby="trailer-carousel-title"
        >
            <div class="absolute inset-0 bg-gradient-to-br from-slate-800 to-slate-950"></div>
            <img
                class="trailer-backdrop absolute inset-0 h-full w-full object-cover {{ $trailerMovies[0]['backdrop_url'] ? '' : 'hidden' }}"
                src="{{ $trailerMovies[0]['backdrop_url'] ?? '' }}"
                alt=""
                fetchpriority="high"
            >

            <div class="trailer-player pointer-events-none absolute inset-0 opacity-0 transition-opacity duration-700 ease-out" aria-hidden="true">
                <div class="trailer-video-frame absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">
                    <div id="trailer-youtube-player" class="h-full w-full"></div>
                </div>
            </div>
            <div class="absolute inset-0 bg-gradient-to-r from-black/85 via-black/45 to-black/20"></div>
            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/85 to-transparent px-6 pb-7 pt-24 sm:px-10 sm:pb-10">
                <p class="mb-2 text-xs font-semibold uppercase tracking-[0.2em] text-red-400">Now Playing</p>
                <p id="trailer-carousel-title" class="trailer-title truncate text-xl font-bold tracking-tight text-white drop-shadow-lg sm:text-3xl lg:text-4xl">
                    {{ $trailerMovies[0]['title'] }}
                </p>
            </div>
        </section>
    @endif

    <section>
        <div class="mb-4">
            <h1 class="text-3xl font-bold tracking-tight">上映中の映画</h1>
        </div>

        @if ($apiError)
            <div class="rounded-2xl border border-red-400/30 bg-red-400/10 p-6 text-red-200">{{ $apiError }}</div>
        @elseif (empty($movies))
            <div class="rounded-2xl border border-white/10 bg-white/5 p-10 text-center text-slate-400">上映中の映画が見つかりませんでした。</div>
        @else
            @include('movies.partials.horizontal-movie-list', ['items' => $movies])
        @endif
    </section>

    @if ($popularError || !empty($popularMovies))
        <section class="mt-12">
            <div class="mb-4">
                <h2 class="text-3xl font-bold tracking-tight">{{ $fiscalYear }}年の人気作品</h2>
            </div>

            @if ($popularError)
                <div class="rounded-2xl border border-red-400/30 bg-red-400/10 p-6 text-red-200">{{ $popularError }}</div>
            @else
                @include('movies.partials.horizontal-movie-list', ['items' => $popularMovies])
            @endif
        </section>
    @endif

    <section class="mt-12">
        <div class="mb-4">
            <h2 class="text-3xl font-bold tracking-tight">マイコレクション</h2>
        </div>

        @if (empty($collectionMovies))
            <div class="rounded-2xl border border-white/10 bg-white/5 p-8 text-center text-slate-400">
                コレクションに追加した映画がここに表示されます。
            </div>
        @else
            @include('movies.partials.horizontal-movie-list', ['items' => $collectionMovies])
        @endif
    </section>
@endsection
