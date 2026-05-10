document.addEventListener('DOMContentLoaded', () => {
    initCourseManageDelete();
    initCourseManageReorder();
    initCourseManageSearch();
    initCourseManageLessonNavigation();
});

function initCourseManageDelete() {
    document.addEventListener('click', async (event) => {
        const button = event.target.closest('.btn-delete-lesson');
        if (!button) return;
        if (!window.confirm('Deletar esta aula? Esta ação é irreversível.')) return;

        try {
            const response = await csrfFetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: new URLSearchParams({
                    acao: 'deletar_aula',
                    lesson_id: button.getAttribute('data-lesson-id')
                })
            });

            const result = await response.json();
            if (result.sucesso) {
                window.location.reload();
            } else {
                showNotification(result.mensagem || 'Erro', 'error');
            }
        } catch (error) {
            showNotification('Erro na requisição', 'error');
        }
    });
}

function initCourseManageReorder() {
    const toggle = document.getElementById('btn-toggle-reorder');
    if (!toggle) return;
    const lessonCards = () => document.querySelectorAll('.lesson-card');

    let reorderMode = false;
    let dragSource = null;

    toggle.addEventListener('click', async () => {
        reorderMode = !reorderMode;
        toggle.textContent = reorderMode ? 'Salvar Ordem' : 'Reordenar Aulas';
        toggle.classList.toggle('ui-btn--primary', reorderMode);
        toggle.classList.toggle('btn-primary', reorderMode);
        toggle.classList.toggle('btn-outline', !reorderMode);

        lessonCards().forEach((card) => {
            card.setAttribute('draggable', reorderMode ? 'true' : 'false');
            card.style.cursor = reorderMode ? 'grab' : '';
        });

        if (reorderMode) return;

        const order = Array.from(lessonCards())
            .map((card) => card.getAttribute('data-lesson-id'));

        const formData = new FormData();
        formData.append('acao', 'reordenar_aulas');
        formData.append('course_id', new URLSearchParams(window.location.search).get('id') || '');
        formData.append('order', JSON.stringify(order));

        try {
            const response = await csrfFetch(window.location.pathname + window.location.search, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            });

            const result = await response.json();
            showNotification(result?.mensagem || (result?.sucesso ? 'Ordem salva' : 'Erro ao salvar ordem'), result?.sucesso ? 'success' : 'error');
        } catch (error) {
            showNotification('Erro ao salvar ordem', 'error');
        }
    });

    document.addEventListener('dragstart', (event) => {
        const card = event.target.closest('.lesson-card');
        if (!card || card.getAttribute('draggable') !== 'true') return;
        dragSource = card;
        card.classList.add('dragging');
        event.dataTransfer.effectAllowed = 'move';
    });

    document.addEventListener('dragend', (event) => {
        const card = event.target.closest('.lesson-card');
        if (card) card.classList.remove('dragging');
        dragSource = null;
    });

    document.addEventListener('dragover', (event) => {
        const over = event.target.closest('.lesson-card');
        if (!over || !dragSource || over === dragSource || over.getAttribute('draggable') !== 'true') return;

        event.preventDefault();
        const rect = over.getBoundingClientRect();
        const insertAfter = (event.clientY - rect.top) > (rect.height / 2);
        over.parentElement.insertBefore(dragSource, insertAfter ? over.nextSibling : over);
    });

    document.addEventListener('drop', (event) => {
        if (event.target.closest('.lesson-card')) {
            event.preventDefault();
        }
    });
}

function initCourseManageSearch() {
    const input = document.getElementById('lesson-search');
    if (!input) return;

    input.addEventListener('input', () => {
        const query = input.value.trim().toLowerCase();
        const lessonCards = document.querySelectorAll('.lesson-card');

        lessonCards.forEach((card) => {
            const title = card.querySelector('.lesson-title')?.textContent?.toLowerCase() || '';
            card.hidden = query !== '' && !title.includes(query);
        });
    });
}

function initCourseManageLessonNavigation() {
    const shouldIgnoreClick = (target) => {
        return !!target.closest('a, button, form, input, select, textarea, label');
    };

    document.addEventListener('click', (event) => {
        const card = event.target.closest('.lesson-card[data-lesson-url]');
        if (!card || shouldIgnoreClick(event.target)) return;
        const url = card.getAttribute('data-lesson-url');
        if (!url) return;
        window.location.href = url;
    });

    document.addEventListener('keydown', (event) => {
        const card = event.target.closest('.lesson-card[data-lesson-url]');
        if (!card) return;
        if (event.key !== 'Enter' && event.key !== ' ') return;
        if (shouldIgnoreClick(event.target)) return;

        event.preventDefault();
        const url = card.getAttribute('data-lesson-url');
        if (!url) return;
        window.location.href = url;
    });
}
