document.addEventListener('DOMContentLoaded', () => {
    initQuizOptionBuilder();
    initQuizAjaxForms();
});

function initQuizOptionBuilder() {
    const addButton = document.getElementById('add-opcao');
    const list = document.getElementById('q-opcoes-list');
    if (!addButton || !list) return;

    addButton.addEventListener('click', () => {
        const index = list.querySelectorAll('.opcao-row').length;
        const row = document.createElement('div');
        row.className = 'opcao-row';
        row.innerHTML = `
            <input type="text" name="opcao_${index}" placeholder="Opção ${index + 1}">
            <button type="button" class="btn btn-sm remove-opcao">-</button>
        `;

        list.appendChild(row);
        row.querySelector('.remove-opcao')?.addEventListener('click', () => row.remove());
    });
}

function initQuizAjaxForms() {
    document.querySelectorAll('form[data-ajax="true"]').forEach((form) => {
        if (typeof submitFormAjax !== 'function') return;
        if (!form.id) {
            form.id = `ajaxform_${Math.random().toString(36).slice(2, 9)}`;
        }

        submitFormAjax(form.id, (data) => {
            if (!data?.sucesso) return;
            if (data.redirect) {
                window.location.href = data.redirect;
                return;
            }
            window.location.reload();
        });
    });
}
