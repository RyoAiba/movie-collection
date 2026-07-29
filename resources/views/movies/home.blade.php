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
        <div class="grid gap-7 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($movies as $movie)
                @php($isCollected = in_array($movie['id'], $collectedTmdbIds))
                <article class="flex overflow-hidden rounded-2xl border border-white/10 bg-slate-900 shadow-xl shadow-black/10 sm:flex-col">
                    <div class="w-32 shrink-0 bg-slate-800 sm:aspect-[2/3] sm:w-full">
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
                    </div>
                    <div class="flex min-w-0 flex-1 flex-col p-4 sm:p-5">
                        <h2 class="line-clamp-2 text-lg font-semibold">{{ $movie['title'] }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ $movie['release_date'] ?: '公開日未定' }}</p>
                        <p class="mt-3 line-clamp-4 text-sm leading-6 text-slate-400">{{ $movie['overview'] ?: '概要はまだありません。' }}</p>
                        <div class="mt-auto pt-5">
                            @if ($isCollected)
                                <span class="block rounded-lg border border-emerald-400/30 bg-emerald-400/10 px-4 py-2 text-center text-sm font-medium text-emerald-300">追加済み</span>
                            @else
                                <form action="{{ route('movies.store') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="tmdb_id" value="{{ $movie['id'] }}">
                                    <button class="w-full rounded-lg bg-amber-400 px-4 py-2 text-sm font-semibold text-slate-950 transition hover:bg-amber-300">
                                        コレクションに追加
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
@endsection
