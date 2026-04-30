document.addEventListener('DOMContentLoaded', () => {
    initLessonVideoEmbed();
    initLessonCompletion();
});

function initLessonVideoEmbed() {
    document.addEventListener('click', (event) => {
        const placeholder = event.target.closest('.video-wrapper .placeholder');
        if (!placeholder) return;

        const wrapper = placeholder.closest('.video-wrapper');
        const src = wrapper?.dataset.embed;
        if (!wrapper || !src) return;

        const iframe = document.createElement('iframe');
        iframe.src = src;
        iframe.className = 'media-frame';
        iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
        iframe.setAttribute('allowfullscreen', '');
        iframe.setAttribute('title', 'Reprodutor de vídeo da aula');

        wrapper.innerHTML = '';
        wrapper.appendChild(iframe);
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
