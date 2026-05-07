document.addEventListener('DOMContentLoaded', () => {
    initLessonModes();
});

function initLessonModes() {
    const switcher = document.querySelector('[data-lesson-mode-switch]');
    if (!switcher) return;

    const buttons = Array.from(switcher.querySelectorAll('.mode-btn[data-mode-target]'));
    const panels = Array.from(document.querySelectorAll('.lesson-player-card [data-mode-panel]'));
    if (buttons.length === 0 || panels.length === 0) return;

    const allowedModes = buttons
        .filter((button) => !button.disabled)
        .map((button) => button.dataset.modeTarget)
        .filter(Boolean);
    const savedMode = readSavedLessonMode();
    const preferredMode = switcher.dataset.initialMode || '';
    const initialMode = allowedModes.includes(savedMode) ? savedMode : 'video';
    const resolvedInitialMode = allowedModes.includes(preferredMode) ? preferredMode : initialMode;

    buttons.forEach((button) => {
        button.addEventListener('click', () => {
            const nextMode = button.dataset.modeTarget || 'video';
            if (button.disabled || !allowedModes.includes(nextMode)) {
                return;
            }

            applyLessonMode(nextMode, buttons, panels);
        });
    });

    applyLessonMode(resolvedInitialMode, buttons, panels);
    initLessonAiGenerationForm();
}

function initLessonAiGenerationForm() {
    const form = document.querySelector('[data-lesson-ai-generate-form]');
    if (!form) return;

    const button = form.querySelector('[data-lesson-ai-generate-button]');
    const loading = form.querySelector('[data-lesson-ai-loading]');

    form.addEventListener('submit', () => {
        if (button) {
            button.disabled = true;
            button.setAttribute('aria-disabled', 'true');
        }
        if (loading) {
            loading.classList.remove('is-hidden');
        }
    });
}

function applyLessonMode(mode, buttons, panels) {
    const lessonPlayer = document.querySelector('.aula-player');

    buttons.forEach((button) => {
        const isActive = button.dataset.modeTarget === mode;
        button.classList.toggle('is-active', isActive);
        button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    });

    panels.forEach((panel) => {
        const isActive = panel.dataset.modePanel === mode;
        panel.classList.toggle('is-hidden', !isActive);
        panel.setAttribute('aria-hidden', isActive ? 'false' : 'true');
    });

    if (mode === 'video') {
        ensureVideoPanelReady();
    } else {
        pauseMediaInside(document.querySelector('[data-mode-panel="video"]'));
    }

    if (mode !== 'economico') {
        pauseMediaInside(document.querySelector('[data-mode-panel="economico"]'));
    }

    if (lessonPlayer) {
        lessonPlayer.classList.toggle('is-reading-mode', mode === 'leitura');
    }

    toggleEconomicBanner(mode);

    try {
        window.localStorage.setItem('lessonMode', mode);
    } catch (error) {
        // Ignorar indisponibilidade de storage sem afetar a aula.
    }
}

function ensureVideoPanelReady() {
    const videoPanel = document.querySelector('[data-mode-panel="video"]');
    if (!videoPanel) return;

    const mediaElements = videoPanel.querySelectorAll('iframe[data-src], video[data-src], source[data-src]');
    mediaElements.forEach((element) => {
        const src = element.getAttribute('data-src');
        if (!src || element.getAttribute('src')) {
            return;
        }

        element.setAttribute('src', src);

        if (element.tagName === 'SOURCE') {
            const parentVideo = element.parentElement;
            if (parentVideo && parentVideo.tagName === 'VIDEO') {
                parentVideo.load();
            }
        }
    });
}

function pauseMediaInside(container) {
    if (!container) return;

    container.querySelectorAll('video, audio').forEach((media) => {
        try {
            media.pause();
        } catch (error) {
            // Ignorar falhas de pausa para manter a troca de modo resiliente.
        }
    });
}

function toggleEconomicBanner(mode) {
    document.querySelectorAll('[data-mode-banner="economico"]').forEach((banner) => {
        banner.hidden = mode !== 'economico';
    });
}

function readSavedLessonMode() {
    try {
        return window.localStorage.getItem('lessonMode') || 'video';
    } catch (error) {
        return 'video';
    }
}
