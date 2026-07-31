const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

function initializeTrailerCarousel() {
    const carousel = document.querySelector('.trailer-carousel');

    if (!carousel || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    const trailers = JSON.parse(carousel.dataset.trailers || '[]');

    if (!trailers.length) {
        return;
    }

    const playerLayer = carousel.querySelector('.trailer-player');
    const backdrop = carousel.querySelector('.trailer-backdrop');
    const title = carousel.querySelector('.trailer-title');
    let player;
    let currentIndex = 0;
    let switchTimer;
    let revealTimer;

    const showBackdrop = () => {
        window.clearTimeout(revealTimer);
        playerLayer.classList.remove('opacity-100');
        playerLayer.classList.add('opacity-0');
    };

    const updateSlideContent = (trailer) => {
        title.textContent = trailer.title;

        if (trailer.backdrop_url) {
            backdrop.src = trailer.backdrop_url;
            backdrop.classList.remove('hidden');
        } else {
            backdrop.removeAttribute('src');
            backdrop.classList.add('hidden');
        }
    };

    const scheduleNext = () => {
        window.clearTimeout(switchTimer);

        if (trailers.length < 2) {
            return;
        }

        switchTimer = window.setTimeout(() => {
            showBackdrop();
            player?.stopVideo();
            currentIndex = (currentIndex + 1) % trailers.length;
            const nextTrailer = trailers[currentIndex];
            updateSlideContent(nextTrailer);

            window.setTimeout(() => {
                player?.loadVideoById(nextTrailer.video_id);
                scheduleNext();
            }, 350);
        }, 10000);
    };

    window.onYouTubeIframeAPIReady = () => {
        player = new window.YT.Player('trailer-youtube-player', {
            width: '100%',
            height: '100%',
            videoId: trailers[0].video_id,
            playerVars: {
                autoplay: 1,
                controls: 0,
                disablekb: 1,
                fs: 0,
                iv_load_policy: 3,
                modestbranding: 1,
                mute: 1,
                playsinline: 1,
                rel: 0,
            },
            events: {
                onReady(event) {
                    event.target.mute();
                    event.target.playVideo();
                    scheduleNext();
                },
                onStateChange(event) {
                    if (event.data === window.YT.PlayerState.PLAYING) {
                        window.clearTimeout(revealTimer);
                        revealTimer = window.setTimeout(() => {
                            if (player?.getPlayerState() === window.YT.PlayerState.PLAYING) {
                                playerLayer.classList.remove('opacity-0');
                                playerLayer.classList.add('opacity-100');
                            }
                        }, 700);
                    }
                },
                onError() {
                    showBackdrop();
                    scheduleNext();
                },
            },
        });
    };

    const loadYouTubeApi = () => {
        if (window.YT?.Player) {
            window.onYouTubeIframeAPIReady();

            return;
        }

        const script = document.createElement('script');
        script.src = 'https://www.youtube.com/iframe_api';
        script.async = true;
        document.head.append(script);
    };

    if ('requestIdleCallback' in window) {
        window.requestIdleCallback(loadYouTubeApi, { timeout: 1500 });
    } else {
        window.setTimeout(loadYouTubeApi, 500);
    }
}

initializeTrailerCarousel();

const icons = {
    add: '<svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z"/></svg>',
    collected: '<svg class="size-5" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="1.5"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z"/></svg>',
};

const buttonClasses = {
    add: ['border-white/80', 'bg-white/85', 'text-rose-600', 'shadow-black/40', 'hover:bg-white'],
    collected: ['border-rose-300/80', 'bg-rose-500', 'text-white', 'shadow-rose-950/40', 'hover:bg-rose-400'],
};

function showCollectionError(message) {
    const notice = document.createElement('div');
    notice.className = 'fixed bottom-5 left-1/2 z-50 -translate-x-1/2 rounded-lg border border-red-300/40 bg-red-950 px-4 py-3 text-sm font-medium text-red-100 shadow-xl transition';
    notice.setAttribute('role', 'alert');
    notice.textContent = message;
    document.body.append(notice);

    window.setTimeout(() => notice.remove(), 2800);
}

function renderButton(form, collected) {
    const button = form.querySelector('button');
    const icon = form.querySelector('.toggle-icon');
    const removeClasses = collected ? buttonClasses.add : buttonClasses.collected;
    const addClasses = collected ? buttonClasses.collected : buttonClasses.add;
    const label = collected ? 'コレクションから外す' : 'コレクションに追加';

    button.classList.remove(...removeClasses);
    button.classList.add(...addClasses);
    button.setAttribute('aria-pressed', collected ? 'true' : 'false');
    button.setAttribute('aria-label', label);
    button.setAttribute('title', label);
    icon.innerHTML = collected ? icons.collected : icons.add;
    form.dataset.collected = collected ? 'true' : 'false';
}

function collectionFormsFor(tmdbId) {
    return document.querySelectorAll(`.collection-toggle[data-tmdb-id="${tmdbId}"]`);
}

const collectionStates = new Map();

function collectionStateFor(form) {
    const tmdbId = Number(form.dataset.tmdbId);

    if (!collectionStates.has(tmdbId)) {
        const collected = form.dataset.collected === 'true';

        collectionStates.set(tmdbId, {
            tmdbId,
            confirmed: collected,
            desired: collected,
            destroyUrl: collected ? form.action : null,
            storeUrl: form.dataset.storeUrl,
            processing: false,
            version: 0,
        });
    }

    return collectionStates.get(tmdbId);
}

function renderCollectionVisualState(tmdbId, collected) {
    collectionFormsFor(tmdbId).forEach((form) => renderButton(form, collected));
}

function renderCollectionState(tmdbId, collected, destroyUrl = null) {
    collectionFormsFor(tmdbId).forEach((form) => {
        form.action = collected ? destroyUrl : form.dataset.storeUrl;
        renderButton(form, collected);
    });

    const state = collectionStates.get(Number(tmdbId));

    if (state) {
        state.confirmed = collected;
        state.desired = collected;
        state.destroyUrl = collected ? destroyUrl : null;
    }
}

function updateCollectionFormActions(state) {
    collectionFormsFor(state.tmdbId).forEach((form) => {
        form.action = state.confirmed ? state.destroyUrl : form.dataset.storeUrl;
    });
}

async function syncCollectionState(state) {
    if (state.processing) {
        return;
    }

    state.processing = true;

    try {
        while (state.confirmed !== state.desired) {
            const targetState = state.desired;
            const requestVersion = state.version;

            try {
                const response = await fetch(targetState ? state.storeUrl : state.destroyUrl, {
                    method: targetState ? 'POST' : 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: targetState ? JSON.stringify({ tmdb_id: state.tmdbId }) : null,
                });
                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    throw new Error(data.message || 'コレクションを更新できませんでした。');
                }

                state.confirmed = targetState;
                state.destroyUrl = targetState ? data.destroy_url : null;
                updateCollectionFormActions(state);
            } catch (error) {
                const isLatestIntent = state.version === requestVersion && state.desired === targetState;

                if (isLatestIntent) {
                    state.desired = state.confirmed;
                    renderCollectionVisualState(state.tmdbId, state.confirmed);
                }

                showCollectionError(error.message || '通信に失敗しました。もう一度お試しください。');
                break;
            }
        }
    } finally {
        state.processing = false;

        if (state.confirmed !== state.desired) {
            syncCollectionState(state);
        }
    }
}

function updateScrollFades(scroller) {
    const container = scroller.parentElement;
    const leftFade = container.querySelector('.scroll-fade-left');
    const rightFade = container.querySelector('.scroll-fade-right');
    const maxScrollLeft = scroller.scrollWidth - scroller.clientWidth;
    const canScroll = maxScrollLeft > 1;
    const isAtLeft = scroller.scrollLeft <= 1;
    const isAtRight = scroller.scrollLeft >= maxScrollLeft - 1;

    leftFade.classList.toggle('opacity-0', !canScroll || isAtLeft);
    leftFade.classList.toggle('opacity-100', canScroll && !isAtLeft);
    rightFade.classList.toggle('opacity-0', !canScroll || isAtRight);
    rightFade.classList.toggle('opacity-100', canScroll && !isAtRight);
}

const fadeScrollers = document.querySelectorAll('.scroll-fade-scroller');

fadeScrollers.forEach((scroller) => {
    updateScrollFades(scroller);
    scroller.addEventListener('scroll', () => updateScrollFades(scroller), { passive: true });
});

window.addEventListener('resize', () => {
    fadeScrollers.forEach(updateScrollFades);
});

document.querySelectorAll('.review-body').forEach((reviewBody) => {
    const button = reviewBody.nextElementSibling;

    if (button && reviewBody.scrollHeight > reviewBody.clientHeight + 1) {
        button.classList.remove('hidden');
    }
});

document.addEventListener('click', (event) => {
    const button = event.target.closest('.review-expand');

    if (!button) {
        return;
    }

    const reviewBody = button.previousElementSibling;
    const isExpanded = !reviewBody.classList.contains('line-clamp-5');

    reviewBody.classList.toggle('line-clamp-5', isExpanded);
    button.textContent = isExpanded ? '続きを読む' : '閉じる';
    button.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
});

document.addEventListener('submit', async (event) => {
    const form = event.target.closest('.movie-review-form');

    if (!form) {
        return;
    }

    event.preventDefault();

    const button = form.querySelector('button[type="submit"]');
    const message = form.querySelector('.review-save-message');
    const formData = new FormData(form);

    button.disabled = true;
    message.classList.add('hidden');

    try {
        const response = await fetch(form.action, {
            method: 'PUT',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                rating: Number(formData.get('rating')) || null,
                review: formData.get('review'),
            }),
        });
        const data = await response.json();

        if (!response.ok) {
            const validationMessage = data.errors ? Object.values(data.errors).flat()[0] : null;
            throw new Error(validationMessage || data.message || 'レビューを保存できませんでした。');
        }

        message.textContent = data.message;
        message.className = 'review-save-message mt-2 text-xs text-emerald-400';

        renderCollectionState(data.tmdb_id, true, data.destroy_url);
    } catch (error) {
        message.textContent = error.message || '通信に失敗しました。もう一度お試しください。';
        message.className = 'review-save-message mt-2 text-xs text-red-400';
    } finally {
        button.disabled = false;
    }
});

document.addEventListener('submit', async (event) => {
    const form = event.target.closest('.collection-toggle');

    if (!form) {
        return;
    }

    event.preventDefault();

    const state = collectionStateFor(form);

    state.desired = !state.desired;
    state.version += 1;
    renderCollectionVisualState(state.tmdbId, state.desired);
    syncCollectionState(state);
});
