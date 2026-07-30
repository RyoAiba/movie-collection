<form
    action="{{ $collectionId ? route('movies.destroy', $collectionId) : route('movies.store') }}"
    method="POST"
    class="collection-toggle {{ $class ?? '' }}"
    data-store-url="{{ route('movies.store') }}"
    data-tmdb-id="{{ $tmdbId }}"
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
