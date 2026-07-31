@php($isCompact = $compact ?? false)
<form
    action="{{ $collectionId ? route('movies.destroy', $collectionId) : route('movies.store') }}"
    method="POST"
    class="collection-toggle {{ $isCompact ? 'collection-toggle-compact' : '' }} {{ $class ?? '' }}"
    data-store-url="{{ route('movies.store') }}"
    data-tmdb-id="{{ $tmdbId }}"
    data-collected="{{ $collectionId ? 'true' : 'false' }}"
>
    @csrf
    <button
        type="submit"
        class="relative flex {{ $isCompact ? 'size-9' : 'size-11' }} items-center justify-center rounded-full border shadow-lg backdrop-blur motion-safe:transition-transform motion-safe:duration-200 motion-safe:hover:scale-105 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-300 disabled:cursor-wait disabled:opacity-60 {{ $collectionId ? 'border-rose-300/80 bg-rose-500 text-white shadow-rose-950/40 hover:bg-rose-400' : 'border-white/80 bg-white/85 text-rose-600 shadow-black/40 hover:bg-white' }}"
        aria-pressed="{{ $collectionId ? 'true' : 'false' }}"
        aria-label="{{ $collectionId ? 'コレクションから外す' : 'コレクションに追加' }}"
        title="{{ $collectionId ? 'コレクションから外す' : 'コレクションに追加' }}"
    >
        <span class="toggle-icon" aria-hidden="true">
            @if ($collectionId)
                <svg class="size-5" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z"/></svg>
            @else
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z"/></svg>
            @endif
        </span>
    </button>
</form>
