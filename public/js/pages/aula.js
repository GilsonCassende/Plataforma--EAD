document.addEventListener('DOMContentLoaded', () => {
    initLessonVideoEmbed();
    initLessonCompletion();
    initLessonTutor();
    initTranscriptGeneration();
    initReadingPremium();
    initPremiumAiChat();
});

function initLessonVideoEmbed() {
    const loadVideoEmbed = (wrapper) => {
        const src = wrapper?.dataset.embed;
        if (!wrapper || !src || wrapper.dataset.loadingVideo === '1') return;

        wrapper.dataset.loadingVideo = '1';

        const existingIframe = wrapper.querySelector('iframe.media-frame');
        if (existingIframe) {
            existingIframe.remove();
        }

        const iframe = document.createElement('iframe');
        iframe.src = src;
        iframe.className = 'media-frame';
        iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
        iframe.setAttribute('allowfullscreen', '');
        iframe.setAttribute('loading', 'eager');
        iframe.setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
        iframe.setAttribute('title', wrapper.dataset.title || 'Reprodutor de vídeo da aula');

        const placeholder = wrapper.querySelector('.placeholder');

        iframe.addEventListener('load', () => {
            wrapper.dataset.loadingVideo = '0';
            if (placeholder) placeholder.remove();
        }, { once: true });

        iframe.addEventListener('error', () => {
            wrapper.dataset.loadingVideo = '0';
        }, { once: true });

        wrapper.appendChild(iframe);
    };

    document.addEventListener('click', (event) => {
        const placeholder = event.target.closest('.video-wrapper .placeholder');
        if (!placeholder) return;

        const wrapper = placeholder.closest('.video-wrapper');
        loadVideoEmbed(wrapper);
    });
}

function initLessonCompletion() {
    const actions = document.querySelector('.aula-actions');
    if (!actions) return;

    const lessonId = Number(actions.dataset.lessonId || 0);
    if (!lessonId) return;

    const form = actions.querySelector('#form-marcar-concluida');
    const completedButton = actions.querySelector('#btn-marcar-concluida.completed');

    if (form) {
        bindMarkForm(form, lessonId);
    }

    if (completedButton) {
        bindUnmarkButton(completedButton, lessonId);
    }
}

function bindMarkForm(form, lessonId) {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const button = form.querySelector('#btn-marcar-concluida');
        const originalText = button?.textContent || '';

        try {
            if (button) {
                button.disabled = true;
                button.textContent = 'Processando...';
            }

            const result = await postLessonAction(window.location.href, new FormData(form));
            if (!result?.sucesso) {
                throw new Error(result?.mensagem || 'Erro ao marcar aula como concluída');
            }

            const completedButton = document.createElement('button');
            completedButton.id = 'btn-marcar-concluida';
            completedButton.className = 'btn btn-success btn-lg completed';
            completedButton.dataset.completed = '1';
            completedButton.textContent = '✓ Concluída';

            form.replaceWith(completedButton);
            bindUnmarkButton(completedButton, lessonId);
            notifyLessonResult(result, true);
        } catch (error) {
            showNotification(error.message || 'Erro ao processar requisição', 'error');
            if (button) {
                button.disabled = false;
                button.textContent = originalText;
            }
        }
    });
}

function bindUnmarkButton(button, lessonId) {
    button.addEventListener('click', async (event) => {
        event.preventDefault();
        if (!window.confirm('Deseja desmarcar esta aula como concluída?')) return;

        const originalText = button.textContent;

        try {
            button.disabled = true;
            button.textContent = 'Processando...';

            const formData = new FormData();
            formData.append('acao', 'desmarcar_concluida');
            formData.append('lesson_id', String(lessonId));
            formData.append('csrf_token', getCsrfToken());

            const result = await postLessonAction(window.location.href, formData);
            if (!result?.sucesso) {
                throw new Error(result?.mensagem || 'Erro ao desmarcar aula');
            }

            const form = createMarkCompleteForm(lessonId);
            button.replaceWith(form);
            bindMarkForm(form, lessonId);
            notifyLessonResult(result, false);
        } catch (error) {
            showNotification(error.message || 'Erro ao processar requisição', 'error');
            button.disabled = false;
            button.textContent = originalText;
        }
    });
}

async function postLessonAction(url, formData) {
    const response = await csrfFetch(url, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-Token': formData.get('csrf_token') || getCsrfToken()
        },
        body: formData
    });

    if (!response.ok) {
        const text = await response.text();
        throw new Error(text || response.statusText);
    }

    return response.json();
}

function createMarkCompleteForm(lessonId) {
    const form = document.createElement('form');
    form.id = 'form-marcar-concluida';
    form.method = 'POST';
    form.className = 'inline-form';
    form.innerHTML = `
        <input type="hidden" name="acao" value="marcar_concluida">
        <input type="hidden" name="csrf_token" value="${escapeAttribute(getCsrfToken())}">
        <input type="hidden" name="lesson_id" value="${lessonId}">
        <button type="submit" class="btn btn-secondary btn-lg" id="btn-marcar-concluida">✓ Marcar como Concluída</button>
    `;
    return form;
}

function notifyLessonResult(result, completed) {
    showNotification(
        result?.mensagem || (completed ? 'Aula marcada como concluída com sucesso!' : 'Aula desmarcada com sucesso.'),
        'success'
    );

    const certificateEvents = Array.isArray(result?.certificate_events) ? result.certificate_events : [];
    certificateEvents.forEach((event) => {
        const certificate = event?.certificate || {};
        const label = certificate?.type === 'module'
            ? `Certificado do módulo liberado: ${certificate?.module_title || 'Módulo'}`
            : `Certificado final liberado: ${certificate?.course_title || 'Curso'}`;
        showNotification(label, 'success');
    });

    const currentStatus = document.querySelector('.lesson-current-status');
    if (currentStatus) {
        currentStatus.textContent = completed ? 'Assistida' : 'Em andamento';
    }

    window.dispatchEvent(new CustomEvent('aulaCompletada', {
        detail: {
            progress: result?.progress,
            course_id: result?.course_id,
            certificate_events: certificateEvents
        }
    }));
}

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function escapeAttribute(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

function initLessonTutor() {
    const container = document.querySelector('[data-lesson-ai]');
    const form = container?.querySelector('[data-ai-form]');
    const responseBox = container?.querySelector('[data-ai-response]');
    const status = container?.querySelector('[data-ai-status]');
    const submit = container?.querySelector('[data-ai-submit]');
    if (!container || !form || !responseBox || !status || !submit) return;

    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        const formData = new FormData(form);
        const question = String(formData.get('pergunta') || '').trim();
        if (question.length < 3) {
            status.textContent = 'Escreva uma pergunta mais completa.';
            responseBox.innerHTML = '<p class="lesson-ai-error">Informe uma dúvida com pelo menos 3 caracteres.</p>';
            responseBox.classList.remove('is-empty');
            return;
        }

        submit.disabled = true;
        status.textContent = 'Analisando sua pergunta...';
        responseBox.innerHTML = '<p class="lesson-ai-loading">Analisando sua pergunta...</p>';
        responseBox.classList.remove('is-empty');

        try {
            const response = await csrfFetch(container.dataset.endpoint || window.location.href, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': formData.get('csrf_token') || getCsrfToken()
                },
                body: formData
            });

            const result = await response.json();
            if (!response.ok || !result?.sucesso) {
                throw new Error(result?.mensagem || 'Não foi possível responder agora.');
            }

            status.textContent = result?.modo_limitado
                ? 'Resposta gerada em modo limitado.'
                : 'Resposta pronta.';
            responseBox.innerHTML = `<div class="lesson-ai-answer">${formatAiText(result.resposta || '')}</div>`;
            if (result?.modo_limitado) {
                const warning = document.createElement('p');
                warning.className = 'lesson-ai-warning';
                warning.textContent = 'Base da aula limitada: a transcrição completa ainda não está disponível, mas a orientação geral de estudo continua ativa.';
                responseBox.prepend(warning);
            }
        } catch (error) {
            status.textContent = 'Falha ao consultar o assistente.';
            responseBox.innerHTML = `<p class="lesson-ai-error">${escapeHtml(error.message || 'Não foi possível responder agora.')}</p>`;
            showNotification(error.message || 'Erro ao consultar o assistente da aula', 'error');
        } finally {
            submit.disabled = false;
        }
    });
}

function initTranscriptGeneration() {
    const button = document.querySelector('[data-generate-transcript]');
    if (!button) return;

    button.addEventListener('click', async () => {
        const lessonId = Number(button.dataset.lessonId || 0);
        if (!lessonId) return;

        const originalText = button.textContent;
        const formData = new FormData();
        formData.append('acao', 'gerar_transcricao_aula');
        formData.append('lesson_id', String(lessonId));
        formData.append('csrf_token', getCsrfToken());

        try {
            button.disabled = true;
            button.textContent = 'Gerando...';

            const response = await csrfFetch(button.dataset.endpoint || window.location.href, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': getCsrfToken()
                },
                body: formData
            });

            const result = await response.json();
            if (!response.ok || !result?.sucesso) {
                throw new Error(result?.mensagem || 'Não foi possível gerar a transcrição.');
            }

            showNotification(result.mensagem || 'Transcrição gerada com sucesso.', 'success');
            window.location.reload();
        } catch (error) {
            showNotification(error.message || 'Erro ao gerar transcrição', 'error');
        } finally {
            button.disabled = false;
            button.textContent = originalText;
        }
    });
}

function formatAiText(text) {
    const normalized = String(text || '')
        .replace(/\r\n?/g, '\n')
        .replace(/Explicação(?=\S)/g, 'Explicação\n\n')
        .replace(/Exemplo(?=\S)/g, '\n\nExemplo\n\n')
        .replace(/Resumo(?=\S)/g, '\n\nResumo\n\n')
        .replace(/\n{3,}/g, '\n\n')
        .trim();

    const sectionLabels = ['Explicação', 'Exemplo', 'Resumo'];
    const lines = normalized.split('\n');
    const sections = [];
    let current = null;

    lines.forEach((rawLine) => {
        const line = rawLine.trim();
        if (!line) {
            if (current && current.content.length > 0) {
                current.content.push('');
            }
            return;
        }

        if (sectionLabels.includes(line)) {
            current = { label: line, content: [] };
            sections.push(current);
            return;
        }

        if (!current) {
            current = { label: 'Explicação', content: [] };
            sections.push(current);
        }

        current.content.push(line);
    });

    if (sections.length === 0) {
        const html = escapeHtml(normalized)
            .replace(/\n{2,}/g, '</p><p>')
            .replace(/\n/g, '<br>');
        return `<p>${html}</p>`;
    }

    return sections
        .map((section) => {
            const body = escapeHtml(section.content.join('\n').trim())
                .replace(/\n{2,}/g, '</p><p>')
                .replace(/\n/g, '<br>');
            return `<section class="lesson-ai-answer-section"><h3>${escapeHtml(section.label)}</h3><p>${body}</p></section>`;
        })
        .join('');
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function initReadingPremium() {
    const premiumCard = document.querySelector('[data-reading-premium]');
    const readingPanel = document.querySelector('.lesson-mode-panel--leitura[data-mode-panel="leitura"]');
    if (!premiumCard || !readingPanel) return;

    const reveal = () => {
        const isVisible = readingPanel.getAttribute('aria-hidden') === 'false' && !readingPanel.classList.contains('is-hidden');
        premiumCard.classList.toggle('is-visible', isVisible);
    };

    reveal();

    const observer = new MutationObserver(reveal);
    observer.observe(readingPanel, {
        attributes: true,
        attributeFilter: ['aria-hidden', 'class']
    });

    document.querySelectorAll('[data-mode-target]').forEach((button) => {
        button.addEventListener('click', () => {
            window.requestAnimationFrame(reveal);
        });
    });
}

function initPremiumAiChat() {
    const widget = document.querySelector('[data-ai-chat-widget]');
    if (!widget) return;

    const input = widget.querySelector('[data-ai-chat-input]');
    const form = widget.querySelector('[data-ai-chat-form]');
    const messages = widget.querySelector('[data-ai-chat-messages]');
    const toggle = widget.querySelector('[data-ai-chat-toggle]');
    const hint = widget.querySelector('[data-ai-chat-input-hint]');
    if (!input || !form || !messages || !toggle) return;

    convertAiChatValidationToInlineHint(form, input, hint, messages);
    enhanceAiChatTextarea(input, form);
    enhanceAiChatMessages(messages);

    toggle.addEventListener('click', () => {
        window.requestAnimationFrame(() => {
            enhanceAiChatMessages(messages);
            scrollChatToBottom(messages);
            input.focus();
        });
    });
}

function enhanceAiChatTextarea(input, form) {
    const resize = () => {
        input.style.height = 'auto';
        input.style.height = `${Math.min(input.scrollHeight, 144)}px`;
    };

    resize();
    input.addEventListener('input', resize);

    input.addEventListener('keydown', (event) => {
        if (event.repeat) {
            return;
        }

        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            form.requestSubmit();
        }
    });
}

function convertAiChatValidationToInlineHint(form, input, hint, messages) {
    const syncHint = () => {
        const normalized = String(input.value || '').replace(/\s+/g, ' ').trim();
        const showHint = normalized.length > 0 && normalized.length < 3;
        if (hint) {
            hint.hidden = !showHint;
        }
    };

    input.addEventListener('input', syncHint);

    form.addEventListener('submit', (event) => {
        const normalized = String(input.value || '').replace(/\s+/g, ' ').trim();
        if (normalized.length >= 3) {
            if (hint) {
                hint.hidden = true;
            }
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
        if (hint) {
            hint.hidden = false;
        }
        input.focus();

        window.setTimeout(() => {
            dedupeInvalidAiChatMessages(messages);
        }, 60);
    }, true);
}

function enhanceAiChatMessages(container) {
    const renderAll = () => {
        container.querySelectorAll('.ai-chat-bubble').forEach((bubble) => {
            scheduleAiBubbleRender(bubble);
        });
        scrollChatToBottom(container);
    };

    renderAll();

    const observer = new MutationObserver(() => {
        renderAll();
    });

    observer.observe(container, {
        childList: true,
        subtree: true,
        characterData: true
    });
}

const aiBubbleRenderTimers = new WeakMap();

function scheduleAiBubbleRender(bubble) {
    if (!(bubble instanceof HTMLElement)) return;
    if (bubble.classList.contains('ai-chat-typing')) return;

    const role = bubble.parentElement?.classList.contains('ai-chat-user') ? 'user' : 'ai';
    const text = bubble.textContent || '';
    const normalized = text.replace(/\r\n?/g, '\n').trim();
    if (!normalized) return;

    if (bubble.dataset.renderedSource === normalized) return;

    const pending = aiBubbleRenderTimers.get(bubble);
    if (pending) {
        window.clearTimeout(pending);
    }

    const timer = window.setTimeout(() => {
        if (!bubble.isConnected || bubble.classList.contains('ai-chat-typing')) return;
        const latest = (bubble.textContent || '').replace(/\r\n?/g, '\n').trim();
        if (!latest) return;

        bubble.innerHTML = role === 'user'
            ? renderUserChatText(latest)
            : renderAiChatMarkdown(latest);
        bubble.dataset.renderedSource = latest;
    }, 220);

    aiBubbleRenderTimers.set(bubble, timer);
}

function renderUserChatText(text) {
    return `<p>${escapeHtml(text).replace(/\n/g, '<br>')}</p>`;
}

function renderAiChatMarkdown(text) {
    const source = normalizeAiChatResponse(text);
    if (!source) return '';

    const fencedBlocks = [];
    const withPlaceholders = source.replace(/```(?:([^\n`]+))?\n([\s\S]*?)```/g, (_, language, code) => {
        const token = `__AI_CHAT_CODE_${fencedBlocks.length}__`;
        fencedBlocks.push({
            language: String(language || '').trim(),
            code: String(code || '').replace(/\n$/, '')
        });
        return token;
    });

    const blocks = withPlaceholders.split(/\n{2,}/).map((block) => block.trim()).filter(Boolean);
    const html = [];
    let blockCount = 0;
    let listCount = 0;
    let headingCount = 0;

    blocks.forEach((block) => {
        const codeIndex = resolveAiChatCodeIndex(block);
        if (codeIndex >= 0) {
            const item = fencedBlocks[codeIndex];
            html.push(`<pre><code>${escapeHtml(item.code)}</code></pre>`);
            blockCount += 1;
            return;
        }

        const lines = block.split('\n').map((line) => line.trim()).filter(Boolean);
        if (!lines.length) return;

        if (lines.every((line) => /^[-*]\s+/.test(line))) {
            html.push(`<ul>${lines.map((line) => `<li>${formatAiChatInline(line.replace(/^[-*]\s+/, ''))}</li>`).join('')}</ul>`);
            blockCount += 1;
            listCount += 1;
            return;
        }

        if (lines.every((line) => /^\d+\.\s+/.test(line))) {
            html.push(`<ol>${lines.map((line) => `<li>${formatAiChatInline(line.replace(/^\d+\.\s+/, ''))}</li>`).join('')}</ol>`);
            blockCount += 1;
            listCount += 1;
            return;
        }

        const heading = lines[0];
        if (/^#{1,3}\s+/.test(heading)) {
            const level = Math.min((heading.match(/^#{1,3}/) || ['#'])[0].length + 3, 5);
            const title = formatAiChatInline(heading.replace(/^#{1,3}\s+/, ''));
            html.push(`<h${level}>${title}</h${level}>`);
            blockCount += 1;
            headingCount += 1;
            const rest = lines.slice(1);
            if (rest.length) {
                html.push(`<p>${formatAiChatInline(rest.join('\n')).replace(/\n/g, '<br>')}</p>`);
                blockCount += 1;
            }
            return;
        }

        if (/^(Explicação|Exemplo|Resumo|O que você vai aprender|Explicação completa|Exemplos práticos|Pontos importantes|Resumo final|Possíveis dúvidas)\s*:?\s*$/i.test(heading)) {
            html.push(`<h5>${formatAiChatInline(heading.replace(/:$/, ''))}</h5>`);
            blockCount += 1;
            headingCount += 1;
            const rest = lines.slice(1);
            if (rest.length) {
                html.push(`<p>${formatAiChatInline(rest.join('\n')).replace(/\n/g, '<br>')}</p>`);
                blockCount += 1;
            }
            return;
        }

        html.push(`<p>${formatAiChatInline(lines.join('\n')).replace(/\n/g, '<br>')}</p>`);
        blockCount += 1;
    });

    const variant = classifyAiChatResponse({
        source,
        blockCount,
        listCount,
        headingCount
    });

    return `<div class="ai-chat-rich ai-chat-rich--${variant}">${html.join('')}</div>`;
}

function resolveAiChatCodeIndex(token) {
    const match = token.match(/^__AI_CHAT_CODE_(\d+)__$/);
    return match ? Number(match[1]) : -1;
}

function normalizeAiChatResponse(text) {
    let source = String(text || '').replace(/\r\n?/g, '\n').trim();
    if (!source) return '';

    const sectionLabels = '(?:Explicação|Exemplo|Resumo|O que você vai aprender|Explicação completa|Exemplos práticos|Pontos importantes|Resumo final|Possíveis dúvidas)';
    source = source.replace(new RegExp(`([.!?])\\s*(${sectionLabels})(?=[:A-ZÁÀÂÃÉÊÍÓÔÕÚÇ])`, 'giu'), '$1\n\n$2');
    source = source.replace(new RegExp(`([a-záàâãéêíóôõúç])(${sectionLabels})(?=[:A-ZÁÀÂÃÉÊÍÓÔÕÚÇ])`, 'gu'), '$1\n\n$2');
    source = source.replace(new RegExp(`(^|\\n)(${sectionLabels})(\\s*:?)\\s*`, 'giu'), '$1$2$3\n');
    source = source.replace(new RegExp(`(${sectionLabels})(?=[A-ZÁÀÂÃÉÊÍÓÔÕÚÇa-záàâãéêíóôõúç])`, 'giu'), '$1\n');
    source = source.replace(/([.!?])\s+([-*]\s+)/g, '$1\n$2');
    source = source.replace(/([.!?])\s+(\d+\.\s+)/g, '$1\n$2');
    source = source.replace(/:\s*-\s+/g, ':\n- ');
    source = source.replace(/\n{3,}/g, '\n\n');

    return source.trim();
}

function formatAiChatInline(text) {
    let value = escapeHtml(text);
    value = value.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    value = value.replace(/`([^`]+)`/g, '<code>$1</code>');
    return value;
}

function classifyAiChatResponse(meta) {
    const source = String(meta?.source || '').trim();
    const blockCount = Number(meta?.blockCount || 0);
    const listCount = Number(meta?.listCount || 0);
    const headingCount = Number(meta?.headingCount || 0);
    const charCount = source.length;

    if (charCount <= 220 && blockCount <= 2 && listCount === 0 && headingCount === 0) {
        return 'compact';
    }

    if (listCount > 0 || /(?:passo a passo|etapas|como fazer|passos)/i.test(source)) {
        return 'steps';
    }

    if (headingCount > 0 || blockCount >= 4 || charCount > 520) {
        return 'study';
    }

    return 'default';
}

function scrollChatToBottom(container) {
    container.scrollTop = container.scrollHeight;
}

function dedupeInvalidAiChatMessages(container) {
    const items = Array.from(container.querySelectorAll('.ai-chat-message.ai-chat-assistant'));
    const invalidMessages = items.filter((item) => {
        const text = item.querySelector('.ai-chat-bubble')?.textContent?.replace(/\s+/g, ' ').trim() || '';
        return text === 'Escreva uma pergunta com pelo menos 3 caracteres.';
    });

    if (invalidMessages.length <= 1) {
        return;
    }

    invalidMessages.slice(0, -1).forEach((item) => {
        item.remove();
    });
}
