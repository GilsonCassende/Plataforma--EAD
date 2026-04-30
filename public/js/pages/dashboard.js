document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('.professor-dashboard');
    if (!root) return;

    const data = {
        cursos: Number(root.dataset.cursos || 0),
        alunos: Number(root.dataset.alunos || 0),
        aulas: Number(root.dataset.aulas || 0),
        atividades: Number(root.dataset.atividades || 0)
    };

    if (typeof initDashboardCounters === 'function') {
        initDashboardCounters(data);
    }

    initProfessorDashboardChart(data);
    initProfessorQuickActionPickers(root);
});

function initProfessorDashboardChart(data) {
    const canvas = document.getElementById('cmpChart');
    if (!canvas) return;

    const renderChart = () => {
        try {
            if (!window.Chart) return;

            new window.Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: ['Cursos', 'Alunos', 'Aulas', 'Atividades'],
                    datasets: [{
                        label: 'Comparativo',
                        data: [data.cursos, data.alunos, data.aulas, data.atividades],
                        backgroundColor: ['#667eea', '#7c3aed', '#06b6d4', '#10b981'],
                        borderRadius: 8,
                        barPercentage: 0.6
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { color: '#374151' }
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f3f4f6' },
                            ticks: { color: '#374151' }
                        }
                    }
                }
            });
        } catch (error) {
            console.warn('Erro iniciando gráfico', error);
        }
    };

    if (window.Chart) {
        renderChart();
        return;
    }

    const script = document.createElement('script');
    script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
    script.onload = renderChart;
    document.head.appendChild(script);
}

function initProfessorQuickActionPickers(root) {
    let courses = [];
    try {
        courses = JSON.parse(root.dataset.courseOptions || '[]');
    } catch (error) {
        console.warn('Não foi possível carregar cursos do professor para os atalhos rápidos.', error);
    }

    const buildQuickActionUrl = (mode, courseId) => {
        const page = mode === 'quiz' ? 'criar-quiz' : 'criar-aula';
        return `?page=${page}&course_id=${encodeURIComponent(courseId)}`;
    };

    const continueToQuickAction = (mode, courseId) => {
        const targetUrl = buildQuickActionUrl(mode, courseId);

        if (typeof loadFragmentToModal === 'function' && typeof openModal === 'function') {
            loadFragmentToModal(`${targetUrl}&partial=1`, {
                title: mode === 'quiz' ? 'Criar Quiz' : 'Adicionar Aula'
            });
            return;
        }

        window.location.href = targetUrl;
    };

    root.querySelectorAll('[data-course-picker]').forEach((trigger) => {
        const mode = trigger.getAttribute('data-course-picker');

        trigger.addEventListener('click', function () {
            if (!Array.isArray(courses) || courses.length === 0) {
                showNotification('Crie um curso antes de continuar.', 'info');
                return;
            }

            if (courses.length === 1) {
                continueToQuickAction(mode, courses[0].id || '');
                return;
            }

            if (typeof openModal !== 'function') {
                continueToQuickAction(mode, courses[0].id || '');
                return;
            }

            const wrapper = document.createElement('div');
            wrapper.className = 'quick-action-picker';

            const intro = document.createElement('div');
            intro.className = 'quick-action-picker__intro';
            intro.innerHTML = `
                <p class="quick-action-picker__title">Escolha o curso</p>
                <p class="quick-action-picker__copy">${mode === 'quiz'
                    ? 'Selecione o curso para abrir o criador de quiz no contexto correto.'
                    : 'Selecione em qual curso você quer adicionar a próxima aula. O formulário será aberto já vinculado ao curso escolhido.'}</p>
            `;
            wrapper.appendChild(intro);

            const list = document.createElement('div');
            list.className = 'quick-action-picker__list';

            courses.forEach((course, index) => {
                const option = document.createElement('label');
                option.className = 'quick-action-picker__option';

                const input = document.createElement('input');
                input.type = 'radio';
                input.name = 'quick-action-course';
                input.value = String(course.id || '');
                if (index === 0) input.checked = true;

                const meta = document.createElement('span');
                meta.className = 'quick-action-picker__meta';
                meta.innerHTML = `
                    <strong>${escapeHtml(course.titulo || 'Curso sem título')}</strong>
                    <small>${course.total_aulas || 0} aula(s) · ${course.total_alunos || 0} aluno(s)</small>
                `;

                option.appendChild(input);
                option.appendChild(meta);
                list.appendChild(option);
            });

            wrapper.appendChild(list);

            const footer = `
                <button type="button" class="btn btn-primary" id="quick-action-course-confirm">Prosseguir</button>
                <button type="button" class="btn btn-secondary" data-modal-close>Cancelar</button>
            `;

            openModal({
                title: mode === 'quiz' ? 'Criar quiz para o curso' : 'Adicionar aula ao curso',
                body: wrapper,
                footer
            });

            const confirmButton = document.getElementById('quick-action-course-confirm');
            if (!confirmButton) return;

            confirmButton.addEventListener('click', function () {
                const selected = document.querySelector('input[name="quick-action-course"]:checked');
                if (!selected || !selected.value) {
                    showNotification('Selecione um curso para continuar.', 'error');
                    return;
                }

                continueToQuickAction(mode, selected.value);
            });
        });
    });
}

function escapeHtml(value) {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#39;');
}
