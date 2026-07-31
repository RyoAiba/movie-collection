<div class="relative">
    <div class="scroll-fade-scroller horizontal-scroller flex gap-3 overflow-x-auto overscroll-x-contain pb-4">
        @foreach ($items as $movie)
            @php($collectionId = $collectedMovies->get($movie['id']))
            <article class="group/movie relative w-32 shrink-0 overflow-hidden rounded-xl border border-white/10 bg-slate-900">
                <a href="{{ route('movies.show', $movie['id']) }}" class="block focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-amber-400">
                    <div class="h-48 overflow-hidden bg-slate-800">
                        @if (!empty($movie['poster_path']))
                            <img
                                src="https://image.tmdb.org/t/p/w342{{ $movie['poster_path'] }}"
                                alt="{{ $movie['title'] }}のポスター"
                                class="h-full w-full object-cover motion-safe:transition-transform motion-safe:duration-300 motion-safe:group-hover/movie:scale-[1.04] motion-safe:group-focus-within/movie:scale-[1.04]"
                                loading="lazy"
                            >
                        @else
                            <div class="flex h-full items-center justify-center px-3 text-center text-xs text-slate-500">No Poster</div>
                        @endif
                    </div>
                    <div class="p-2.5">
                        <h3 class="line-clamp-2 min-h-10 text-sm font-semibold leading-5">{{ $movie['title'] }}</h3>
                        <p class="mt-1 text-xs text-slate-500">{{ $movie['release_date'] ?: '公開日未定' }}</p>
                    </div>
                </a>
                @include('movies.partials.collection-toggle', [
                    'collectionId' => $collectionId,
                    'tmdbId' => $movie['id'],
                    'class' => 'absolute right-1.5 top-1.5 z-10',
                    'compact' => true,
                ])
            </article>
        @endforeach
    </div>
    <div class="scroll-fade-left pointer-events-none absolute bottom-4 left-0 top-0 w-8 bg-gradient-to-r from-slate-950 to-transparent opacity-0 transition-opacity duration-200" aria-hidden="true"></div>
    <div class="scroll-fade-right pointer-events-none absolute bottom-4 right-0 top-0 w-8 bg-gradient-to-l from-slate-950 to-transparent opacity-0 transition-opacity duration-200" aria-hidden="true"></div>
</div>
