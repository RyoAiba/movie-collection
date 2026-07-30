const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;

const icons = {
    add: '<svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>',
    collected: '<svg class="size-5 group-hover:hidden" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="m5 12 4 4L19 6"/></svg><svg class="hidden size-5 group-hover:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14"/></svg>',
    loading: '<svg class="size-5 animate-spin" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.2-8.6"/></svg>',
};

const buttonClasses = {
    add: ['border-white/30', 'bg-slate-950/80', 'text-white', 'hover:border-amber-300', 'hover:bg-amber-400', 'hover:text-slate-950'],
    collected: ['border-emerald-300/70', 'bg-emerald-400', 'text-slate-950', 'hover:border-red-300/80', 'hover:bg-red-400'],
};

function showNotice(message, isError = false) {
    const notice = document.createElement('div');
    notice.className = `fixed bottom-5 left-1/2 z-50 -translate-x-1/2 rounded-lg border px-4 py-3 text-sm font-medium shadow-xl transition ${
        isError
            ? 'border-red-300/40 bg-red-950 text-red-100'
            : 'border-emerald-300/40 bg-emerald-950 text-emerald-100'
    }`;
    notice.setAttribute('role', isError ? 'alert' : 'status');
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
    button.setAttribute('aria-label', label);
    button.setAttribute('title', label);
    icon.innerHTML = collected ? icons.collected : icons.add;
    form.dataset.collected = collected ? 'true' : 'false';
}

document.addEventListener('submit', async (event) => {
    const form = event.target.closest('.collection-toggle');

    if (!form) {
        return;
    }

    event.preventDefault();

    const button = form.querySelector('button');
    const icon = form.querySelector('.toggle-icon');
    const wasCollected = form.dataset.collected === 'true';
    const previousIcon = icon.innerHTML;

    button.disabled = true;
    icon.innerHTML = icons.loading;

    try {
        const response = await fetch(form.action, {
            method: wasCollected ? 'DELETE' : 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: wasCollected ? null : JSON.stringify({ tmdb_id: Number(form.dataset.tmdbId) }),
        });
        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.message || '操作に失敗しました。');
        }

        if (wasCollected) {
            form.action = form.dataset.storeUrl;
            renderButton(form, false);
        } else {
            form.action = data.destroy_url;
            renderButton(form, true);
        }

        showNotice(data.message);
    } catch (error) {
        icon.innerHTML = previousIcon;
        showNotice(error.message || '通信に失敗しました。もう一度お試しください。', true);
    } finally {
        button.disabled = false;
    }
});
