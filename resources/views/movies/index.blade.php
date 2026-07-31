@extends('layouts.app')

@section('title', 'コレクション | Movie Shelf')

@section('content')
    <div class="mb-7 flex items-center gap-3">
        <div class="flex items-center gap-3">
            <h1 class="text-3xl font-bold tracking-tight">コレクション</h1>
            <span class="rounded-full bg-white/10 px-3 py-1 text-sm font-medium text-slate-300">{{ $movies->count() }}作品</span>
        </div>
    </div>

    @if ($movies->isEmpty())
        <div class="rounded-2xl border border-dashed border-white/15 bg-white/[0.03] px-6 py-20 text-center">
            <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-white/5 text-slate-500">
                <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 6h16v14H4zM8 6l2-3h4l2 3M8 10h8M8 14h5"/></svg>
            </div>
            <p class="text-lg font-medium">コレクションはまだ空です</p>
            <p class="mt-2 text-sm text-slate-400">追加した映画がここに表示されます。</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach ($movies as $movie)
                @php
                    $tmdbRating = $tmdbRatings[$movie->tmdb_id] ?? null;
                    $ratingPercentage = $tmdbRating !== null ? max(0, min(100, $tmdbRating * 10)) : null;
                @endphp
                <article class="group relative cursor-pointer overflow-hidden rounded-xl border border-white/10 bg-slate-900 transition-colors hover:border-white/20">
                    <a href="{{ route('movies.show', $movie->tmdb_id) }}" class="grid min-w-0 grid-cols-[9rem_minmax(0,1fr)_3.5rem] focus-visible:outline-2 focus-visible:outline-inset focus-visible:outline-red-500 sm:grid-cols-[16rem_minmax(0,1fr)_4.5rem]">
                        <div class="h-24 overflow-hidden bg-slate-800 sm:h-[6.5rem]">
                            @if ($movie->poster_path)
                                <img
                                    src="https://image.tmdb.org/t/p/w500{{ $movie->poster_path }}"
                                    alt="{{ $movie->title }}のポスター"
                                    class="h-full w-full object-cover object-center motion-safe:transition-transform motion-safe:duration-300 motion-safe:group-hover:scale-[1.03] motion-safe:group-focus-within:scale-[1.03]"
                                    loading="lazy"
                                >
                            @else
                                <div class="flex h-full items-center justify-center text-xs text-slate-500">No Poster</div>
                            @endif
                        </div>
                        <div class="flex min-w-0 flex-col justify-center gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <div class="min-w-0">
                                <h2 class="truncate text-sm font-semibold text-slate-100 sm:text-base">{{ $movie->title }}</h2>
                                <p class="mt-1 text-xs text-slate-500">{{ $movie->release_date?->format('Y年n月j日') ?? '公開日未定' }}</p>
                            </div>

                            <div class="shrink-0 sm:text-right">
                                @if ($ratingPercentage !== null)
                                    <div class="flex items-center gap-2 sm:justify-end">
                                        <span class="relative inline-block text-lg leading-none tracking-[0.12em]" aria-label="TMDB平均評価 {{ round($ratingPercentage) }}パーセント">
                                            <span class="text-slate-700" aria-hidden="true">★★★★★</span>
                                            <span class="absolute inset-y-0 left-0 overflow-hidden text-amber-400" style="width: {{ $ratingPercentage }}%" aria-hidden="true">
                                                <span class="block whitespace-nowrap">★★★★★</span>
                                            </span>
                                        </span>
                                        <span class="text-xs font-medium tabular-nums text-slate-400">{{ round($ratingPercentage) }}%</span>
                                    </div>
                                @else
                                    <span class="text-xs text-slate-600">評価情報なし</span>
                                @endif
                            </div>
                        </div>
                        <span aria-hidden="true"></span>
                    </a>
                    @include('movies.partials.collection-toggle', [
                        'collectionId' => $movie->id,
                        'tmdbId' => $movie->tmdb_id,
                        'compact' => true,
                        'class' => 'absolute right-2.5 top-1/2 z-10 -translate-y-1/2 sm:right-[1.125rem]',
                    ])
                </article>
            @endforeach
        </div>
    @endif
@endsection
