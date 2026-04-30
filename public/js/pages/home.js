document.addEventListener('DOMContentLoaded', () => {
    initHomeHeroAnimation();
    initHomeCarousel();
    initHomeDescriptionModal();
});

function initHomeHeroAnimation() {
    try {
        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        const btn = document.querySelector('.btn-hero');
        if (!btn) return;
        window.setTimeout(() => btn.classList.add('animate'), 700);
    } catch (error) {
        console.warn('Hero animation error', error);
    }
}

function initHomeCarousel() {
    const track = document.getElementById('carouselTrack');
    const indicatorsContainer = document.getElementById('carouselIndicators');
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    if (!track || !indicatorsContainer) return;

    const slides = Array.from(track.querySelectorAll('.carousel-slide'));
    if (!slides.length) return;

    let index = 0;
    let autoplay = true;
    let timer = null;

    const updateIndicators = () => {
        Array.from(indicatorsContainer.children).forEach((button, indicatorIndex) => {
            button.classList.toggle('active', indicatorIndex === index);
            button.setAttribute('aria-selected', String(indicatorIndex === index));
        });

        slides.forEach((slide, slideIndex) => {
            slide.setAttribute('aria-hidden', String(slideIndex !== index));
        });
    };

    const goTo = (nextIndex) => {
        index = (nextIndex + slides.length) % slides.length;
        track.style.transform = `translateX(${-index * 100}%)`;
        updateIndicators();
    };

    const resetTimer = () => {
        window.clearTimeout(timer);
        if (!autoplay) return;
        timer = window.setTimeout(() => {
            goTo(index + 1);
            resetTimer();
        }, 6000);
    };

    slides.forEach((slide, slideIndex) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.setAttribute('role', 'tab');
        button.setAttribute('aria-label', `Ir para slide ${slideIndex + 1}`);
        button.setAttribute('aria-selected', String(slideIndex === 0));
        button.addEventListener('click', () => {
            goTo(slideIndex);
            resetTimer();
        });
        indicatorsContainer.appendChild(button);
    });

    const setPausedState = (paused) => {
        autoplay = !paused;
        if (paused) {
            window.clearTimeout(timer);
            return;
        }
        resetTimer();
    };

    nextBtn?.addEventListener('click', () => {
        goTo(index + 1);
        resetTimer();
    });

    prevBtn?.addEventListener('click', () => {
        goTo(index - 1);
        resetTimer();
    });

    document.querySelector('.hero-carousel')?.addEventListener('mouseenter', () => setPausedState(true));
    document.querySelector('.hero-carousel')?.addEventListener('mouseleave', () => setPausedState(false));

    document.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft') {
            goTo(index - 1);
            resetTimer();
        }
        if (event.key === 'ArrowRight') {
            goTo(index + 1);
            resetTimer();
        }
    });

    goTo(0);
    resetTimer();
}

function initHomeDescriptionModal() {
    const enableModal = window.matchMedia('(max-width: 768px)').matches || ('ontouchstart' in window) || navigator.maxTouchPoints > 0;
    if (!enableModal) return;

    document.querySelectorAll('.descricao[data-full]').forEach((element) => {
        element.style.cursor = 'pointer';
        element.addEventListener('click', (event) => {
            event.preventDefault();
            if (typeof openModal !== 'function') return;

            const full = element.getAttribute('data-full') || element.textContent || '';
            const title = element.getAttribute('data-title') || '';
            const body = `<p class="modal-description">${escapeHtml(full)}</p>`;
            openModal({ title, body });
        });
    });
}

function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
