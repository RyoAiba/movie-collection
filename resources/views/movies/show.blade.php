@extends('layouts.app')

@section('title', $movie['title'].' | Movie Shelf')

@section('content')
    <section class="relative isolate overflow-hidden rounded-3xl border border-white/10 bg-slate-900">
        @if (!empty($movie['backdrop_path']))
            <img
                src="https://image.tmdb.org/t/p/original{{ $movie['backdrop_path'] }}"
                alt=""
                class="absolute inset-0 -z-20 h-full w-full object-cover opacity-30"
            >
        @endif
        <div class="absolute inset-0 -z-10 bg-gradient-to-r from-slate-950 via-slate-950/90 to-slate-950/45"></div>
        <div class="grid gap-8 p-6 sm:p-8 md:grid-cols-[220px_1fr] lg:p-10">
            <div class="mx-auto w-44 overflow-hidden rounded-xl bg-slate-800 shadow-2xl md:mx-0 md:w-full">
                @if (!empty($movie['poster_path']))
                    <img src="https://image.tmdb.org/t/p/w500{{ $movie['poster_path'] }}" alt="{{ $movie['title'] }}のポスター" class="aspect-[2/3] h-full w-full object-cover">
                @else
                    <div class="flex aspect-[2/3] items-center justify-center text-sm text-slate-500">No Poster</div>
                @endif
            </div>

            <div class="min-w-0 self-center">
                <div class="flex items-start justify-between gap-5">
                    <div>
                        <h1 class="text-3xl font-bold tracking-tight sm:text-4xl lg:text-5xl">{{ $movie['title'] }}</h1>
                        <p class="mt-2 text-sm text-slate-400">{{ $movie['original_title'] ?? $movie['title'] }}</p>
                    </div>
                    @include('movies.partials.collection-toggle', [
                        'collectionId' => $collectionId,
                        'tmdbId' => $movie['id'],
                        'class' => 'shrink-0',
                    ])
                </div>

                <div class="mt-5 flex flex-wrap gap-x-5 gap-y-2 text-sm text-slate-300">
                    <span>{{ !empty($movie['release_date']) ? \Illuminate\Support\Carbon::parse($movie['release_date'])->format('Y年n月j日') : '公開日未定' }}</span>
                    @if (!empty($movie['runtime']))
                        <span>{{ intdiv($movie['runtime'], 60) ? intdiv($movie['runtime'], 60).'時間' : '' }}{{ $movie['runtime'] % 60 ? ($movie['runtime'] % 60).'分' : '' }}</span>
                    @endif
                    @if (isset($movie['vote_average']))
                        <span class="font-semibold text-amber-300">★ {{ number_format($movie['vote_average'], 1) }} / 10</span>
                    @endif
                </div>

                @if (!empty($movie['genres']))
                    <div class="mt-5 flex flex-wrap gap-2">
                        @foreach ($movie['genres'] as $genre)
                            <span class="rounded-full border border-white/15 bg-white/10 px-3 py-1 text-xs text-slate-200">{{ $genre['name'] }}</span>
                        @endforeach
                    </div>
                @endif

                <div class="mt-7">
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-slate-300">あらすじ</h2>
                    <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-300 sm:text-base">{{ $movie['overview'] ?: 'あらすじはまだありません。' }}</p>
                </div>

                @if ($director)
                    <div class="mt-6">
                        <p class="text-xs text-slate-500">監督</p>
                        <p class="mt-1 font-medium">{{ $director['name'] }}</p>
                    </div>
                @endif
            </div>
        </div>
    </section>

    <section class="mt-12 grid min-w-0 items-start gap-8 lg:grid-cols-[minmax(0,1fr)_360px]">
        <div class="min-w-0">
            <h2 class="text-2xl font-bold">主な出演者</h2>
            @if ($cast->isNotEmpty())
                <div class="relative mt-6">
                    <div class="scroll-fade-scroller horizontal-scroller flex max-w-full gap-4 overflow-x-auto overscroll-x-contain pb-4">
                        @foreach ($cast as $person)
                            <article class="w-36 shrink-0 overflow-hidden rounded-xl border border-white/10 bg-slate-900">
                                <div class="aspect-[2/3] bg-slate-800">
                                    @if (!empty($person['profile_path']))
                                        <img src="https://image.tmdb.org/t/p/w342{{ $person['profile_path'] }}" alt="{{ $person['name'] }}" class="h-full w-full object-cover" loading="lazy">
                                    @else
                                        <div class="flex h-full flex-col items-center justify-center gap-2 text-slate-500">
                                            <svg class="size-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M20 21a8 8 0 0 0-16 0M12 13a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"/></svg>
                                            <span class="text-xs">No Photo</span>
                                        </div>
                                    @endif
                                </div>
                                <div class="p-3">
                                    <h3 class="line-clamp-2 text-sm font-semibold">{{ $person['name'] }}</h3>
                                    <p class="mt-1 line-clamp-2 text-xs text-slate-500">{{ $person['character'] ?: '役名不明' }}</p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                    <div class="scroll-fade-left pointer-events-none absolute bottom-4 left-0 top-0 w-8 bg-gradient-to-r from-slate-950 to-transparent opacity-0 transition-opacity duration-200" aria-hidden="true"></div>
                    <div class="scroll-fade-right pointer-events-none absolute bottom-4 right-0 top-0 w-8 bg-gradient-to-l from-slate-950 to-transparent opacity-0 transition-opacity duration-200" aria-hidden="true"></div>
                </div>
            @else
                <p class="mt-6 text-sm text-slate-500">出演者情報はまだありません。</p>
            @endif

            @if ($trailer)
                <section class="mt-12">
                    <h2 class="text-2xl font-bold">予告編</h2>
                    <div class="mt-6 aspect-video overflow-hidden rounded-2xl border border-white/10 bg-black">
                        <iframe
                            src="https://www.youtube-nocookie.com/embed/{{ $trailer['key'] }}"
                            title="{{ $movie['title'] }}の予告編"
                            class="h-full w-full"
                            loading="lazy"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen
                        ></iframe>
                    </div>
                </section>
            @endif
        </div>

        <aside class="min-w-0 self-stretch rounded-2xl border border-white/10 bg-slate-900/70 p-5">
            <section aria-labelledby="your-review-heading">
                <h2 id="your-review-heading" class="text-lg font-bold">あなたのレビュー</h2>
                <form class="movie-review-form mt-4" action="{{ route('movies.review.save', $movie['id']) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <fieldset>
                        <legend class="text-xs font-medium text-slate-400">評価</legend>
                        <div class="star-rating mt-2 flex w-fit flex-row-reverse justify-end gap-1">
                            @foreach (range(5, 1) as $rating)
                                <input
                                    type="radio"
                                    id="review-rating-{{ $rating }}"
                                    name="rating"
                                    value="{{ $rating }}"
                                    class="peer sr-only"
                                    @checked($collectionMovie?->rating === $rating)
                                >
                                <label
                                    for="review-rating-{{ $rating }}"
                                    class="cursor-pointer text-2xl leading-none text-slate-600 transition-colors hover:text-amber-400 peer-checked:text-amber-400 peer-focus-visible:rounded-sm peer-focus-visible:outline-2 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-amber-300"
                                    title="{{ $rating }}点"
                                    aria-label="{{ $rating }}点"
                                >★</label>
                            @endforeach
                        </div>
                    </fieldset>

                    <label for="review-comment" class="mt-4 block text-xs font-medium text-slate-400">コメント</label>
                    <textarea
                        id="review-comment"
                        name="review"
                        rows="4"
                        maxlength="5000"
                        class="mt-2 w-full resize-y rounded-xl border border-white/10 bg-slate-950/70 px-3 py-2.5 text-sm leading-6 text-slate-200 outline-none placeholder:text-slate-600 focus:border-amber-400"
                        placeholder="この映画の感想を書いてください"
                    >{{ $collectionMovie?->review }}</textarea>

                    <p class="review-save-message mt-2 hidden text-xs" role="status" aria-live="polite"></p>
                    <button type="submit" class="mt-4 w-full rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-red-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-400 disabled:cursor-wait disabled:opacity-60">
                        レビューを投稿
                    </button>
                </form>
            </section>

            <section class="mt-6 border-t border-white/10 pt-5" aria-labelledby="community-reviews-heading">
                <h2 id="community-reviews-heading" class="text-lg font-bold">みんなのレビュー</h2>
                @if ($tmdbReviews->isEmpty())
                    <p class="mt-4 text-sm text-slate-500">レビューはまだありません</p>
                @else
                    <div class="review-scroller mt-4 max-h-96 space-y-4 overflow-y-auto overscroll-y-contain pr-2">
                        @foreach ($tmdbReviews as $review)
                            <article class="border-b border-white/10 pb-4 last:border-0 last:pb-0">
                                <div class="flex items-center justify-between gap-3">
                                    <h3 class="truncate text-sm font-semibold text-slate-200">{{ $review['author'] ?? '匿名' }}</h3>
                                    @if (isset($review['author_details']['rating']))
                                        <span class="shrink-0 text-xs font-medium text-amber-400">★ {{ number_format((float) $review['author_details']['rating'], 1) }}</span>
                                    @endif
                                </div>
                                <div class="mt-2">
                                    <p class="review-body line-clamp-5 whitespace-pre-line text-sm leading-6 text-slate-400">{{ $review['content'] ?? '' }}</p>
                                    <button type="button" class="review-expand mt-1 hidden text-xs font-medium text-slate-300 underline decoration-slate-600 underline-offset-4 hover:text-white" aria-expanded="false">
                                        続きを読む
                                    </button>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </section>
        </aside>
    </section>
@endsection
