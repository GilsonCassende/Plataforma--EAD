document.addEventListener('DOMContentLoaded', () => {
    initLessonModes();
    initBrowserTtsPlayers();
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

    container.querySelectorAll('[data-browser-tts]').forEach((player) => {
        try {
            player.__ttsController?.stop();
        } catch (error) {
            // Ignorar falhas de speech synthesis para manter a troca de modo resiliente.
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

function initBrowserTtsPlayers() {
    const players = Array.from(document.querySelectorAll('[data-browser-tts]'));
    if (players.length === 0) return;

    const synthesis = window.speechSynthesis || null;
    const buildChunks = (text) => {
        const normalized = String(text || '').replace(/\s+/g, ' ').trim();
        if (!normalized) return [];

        const sentences = normalized.match(/[^.!?]+[.!?]?/g) || [normalized];
        const chunks = [];
        let buffer = '';

        sentences.forEach((sentence) => {
            const part = sentence.trim();
            if (!part) return;

            const candidate = buffer ? `${buffer} ${part}` : part;
            if (candidate.length <= 420) {
                buffer = candidate;
                return;
            }

            if (buffer) {
                chunks.push(buffer);
            }

            if (part.length <= 420) {
                buffer = part;
                return;
            }

            const words = part.split(' ');
            buffer = '';
            words.forEach((word) => {
                const wordCandidate = buffer ? `${buffer} ${word}` : word;
                if (wordCandidate.length <= 420) {
                    buffer = wordCandidate;
                    return;
                }

                if (buffer) {
                    chunks.push(buffer);
                }
                buffer = word;
            });
        });

        if (buffer) {
            chunks.push(buffer);
        }

        return chunks;
    };

    players.forEach((player) => {
        const textField = player.querySelector('[data-tts-text]');
        const status = player.querySelector('[data-tts-status]');
        const playButton = player.querySelector('[data-tts-play]');
        const pauseButton = player.querySelector('[data-tts-pause]');
        const resumeButton = player.querySelector('[data-tts-resume]');
        const stopButton = player.querySelector('[data-tts-stop]');
        const transcript = String(textField?.value || '').trim();

        const setButtonState = (button, disabled) => {
            if (!button) return;
            button.disabled = disabled;
            button.setAttribute('aria-disabled', disabled ? 'true' : 'false');
        };

        const setStatus = (message) => {
            if (status) {
                status.textContent = message;
            }
        };

        const syncIdleState = () => {
            setButtonState(playButton, transcript === '' || synthesis === null);
            setButtonState(pauseButton, true);
            setButtonState(resumeButton, true);
            setButtonState(stopButton, true);
        };

        if (transcript === '') {
            setStatus('A transcrição desta aula está vazia, por isso o áudio econômico não pode ser narrado.');
            syncIdleState();
            return;
        }

        if (synthesis === null) {
            setStatus('Este navegador não suporta reprodução por voz neste modo econômico.');
            syncIdleState();
            return;
        }

        let currentUtterance = null;
        let currentChunks = [];
        let currentChunkIndex = 0;
        let stopping = false;

        const syncSpeakingState = () => {
            const speaking = synthesis.speaking;
            const paused = synthesis.paused;
            setButtonState(playButton, speaking && !paused);
            setButtonState(pauseButton, !speaking || paused);
            setButtonState(resumeButton, !paused);
            setButtonState(stopButton, !speaking && !paused);
        };

        const stopPlayback = () => {
            stopping = true;
            if (synthesis.speaking || synthesis.paused) {
                synthesis.cancel();
            }
            currentUtterance = null;
            currentChunks = [];
            currentChunkIndex = 0;
            setStatus('Áudio econômico parado.');
            syncIdleState();
        };

        const speakCurrentChunk = () => {
            if (currentChunkIndex >= currentChunks.length) {
                currentUtterance = null;
                currentChunks = [];
                currentChunkIndex = 0;
                setStatus('Narração concluída.');
                syncIdleState();
                return;
            }

            const utterance = new SpeechSynthesisUtterance(currentChunks[currentChunkIndex]);
            utterance.lang = document.documentElement.lang === 'pt-BR' ? 'pt-BR' : 'pt-PT';
            utterance.rate = 1;
            utterance.pitch = 1;
            currentUtterance = utterance;

            utterance.addEventListener('start', () => {
                setStatus('Narrando a aula em modo econômico...');
                syncSpeakingState();
            });

            utterance.addEventListener('pause', () => {
                setStatus('Áudio econômico pausado.');
                syncSpeakingState();
            });

            utterance.addEventListener('resume', () => {
                setStatus('Continuando a narração da aula...');
                syncSpeakingState();
            });

            utterance.addEventListener('end', () => {
                if (stopping) {
                    stopping = false;
                    return;
                }

                currentChunkIndex += 1;
                if (currentChunkIndex < currentChunks.length) {
                    speakCurrentChunk();
                    return;
                }

                currentUtterance = null;
                currentChunks = [];
                currentChunkIndex = 0;
                setStatus('Narração concluída.');
                syncIdleState();
            });

            utterance.addEventListener('error', () => {
                currentUtterance = null;
                currentChunks = [];
                currentChunkIndex = 0;
                setStatus('Não foi possível narrar esta aula no modo econômico agora.');
                syncIdleState();
            });

            synthesis.speak(utterance);
            syncSpeakingState();
        };

        playButton?.addEventListener('click', () => {
            if (transcript === '') {
                setStatus('A transcrição desta aula está vazia, por isso o áudio econômico não pode ser narrado.');
                return;
            }

            stopping = false;
            if (synthesis.speaking || synthesis.paused) {
                synthesis.cancel();
            }

            currentChunks = buildChunks(transcript);
            currentChunkIndex = 0;
            if (currentChunks.length === 0) {
                setStatus('A transcrição desta aula está vazia, por isso o áudio econômico não pode ser narrado.');
                syncIdleState();
                return;
            }

            speakCurrentChunk();
        });

        pauseButton?.addEventListener('click', () => {
            if (!synthesis.speaking || synthesis.paused) return;
            synthesis.pause();
            syncSpeakingState();
        });

        resumeButton?.addEventListener('click', () => {
            if (!synthesis.paused) return;
            synthesis.resume();
            syncSpeakingState();
        });

        stopButton?.addEventListener('click', stopPlayback);

        player.__ttsController = {
            stop: stopPlayback,
        };

        syncIdleState();
    });
}
