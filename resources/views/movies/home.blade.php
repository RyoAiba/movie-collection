@extends('layouts.app')

@section('title', '上映中の映画 | Movie Shelf')

@section('content')
    <div class="mb-9">
        <p class="mb-2 text-sm font-semibold uppercase tracking-widest text-amber-400">Now Playing</p>
        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">上映中の映画</h1>
        <p class="mt-3 max-w-2xl text-slate-400">気になる映画を見つけて、自分だけのコレクションに追加しましょう。</p>
    </div>

    @if ($apiError)
        <div class="rounded-2xl border border-red-400/30 bg-red-400/10 p-6 text-red-200">{{ $apiError }}</div>
    @elseif (empty($movies))
        <div class="rounded-2xl border border-white/10 bg-white/5 p-10 text-center text-slate-400">上映中の映画が見つかりませんでした。</div>
    @else
        <div class="grid items-stretch gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
            @foreach ($movies as $movie)
                @php($collectionId = $collectedMovies->get($movie['id']))
                <article class="group/card relative h-full overflow-hidden rounded-2xl border border-white/10 bg-slate-900 shadow-xl shadow-black/10">
                    <a href="{{ route('movies.show', $movie['id']) }}" class="flex h-full focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-400 sm:flex-col">
                        <div class="w-32 shrink-0 overflow-hidden bg-slate-800 sm:h-72 sm:w-full">
                            @if (!empty($movie['poster_path']))
                                <img
                                    src="https://image.tmdb.org/t/p/w500{{ $movie['poster_path'] }}"
                                    alt="{{ $movie['title'] }}のポスター"
                                    class="h-full w-full object-cover motion-safe:transition-transform motion-safe:duration-300 motion-safe:group-hover/card:scale-[1.04] motion-safe:group-focus-within/card:scale-[1.04]"
                                    loading="lazy"
                                >
                            @else
                                <div class="flex h-full min-h-48 items-center justify-center px-3 text-center text-sm text-slate-500">No Poster</div>
                            @endif
                        </div>
                        <div class="flex min-w-0 flex-1 flex-col p-4 sm:p-5">
                            <h2 class="line-clamp-2 min-h-14 text-lg font-semibold leading-7">{{ $movie['title'] }}</h2>
                            <p class="mt-1 text-sm text-slate-500">{{ $movie['release_date'] ?: '公開日未定' }}</p>
                        </div>
                    </a>
                    @include('movies.partials.collection-toggle', [
                        'collectionId' => $collectionId,
                        'tmdbId' => $movie['id'],
                        'class' => 'absolute right-3 top-3 z-10',
                    ])
                </article>
            @endforeach
        </div>
    @endif

    @if ($popularError || !empty($popularMovies))
        <section class="mt-16">
            <div class="mb-6">
                <h2 class="text-3xl font-bold tracking-tight">{{ $fiscalYear }}年の人気作品</h2>
            </div>

            @if ($popularError)
                <div class="rounded-2xl border border-red-400/30 bg-red-400/10 p-6 text-red-200">{{ $popularError }}</div>
            @else
            <div class="relative">
                <div class="scroll-fade-scroller horizontal-scroller flex gap-4 overflow-x-auto overscroll-x-contain pb-4">
                    @foreach ($popularMovies as $movie)
                        @php($collectionId = $collectedMovies->get($movie['id']))
                        <article class="group/popular relative w-40 shrink-0 overflow-hidden rounded-xl border border-white/10 bg-slate-900 sm:w-44">
                            <a href="{{ route('movies.show', $movie['id']) }}" class="block focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-400">
                                <div class="h-56 overflow-hidden bg-slate-800">
                                    @if (!empty($movie['poster_path']))
                                        <img
                                            src="https://image.tmdb.org/t/p/w342{{ $movie['poster_path'] }}"
                                            alt="{{ $movie['title'] }}のポスター"
                                            class="h-full w-full object-cover motion-safe:transition-transform motion-safe:duration-300 motion-safe:group-hover/popular:scale-[1.04] motion-safe:group-focus-within/popular:scale-[1.04]"
                                            loading="lazy"
                                        >
                                    @else
                                        <div class="flex h-full items-center justify-center px-3 text-center text-xs text-slate-500">No Poster</div>
                                    @endif
                                </div>
                                <div class="p-3">
                                    <h3 class="line-clamp-2 min-h-10 text-sm font-semibold leading-5">{{ $movie['title'] }}</h3>
                                    <p class="mt-1 text-xs text-slate-500">{{ $movie['release_date'] ?: '公開日未定' }}</p>
                                </div>
                            </a>
                            @include('movies.partials.collection-toggle', [
                                'collectionId' => $collectionId,
                                'tmdbId' => $movie['id'],
                                'class' => 'absolute right-2 top-2 z-10',
                            ])
                        </article>
                    @endforeach
                </div>
                <div class="scroll-fade-left pointer-events-none absolute bottom-4 left-0 top-0 w-8 bg-gradient-to-r from-slate-950 to-transparent opacity-0 transition-opacity duration-200" aria-hidden="true"></div>
                <div class="scroll-fade-right pointer-events-none absolute bottom-4 right-0 top-0 w-8 bg-gradient-to-l from-slate-950 to-transparent opacity-0 transition-opacity duration-200" aria-hidden="true"></div>
            </div>
            @endif
        </section>
    @endif
@endsection
