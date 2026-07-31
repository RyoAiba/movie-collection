@extends('layouts.app')

@section('title', 'コレクション | Movie Shelf')

@section('content')
    <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <h1 class="text-3xl font-bold tracking-tight">コレクション</h1>
            <span class="rounded-full bg-white/10 px-3 py-1 text-sm font-medium text-slate-300">{{ $movies->count() }}作品</span>
        </div>
        <a href="{{ route('home') }}" class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-400">
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m21 21-4.3-4.3M19 11a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/></svg>
            映画を探す
        </a>
    </div>

    @if ($movies->isEmpty())
        <div class="rounded-2xl border border-dashed border-white/15 bg-white/[0.03] px-6 py-20 text-center">
            <div class="mx-auto flex size-14 items-center justify-center rounded-full bg-white/5 text-slate-500">
                <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 6h16v14H4zM8 6l2-3h4l2 3M8 10h8M8 14h5"/></svg>
            </div>
            <p class="text-lg font-medium">コレクションはまだ空です</p>
            <p class="mt-2 text-sm text-slate-400">上映中の映画から気になる作品を追加してみましょう。</p>
            <a href="{{ route('home') }}" class="mt-6 inline-block rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-red-500">上映中の映画を見る</a>
        </div>
    @else
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 md:gap-5 lg:grid-cols-4 xl:grid-cols-6">
            @foreach ($movies as $movie)
                <article class="group flex min-w-0 flex-col overflow-hidden rounded-xl border border-white/10 bg-slate-900 transition-colors hover:border-white/20">
                    <a href="{{ route('movies.show', $movie->tmdb_id) }}" class="block focus-visible:outline-2 focus-visible:outline-inset focus-visible:outline-red-500">
                        <div class="aspect-[2/3] overflow-hidden bg-slate-800">
                            @if ($movie->poster_path)
                                <img
                                    src="https://image.tmdb.org/t/p/w500{{ $movie->poster_path }}"
                                    alt="{{ $movie->title }}のポスター"
                                    class="h-full w-full object-cover motion-safe:transition-transform motion-safe:duration-300 motion-safe:group-hover:scale-[1.03]"
                                    loading="lazy"
                                >
                            @else
                                <div class="flex h-full items-center justify-center text-xs text-slate-500">No Poster</div>
                            @endif
                        </div>
                        <div class="px-3 pt-3">
                            <h2 class="line-clamp-2 min-h-10 text-sm font-semibold leading-5">{{ $movie->title }}</h2>
                            <p class="mt-1 text-xs text-slate-500">{{ $movie->release_date?->format('Y年n月j日') ?? '公開日未定' }}</p>
                        </div>
                    </a>

                    <div class="flex flex-1 flex-col px-3 pb-3">
                        <div class="mt-3 flex items-center gap-2">
                            @if ($movie->rating)
                                <span class="text-sm tracking-wider text-amber-400" aria-label="5点満点中{{ $movie->rating }}点">
                                    {{ str_repeat('★', $movie->rating) }}<span class="text-slate-700">{{ str_repeat('★', 5 - $movie->rating) }}</span>
                                </span>
                            @else
                                <span class="text-xs text-slate-500">未評価</span>
                            @endif
                        </div>

                        <p class="mt-2 line-clamp-3 min-h-14 text-xs leading-[1.125rem] {{ $movie->review ? 'text-slate-400' : 'italic text-slate-600' }}">
                            {{ $movie->review ?: 'レビューはまだありません' }}
                        </p>

                        <div class="mt-3 flex items-center gap-2 border-t border-white/8 pt-3">
                            <a href="{{ route('movies.edit', $movie) }}" class="inline-flex min-w-0 flex-1 items-center justify-center gap-1.5 rounded-lg bg-white/8 px-2 py-2 text-xs font-medium text-slate-200 transition hover:bg-white/14">
                                <svg class="size-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>
                                編集
                            </a>
                            <form action="{{ route('movies.destroy', $movie) }}" method="POST" onsubmit="return confirm('この映画をコレクションから削除しますか？')">
                                @csrf
                                @method('DELETE')
                                <button
                                    class="flex size-8 items-center justify-center rounded-lg text-slate-500 transition hover:bg-red-500/10 hover:text-red-400 focus-visible:outline-2 focus-visible:outline-red-400"
                                    aria-label="コレクションから削除"
                                    title="コレクションから削除"
                                >
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M19 6l-1 14H6L5 6M10 10v6M14 10v6"/></svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
@endsection
