document.addEventListener('DOMContentLoaded', () => {
    initAiChatWidget();
});

const AI_CHAT_HISTORY_LIMIT = 10;

function initAiChatWidget() {
    const widget = document.querySelector('[data-ai-chat-widget]');
    if (!widget) return;

    const toggle = widget.querySelector('[data-ai-chat-toggle]');
    const close = widget.querySelector('[data-ai-chat-close]');
    const clear = widget.querySelector('[data-ai-chat-clear]');
    const messages = widget.querySelector('[data-ai-chat-messages]');
    const form = widget.querySelector('[data-ai-chat-form]');
    const input = widget.querySelector('[data-ai-chat-input]');
    const submit = widget.querySelector('[data-ai-chat-submit]');
    const lessonId = Number(widget.dataset.lessonId || 0);
    const assistantName = String(widget.dataset.assistantName || 'Assistente IA').trim() || 'Assistente IA';
    const assistantAvatar = String(widget.dataset.assistantAvatar || 'AI').trim() || 'AI';
    const assistantGreeting = String(widget.dataset.assistantGreeting || 'Posso ajudar com esta aula ou com estratégias para estudar melhor. O que você quer saber?').trim();

    if (!toggle || !close || !clear || !messages || !form || !input || !submit || !lessonId) return;

    let isWaitingResponse = false;
    let history = loadHistory(lessonId);

    renderHistory(messages, history, assistantGreeting, assistantAvatar);

    toggle.addEventListener('click', () => {
        widget.classList.add('ai-chat-open');
        scrollMessagesToBottom(messages);
        window.setTimeout(() => input.focus(), 120);
    });

    close.addEventListener('click', () => {
        widget.classList.remove('ai-chat-open');
        toggle.focus();
    });

        clear.addEventListener('click', () => {
        history = [];
        saveHistory(lessonId, history);
        renderHistory(messages, history, assistantGreeting, assistantAvatar);
        input.focus();
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (isWaitingResponse) return;

        const question = sanitizeInput(input.value);
        if (question.length < 3) {
            appendMessage(messages, 'ai', 'Escreva uma pergunta com pelo menos 3 caracteres.');
            input.focus();
            return;
        }

        history.push({ role: 'user', text: question });
        history = trimHistory(history);
        saveHistory(lessonId, history);
        appendMessage(messages, 'user', question);

        input.value = '';
        setPendingState(input, submit, true);
        isWaitingResponse = true;

        const typingNode = appendMessage(messages, 'ai', `${assistantName} está digitando...`, false, true, assistantAvatar);

        try {
            const response = await fetch(widget.dataset.endpoint || '/index.php?page=perguntar-ia', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': getAiChatCsrfToken()
                },
                body: JSON.stringify({
                    lesson_id: lessonId,
                    pergunta: question,
                    historico: trimHistory(history),
                    csrf_token: getAiChatCsrfToken()
                })
            });

            const result = await response.json().catch(() => ({}));
            const realisticDelay = 300 + Math.floor(Math.random() * 500);
            await wait(realisticDelay);

            if (!response.ok || !result?.sucesso) {
                throw new Error(result?.mensagem || 'Não foi possível responder agora.');
            }

            const answer = String(result.resposta || 'Resposta vazia.');
            const bubble = replaceTypingWithEmptyBubble(typingNode);
            await typeMessage(bubble, answer, 10, messages);

            history.push({ role: 'ai', text: answer });
            history = trimHistory(history);
            saveHistory(lessonId, history);
        } catch (error) {
            const message = String(error.message || 'Não foi possível responder agora.');
            replaceTypingWithText(typingNode, message);
            history.push({ role: 'ai', text: message });
            history = trimHistory(history);
            saveHistory(lessonId, history);
        } finally {
            setPendingState(input, submit, false);
            isWaitingResponse = false;
            input.focus();
            scrollMessagesToBottom(messages);
        }
    });
}

function renderHistory(container, history, assistantGreeting, assistantAvatar) {
    container.textContent = '';

    if (!Array.isArray(history) || history.length === 0) {
        appendMessage(
            container,
            'ai',
            assistantGreeting || 'Posso ajudar com esta aula ou com estratégias para estudar melhor. O que você quer saber?',
            true,
            false,
            assistantAvatar
        );
        return;
    }

    history.forEach((item) => {
        const role = item?.role === 'user' ? 'user' : 'ai';
        const text = typeof item?.text === 'string' ? item.text : '';
        if (text.trim() !== '') {
            appendMessage(container, role, text, true, false, assistantAvatar);
        }
    });

    scrollMessagesToBottom(container);
}

function appendMessage(container, role, text, animate = true, isTyping = false, assistantAvatar = 'AI') {
    const item = document.createElement('div');
    item.className = `ai-chat-message ${role === 'user' ? 'ai-chat-user' : 'ai-chat-assistant'}${animate ? ' ai-chat-message-enter' : ''}`;

    if (role !== 'user') {
        const avatar = document.createElement('span');
        avatar.className = 'ai-chat-avatar';
        avatar.textContent = assistantAvatar;
        item.appendChild(avatar);
    }

    const bubble = document.createElement('div');
    bubble.className = `ai-chat-bubble${isTyping ? ' ai-chat-typing' : ''}`;
    bubble.textContent = text;
    item.appendChild(bubble);

    container.appendChild(item);
    scrollMessagesToBottom(container);
    return item;
}

function replaceTypingWithEmptyBubble(node) {
    const bubble = node?.querySelector('.ai-chat-bubble');
    if (!bubble) {
        throw new Error('Não foi possível preparar a bolha de resposta.');
    }

    bubble.textContent = '';
    bubble.classList.remove('ai-chat-typing');
    return bubble;
}

function replaceTypingWithText(node, text) {
    const bubble = replaceTypingWithEmptyBubble(node);
    bubble.textContent = text;
}

function typeMessage(element, text, speed = 10, scrollContainer = null) {
    return new Promise((resolve) => {
        let index = 0;

        function typing() {
            if (index < text.length) {
                element.textContent += text.charAt(index);
                index += 1;
                if (scrollContainer) {
                    scrollMessagesToBottom(scrollContainer);
                }
                window.setTimeout(typing, speed);
                return;
            }

            resolve();
        }

        typing();
    });
}

function loadHistory(lessonId) {
    try {
        const stored = window.localStorage.getItem(getHistoryKey(lessonId));
        const parsed = stored ? JSON.parse(stored) : [];
        return Array.isArray(parsed) ? trimHistory(parsed.map(normalizeHistoryItem).filter(Boolean)) : [];
    } catch (error) {
        return [];
    }
}

function saveHistory(lessonId, history) {
    try {
        window.localStorage.setItem(getHistoryKey(lessonId), JSON.stringify(trimHistory(history)));
    } catch (error) {
        // Ignorar falhas de storage para não quebrar o chat.
    }
}

function getHistoryKey(lessonId) {
    return `ai_chat_history_${lessonId}`;
}

function trimHistory(history) {
    const safeHistory = Array.isArray(history) ? history.map(normalizeHistoryItem).filter(Boolean) : [];
    return safeHistory.slice(-AI_CHAT_HISTORY_LIMIT);
}

function normalizeHistoryItem(item) {
    if (!item || typeof item !== 'object') return null;

    const role = item.role === 'user' ? 'user' : 'ai';
    const text = sanitizeInput(String(item.text || ''));
    if (text === '') return null;

    return {
        role,
        text: text.slice(0, 1000)
    };
}

function setPendingState(input, submit, pending) {
    input.disabled = pending;
    submit.disabled = pending;
}

function scrollMessagesToBottom(container) {
    container.scrollTop = container.scrollHeight;
}

function sanitizeInput(value) {
    return String(value || '')
        .replace(/\s+/g, ' ')
        .trim();
}

function wait(ms) {
    return new Promise((resolve) => {
        window.setTimeout(resolve, ms);
    });
}

function getAiChatCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}
