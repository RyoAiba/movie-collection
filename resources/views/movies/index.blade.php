@extends('layouts.app')

@section('title', 'コレクション | Movie Shelf')

@section('content')
    <div class="mb-9 flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="mb-2 text-sm font-semibold uppercase tracking-widest text-amber-400">My Collection</p>
            <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">コレクション</h1>
            <p class="mt-3 text-slate-400">{{ $movies->count() }}作品を保存しています。</p>
        </div>
        <a href="{{ route('home') }}" class="rounded-lg border border-white/15 px-4 py-2 text-sm font-medium transition hover:bg-white/10">映画を探す</a>
    </div>

    @if ($movies->isEmpty())
        <div class="rounded-2xl border border-dashed border-white/15 bg-white/[0.03] px-6 py-16 text-center">
            <p class="text-lg font-medium">コレクションはまだ空です</p>
            <p class="mt-2 text-sm text-slate-400">上映中の映画から気になる作品を追加してみましょう。</p>
            <a href="{{ route('home') }}" class="mt-6 inline-block rounded-lg bg-amber-400 px-5 py-2.5 text-sm font-semibold text-slate-950 hover:bg-amber-300">上映中の映画を見る</a>
        </div>
    @else
        <div class="space-y-5">
            @foreach ($movies as $movie)
                <article class="flex gap-5 rounded-2xl border border-white/10 bg-slate-900 p-4 sm:gap-7 sm:p-6">
                    <div class="w-24 shrink-0 overflow-hidden rounded-lg bg-slate-800 sm:w-32">
                        @if ($movie->poster_path)
                            <img src="https://image.tmdb.org/t/p/w342{{ $movie->poster_path }}" alt="{{ $movie->title }}のポスター" class="aspect-[2/3] h-full w-full object-cover">
                        @else
                            <div class="flex aspect-[2/3] items-center justify-center text-xs text-slate-500">No Poster</div>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <h2 class="text-xl font-semibold">{{ $movie->title }}</h2>
                        <p class="mt-1 text-sm text-slate-500">{{ $movie->release_date?->format('Y年n月j日') ?? '公開日未定' }}</p>
                        <div class="mt-4 flex items-center gap-3">
                            <span class="text-sm text-slate-400">評価</span>
                            <span class="tracking-wider text-amber-400">
                                {{ $movie->rating ? str_repeat('★', $movie->rating).str_repeat('☆', 5 - $movie->rating) : '未評価' }}
                            </span>
                        </div>
                        @if ($movie->review)
                            <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-300">{{ $movie->review }}</p>
                        @endif
                        <div class="mt-5 flex flex-wrap gap-3">
                            <a href="{{ route('movies.edit', $movie) }}" class="rounded-lg bg-white/10 px-4 py-2 text-sm font-medium transition hover:bg-white/15">評価・レビューを編集</a>
                            <form action="{{ route('movies.destroy', $movie) }}" method="POST" onsubmit="return confirm('この映画をコレクションから削除しますか？')">
                                @csrf
                                @method('DELETE')
                                <button class="rounded-lg px-4 py-2 text-sm font-medium text-red-300 transition hover:bg-red-400/10">削除</button>
                            </form>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
@endsection
