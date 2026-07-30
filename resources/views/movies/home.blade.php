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
        <div class="grid items-stretch gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($movies as $movie)
                @php($collectionId = $collectedMovies->get($movie['id']))
                <article class="flex h-full overflow-hidden rounded-2xl border border-white/10 bg-slate-900 shadow-xl shadow-black/10 sm:flex-col">
                    <div class="relative w-32 shrink-0 overflow-hidden bg-slate-800 sm:h-72 sm:w-full">
                        @if (!empty($movie['poster_path']))
                            <img
                                src="https://image.tmdb.org/t/p/w500{{ $movie['poster_path'] }}"
                                alt="{{ $movie['title'] }}のポスター"
                                class="h-full w-full object-cover"
                                loading="lazy"
                            >
                        @else
                            <div class="flex h-full min-h-48 items-center justify-center px-3 text-center text-sm text-slate-500">No Poster</div>
                        @endif
                        <form
                            action="{{ $collectionId ? route('movies.destroy', $collectionId) : route('movies.store') }}"
                            method="POST"
                            class="collection-toggle absolute right-3 top-3"
                            data-store-url="{{ route('movies.store') }}"
                            data-tmdb-id="{{ $movie['id'] }}"
                            data-collected="{{ $collectionId ? 'true' : 'false' }}"
                        >
                            @csrf
                            <button
                                type="submit"
                                class="group relative flex size-10 items-center justify-center rounded-full border shadow-lg backdrop-blur transition hover:scale-105 disabled:cursor-wait disabled:opacity-60 {{ $collectionId ? 'border-emerald-300/70 bg-emerald-400 text-slate-950 hover:border-red-300/80 hover:bg-red-400' : 'border-white/30 bg-slate-950/80 text-white hover:border-amber-300 hover:bg-amber-400 hover:text-slate-950' }}"
                                aria-label="{{ $collectionId ? 'コレクションから外す' : 'コレクションに追加' }}"
                                title="{{ $collectionId ? 'コレクションから外す' : 'コレクションに追加' }}"
                            >
                                <span class="toggle-icon" aria-hidden="true">
                                    @if ($collectionId)
                                        <svg class="size-5 group-hover:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m5 12 4 4L19 6"/></svg>
                                        <svg class="hidden size-5 group-hover:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/></svg>
                                    @else
                                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
                                    @endif
                                </span>
                            </button>
                        </form>
                    </div>
                    <div class="flex min-w-0 flex-1 flex-col p-4 sm:p-5">
                        <h2 class="line-clamp-2 text-lg font-semibold">{{ $movie['title'] }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ $movie['release_date'] ?: '公開日未定' }}</p>
                        <p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-400">{{ $movie['overview'] ?: '概要はまだありません。' }}</p>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
@endsection
