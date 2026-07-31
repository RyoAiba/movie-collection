@extends('layouts.app')

@section('title', '上映中の映画 | Movie Shelf')

@section('content')
    <section>
        <div class="mb-6">
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
        <section class="mt-16">
            <div class="mb-6">
                <h2 class="text-3xl font-bold tracking-tight">{{ $fiscalYear }}年の人気作品</h2>
            </div>

            @if ($popularError)
                <div class="rounded-2xl border border-red-400/30 bg-red-400/10 p-6 text-red-200">{{ $popularError }}</div>
            @else
                @include('movies.partials.horizontal-movie-list', ['items' => $popularMovies])
            @endif
        </section>
    @endif

    <section class="mt-16">
        <div class="mb-6">
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
