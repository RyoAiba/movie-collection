@extends('layouts.app')

@section('title', $movie->title.'を編集 | Movie Shelf')

@section('content')
    <a href="{{ route('movies.index') }}" class="text-sm text-slate-400 transition hover:text-white">← コレクションへ戻る</a>

    <div class="mt-6 grid gap-8 lg:grid-cols-[220px_1fr]">
        <div class="mx-auto w-44 overflow-hidden rounded-xl bg-slate-800 shadow-2xl lg:mx-0 lg:w-full">
            @if ($movie->poster_path)
                <img src="https://image.tmdb.org/t/p/w500{{ $movie->poster_path }}" alt="{{ $movie->title }}のポスター" class="aspect-[2/3] w-full object-cover">
            @else
                <div class="flex aspect-[2/3] items-center justify-center text-sm text-slate-500">No Poster</div>
            @endif
        </div>

        <div>
            <p class="mb-2 text-sm font-semibold uppercase tracking-widest text-amber-400">Edit Review</p>
            <h1 class="text-3xl font-bold tracking-tight">{{ $movie->title }}</h1>
            <p class="mt-4 max-w-3xl text-sm leading-7 text-slate-400">{{ $movie->overview ?: '概要はありません。' }}</p>

            <form action="{{ route('movies.update', $movie) }}" method="POST" class="mt-8 max-w-2xl space-y-6">
                @csrf
                @method('PUT')

                <div>
                    <label for="rating" class="mb-2 block text-sm font-medium">評価</label>
                    <select id="rating" name="rating" class="w-full rounded-lg border border-white/15 bg-slate-900 px-3 py-2.5 text-slate-100 outline-none focus:border-amber-400">
                        <option value="">未評価</option>
                        @foreach (range(1, 5) as $rating)
                            <option value="{{ $rating }}" @selected((string) old('rating', $movie->rating) === (string) $rating)>
                                {{ $rating }} - {{ str_repeat('★', $rating) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="review" class="mb-2 block text-sm font-medium">レビュー</label>
                    <textarea id="review" name="review" rows="8" maxlength="5000" placeholder="この映画の感想を書いてください。" class="w-full rounded-lg border border-white/15 bg-slate-900 px-3 py-3 leading-6 text-slate-100 outline-none placeholder:text-slate-600 focus:border-amber-400">{{ old('review', $movie->review) }}</textarea>
                </div>

                <div class="flex gap-3">
                    <button class="rounded-lg bg-amber-400 px-5 py-2.5 text-sm font-semibold text-slate-950 transition hover:bg-amber-300">保存する</button>
                    <a href="{{ route('movies.index') }}" class="rounded-lg border border-white/15 px-5 py-2.5 text-sm font-medium transition hover:bg-white/10">キャンセル</a>
                </div>
            </form>
        </div>
    </div>
@endsection
