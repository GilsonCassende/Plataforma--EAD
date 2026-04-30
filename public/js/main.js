/**
 * JavaScript Principal - Plataforma EAD
 */

document.addEventListener('DOMContentLoaded', function () {
    initNavigation();
    initAlerts();
    initLoadingForms();
    initDataExportForms();
    initAuthExperience();
    initDropdowns();
    initMobileMenu();
    initConfirmations();
    initCourseProgressSync();
    initProfessorPanels();
    initClipboardActions();
    initQuizBuilders(document);
    initQuizPlayers(document);
    initQuizRuntime(document);
    initExpandableCopies(document);
    initCollapsibleModules(document);
});

/*
 * Monkey-patch global fetch to include CSRF token and credentials by default
 * This ensures any ad-hoc fetch calls (inline or in other scripts) send the X-CSRF-Token header.
 */
// Provide a safe helper for fetch that attaches CSRF token and credentials.
function csrfFetch(input, init = {}) {
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : null;
    init = init || {};
    init.credentials = init.credentials || 'same-origin';
    if (!init.headers) init.headers = {};
    if (csrfToken) {
        if (init.headers instanceof Headers) init.headers.set('X-CSRF-Token', csrfToken);
        else if (Array.isArray(init.headers)) init.headers.push(['X-CSRF-Token', csrfToken]);
        else init.headers['X-CSRF-Token'] = csrfToken;
    }
    return fetch(input, init);
}

// Simple DOM sanitizer for returned HTML fragments. Removes script/style nodes and
// strips event handler attributes (attributes starting with 'on') and javascript: URIs.
function sanitizeHTMLString(html) {
    try {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');

        // remove script/style/iframe/object nodes
        ['script', 'style', 'iframe', 'object'].forEach(tag => {
            doc.querySelectorAll(tag).forEach(n => n.remove());
        });

        // strip dangerous attributes
        doc.querySelectorAll('*').forEach(el => {
            // remove on* handlers
            [...el.attributes].forEach(attr => {
                const name = attr.name.toLowerCase();
                const val = attr.value || '';
                if (name.startsWith('on')) el.removeAttribute(attr.name);
                if (['href', 'src'].includes(name) && /^\s*javascript:/i.test(val)) el.removeAttribute(attr.name);
                if (name === 'style' && /expression\(|javascript:/i.test(val)) el.removeAttribute('style');
            });
        });

        // return sanitized innerHTML
        return doc.body ? doc.body.innerHTML : '';
    } catch (e) {
        console.error('sanitizeHTMLString error', e);
        return '';
    }
}

function extractModalFragmentHtml(rawHtml) {
    const parser = new DOMParser();
    const doc = parser.parseFromString(rawHtml, 'text/html');
    const selectors = [
        '[data-modal-fragment]',
        '.main-container [data-modal-fragment]',
        '.modal [data-modal-fragment]',
        '.main-container .editor-card',
        '.main-container .edit-course',
        '.main-container form',
        'body > [data-modal-fragment]',
        'body > .editor-card',
        'body > .card',
        'body > form'
    ];

    for (const selector of selectors) {
        const match = doc.querySelector(selector);
        if (match) return match.outerHTML;
    }

    const hasFullLayout = !!doc.querySelector('.navbar, .ui-navbar, .footer');
    if (hasFullLayout) {
        throw new Error('Fragmento modal inválido: o servidor retornou o layout completo.');
    }

    return doc.body ? doc.body.innerHTML : rawHtml;
}

/**
 * Inicializar navegação
 */
function initNavigation() {
    // Adicionar listener para links ativos
    const currentPage = new URLSearchParams(window.location.search).get('page') || 'home';
    const navItems = document.querySelectorAll('.nav-item');

    navItems.forEach(item => {
        const href = item.getAttribute('href');
        if (href && href.includes('page=' + currentPage)) {
            item.classList.add('active');
        }
    });
}

/**
 * Inicializar alertas com fade out automático
 */
function initAlerts() {
    const alerts = document.querySelectorAll('.alert, .toast[data-auto-dismiss]');

    alerts.forEach(alert => {
        setTimeout(() => {
            fadeOut(alert);
        }, 5000);
    });
}

function initLoadingForms(root = document) {
    const forms = root.querySelectorAll('[data-loading-form]');
    forms.forEach(form => {
        if (form.dataset.loadingBound === '1') return;
        form.dataset.loadingBound = '1';

        form.addEventListener('submit', () => {
            const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
            if (!submitButton) return;

            const loadingText = submitButton.getAttribute('data-loading-text');
            if (loadingText && !submitButton.dataset.originalText) {
                submitButton.dataset.originalText = submitButton.textContent;
            }

            submitButton.disabled = true;
            submitButton.setAttribute('aria-busy', 'true');

            if (loadingText && submitButton.tagName === 'BUTTON') {
                submitButton.textContent = loadingText;
            }
        });
    });
}

function initDataExportForms(root = document) {
    const forms = root.querySelectorAll('[data-export-form]');
    forms.forEach(form => {
        if (form.dataset.exportBound === '1') return;
        form.dataset.exportBound = '1';

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            setFormLoadingState(form, true);

            try {
                const response = await csrfFetch(form.action, {
                    method: (form.method || 'POST').toUpperCase(),
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new FormData(form)
                });

                const data = await response.json();
                if (!response.ok || !data.sucesso) {
                    showNotification(data.mensagem || 'Não foi possível gerar o backup agora.', 'error');
                    return;
                }

                showNotification(data.mensagem || 'Seu backup está pronto para download.', 'success');
                if (data.download_url) {
                    window.setTimeout(() => {
                        window.location.href = data.download_url;
                    }, 180);
                }
            } catch (error) {
                console.error('Export form error', error);
                showNotification('Não foi possível gerar o backup agora.', 'error');
            } finally {
                setFormLoadingState(form, false);
            }
        });
    });
}

function initAuthExperience(root = document) {
    initVerificationCodeInput(root);
    initAuthAjaxForms(root);
}

function initVerificationCodeInput(root = document) {
    const codeInput = root.querySelector('[data-verification-code-input]');
    const counter = root.querySelector('[data-verification-code-counter]');
    if (!codeInput || codeInput.dataset.codeBound === '1') return;

    codeInput.dataset.codeBound = '1';

    const syncValue = () => {
        const digits = codeInput.value.replace(/\D/g, '').slice(0, 6);
        codeInput.value = digits;
        if (counter) {
            counter.textContent = `${digits.length}/6`;
        }
    };

    codeInput.addEventListener('input', syncValue);
    codeInput.addEventListener('paste', () => {
        window.setTimeout(syncValue, 0);
    });
    syncValue();
}

function initAuthAjaxForms(root = document) {
    const selectors = [
        '#login-form',
        '#registro-form',
        '#registro-professor-form',
        '#forgot-password-form',
        '#reset-password-form',
        '#email-verification-code-form',
        '#email-verification-resend-form'
    ];

    selectors.forEach(selector => {
        const form = root.querySelector(selector);
        if (!form || form.dataset.authAjaxBound === '1') return;
        form.dataset.authAjaxBound = '1';

        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            clearInlineFormMessage(form);
            setFormLoadingState(form, true);

            try {
                const formData = new FormData(form);
                const response = await csrfFetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const data = await response.json();
                showInlineFormMessage(form, data.mensagem || 'Solicitação processada.', !!data.sucesso);

                if (data.redirect) {
                    window.setTimeout(() => {
                        window.location.href = data.redirect;
                    }, data.sucesso ? 550 : 900);
                }
            } catch (error) {
                console.error('Auth form error', error);
                showInlineFormMessage(form, 'Não foi possível concluir a solicitação agora. Tente novamente em instantes.', false);
            } finally {
                setFormLoadingState(form, false);
            }
        });
    });
}

function setFormLoadingState(form, isLoading) {
    const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
    if (!submitButton) return;

    if (!submitButton.dataset.originalText && submitButton.tagName === 'BUTTON') {
        submitButton.dataset.originalText = submitButton.textContent;
    }

    submitButton.disabled = isLoading;
    submitButton.setAttribute('aria-busy', String(isLoading));

    if (submitButton.tagName === 'BUTTON') {
        const loadingText = submitButton.getAttribute('data-loading-text');
        submitButton.textContent = isLoading && loadingText
            ? loadingText
            : (submitButton.dataset.originalText || submitButton.textContent);
    }
}

function showInlineFormMessage(form, message, isSuccess) {
    let feedback = form.querySelector('.form-feedback');
    if (!feedback) {
        feedback = document.createElement('div');
        feedback.className = 'form-feedback';
        form.appendChild(feedback);
    }

    feedback.className = `form-feedback ${isSuccess ? 'form-feedback--success' : 'form-feedback--error'}`;
    feedback.textContent = message;
}

function clearInlineFormMessage(form) {
    const feedback = form.querySelector('.form-feedback');
    if (feedback) {
        feedback.remove();
    }
}

function fadeOut(element) {
    element.style.animation = 'fadeOut 0.3s ease';
    setTimeout(() => {
        element.remove();
    }, 300);
}

/**
 * Menu mobile responsivo
 */
function initMobileMenu() {
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');

    if (!hamburger || !navMenu) return;

    hamburger.addEventListener('click', function () {
        const isOpen = navMenu.classList.toggle('active');
        this.classList.toggle('active', isOpen);
        this.setAttribute('aria-expanded', String(isOpen));
        this.setAttribute('aria-label', isOpen ? 'Fechar menu' : 'Abrir menu');
        document.body.classList.toggle('menu-open', isOpen);
    });

    // Fechar menu ao clicar em um item
    const navItems = navMenu.querySelectorAll('.nav-item');
    navItems.forEach(item => {
        item.addEventListener('click', () => {
            navMenu.classList.remove('active');
            hamburger.classList.remove('active');
            hamburger.setAttribute('aria-expanded', 'false');
            hamburger.setAttribute('aria-label', 'Abrir menu');
            document.body.classList.remove('menu-open');
        });
    });
}

/**
 * Inicializar dropdowns
 */
function initDropdowns() {
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');

    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function (e) {
            e.preventDefault();
            const menu = this.nextElementSibling;
            if (menu && menu.classList.contains('dropdown-menu')) {
                const isOpen = menu.classList.toggle('active');
                this.setAttribute('aria-expanded', String(isOpen));
            }
        });
    });

    // Fechar dropdown ao clicar fora
    document.addEventListener('click', function (e) {
        dropdownToggles.forEach(toggle => {
            if (!toggle.parentElement.contains(e.target)) {
                const menu = toggle.nextElementSibling;
                if (menu) menu.classList.remove('active');
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    });
}

function initConfirmations() {
    document.addEventListener('submit', function (event) {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) return;
        const message = form.getAttribute('data-confirm');
        if (message && !window.confirm(message)) {
            event.preventDefault();
        }
    });

    document.addEventListener('click', function (event) {
        const link = event.target.closest('[data-confirm-link]');
        if (!link) return;
        const message = link.getAttribute('data-confirm-link');
        if (message && !window.confirm(message)) {
            event.preventDefault();
        }
    });
}

function initClipboardActions() {
    document.addEventListener('click', function (event) {
        const trigger = event.target.closest('[data-copy-text]');
        if (!trigger) return;

        event.preventDefault();
        const text = trigger.getAttribute('data-copy-text') || '';
        if (!text) return;

        copyToClipboard(text);
    });
}

function initCourseProgressSync() {
    window.addEventListener('aulaCompletada', function (event) {
        const rawProgress = event.detail?.progress;
        const courseId = event.detail?.course_id;
        const progress = Number.parseInt(rawProgress, 10);

        if (Number.isNaN(progress)) return;

        if (courseId !== undefined && courseId !== null) {
            const fill = document.querySelector(`.progresso-fill[data-course-id="${courseId}"]`);
            const text = document.querySelector(`.progresso-text[data-course-id="${courseId}"]`);
            const progressBar = fill ? fill.closest('.progresso-bar') : null;

            if (fill) fill.style.setProperty('--progress', `${progress}%`);
            if (text) text.textContent = `${progress}% completo`;
            if (progressBar) progressBar.setAttribute('aria-valuenow', String(progress));

            if (fill) {
                const card = fill.closest('.course-card');
                const status = card ? card.querySelector('.status-course') : null;
                if (status) {
                    status.innerHTML = progress === 100
                        ? '<span class="badge badge-success">✓ Concluído</span>'
                        : '<span class="badge badge-warning">Em andamento</span>';
                }
            }
        } else {
            const progressBars = document.querySelectorAll('.progresso-fill');
            const progressTexts = document.querySelectorAll('.progresso-text');
            const firstBar = progressBars.length > 0 ? progressBars[0].closest('.progresso-bar') : null;

            if (progressBars.length > 0) progressBars[0].style.setProperty('--progress', `${progress}%`);
            if (progressTexts.length > 0) progressTexts[0].textContent = `${progress}% completo`;
            if (firstBar) firstBar.setAttribute('aria-valuenow', String(progress));

            const statusElements = document.querySelectorAll('.status-course');
            if (statusElements.length > 0) {
                statusElements[0].innerHTML = progress === 100
                    ? '<span class="badge badge-success">✓ Concluído</span>'
                    : '<span class="badge badge-warning">Em andamento</span>';
            }
        }

        try {
            fetch(`${window.location.origin}${window.location.pathname}?api=dashboard_counts`)
                .then((response) => response.json())
                .then((data) => {
                    if (!data) return;

                    const totalCursos = document.getElementById('stat-total-cursos');
                    const emProgresso = document.getElementById('stat-em-progresso');
                    const concluidos = document.getElementById('stat-concluidos');

                    if (totalCursos && data.total_cursos !== undefined) totalCursos.textContent = data.total_cursos;
                    if (emProgresso && data.em_progresso !== undefined) emProgresso.textContent = data.em_progresso;
                    if (concluidos && data.concluidos !== undefined) concluidos.textContent = data.concluidos;
                })
                .catch(() => {});
        } catch (error) {
            // silent
        }
    });
}

function initProfessorPanels() {
    document.addEventListener('input', function (event) {
        const searchInput = event.target.closest('#student-search');
        if (searchInput) {
            const query = searchInput.value.toLowerCase();
            document.querySelectorAll('.student-card').forEach((card) => {
                const name = card.querySelector('.student-name')?.textContent.toLowerCase() || '';
                const email = card.querySelector('.student-email')?.textContent.toLowerCase() || '';
                card.classList.toggle('is-hidden', !(name.includes(query) || email.includes(query)));
            });
            return;
        }

        const lessonSearch = event.target.closest('#lesson-search');
        if (lessonSearch) {
            const query = lessonSearch.value.toLowerCase();
            document.querySelectorAll('.lesson-card').forEach((card) => {
                const title = card.querySelector('h4')?.textContent.toLowerCase() || '';
                card.classList.toggle('is-hidden', !title.includes(query));
            });
        }
    });

    document.addEventListener('click', async function (event) {
        const editProgressButton = event.target.closest('.btn-edit-progress');
        if (editProgressButton) {
            const userId = editProgressButton.getAttribute('data-user-id');
            const courseId = editProgressButton.getAttribute('data-course-id');
            const current = parseInt(editProgressButton.closest('.student-card')?.querySelector('.student-progress-text')?.textContent || '0', 10);
            const nextValue = window.prompt('Defina o novo progresso (0-100)', String(current));
            if (nextValue === null) return;

            const progress = Math.max(0, Math.min(100, parseInt(nextValue, 10) || 0));
            try {
                const response = await csrfFetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({
                        acao: 'atualizar_progresso',
                        course_id: courseId,
                        user_id: userId,
                        progress: progress
                    })
                });

                const result = await response.json();
                if (result.sucesso) window.location.reload();
                else showNotification(result.mensagem || 'Erro', 'error');
            } catch (error) {
                showNotification('Erro ao atualizar progresso', 'error');
            }
            return;
        }

        const removeEnrollmentButton = event.target.closest('.btn-remove-enrollment');
        if (removeEnrollmentButton) {
            if (!window.confirm('Remover matrícula deste aluno?')) return;

            try {
                const response = await csrfFetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new URLSearchParams({
                        acao: 'remover_matricula',
                        user_id: removeEnrollmentButton.getAttribute('data-user-id'),
                        course_id: removeEnrollmentButton.getAttribute('data-course-id')
                    })
                });

                const result = await response.json();
                if (result.sucesso) window.location.reload();
                else showNotification(result.mensagem || 'Erro', 'error');
            } catch (error) {
                showNotification('Erro ao remover matrícula', 'error');
            }
        }
    });
}

/**
 * Mostrar notificação (toast)
 */
function showNotification(message, type = 'success') {
    // Toast container
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    container.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add('is-visible'));

    const timeout = 4500;
    const remover = () => {
        toast.classList.remove('is-visible');
        setTimeout(() => { toast.remove(); if (!container.childElementCount) container.remove(); }, 260);
    };

    const t = setTimeout(remover, timeout);
    // dismiss on click
    toast.addEventListener('click', () => { clearTimeout(t); remover(); });
}

/**
 * Loader global simples (usado durante redirects após submissões AJAX)
 */
function showGlobalLoader() {
    if (document.getElementById('global-loader')) return;
    const div = document.createElement('div');
    div.id = 'global-loader';
    div.className = 'global-loader';
    div.setAttribute('aria-hidden', 'true');

    const spinner = document.createElement('div');
    spinner.className = 'global-spinner';
    div.appendChild(spinner);
    document.body.appendChild(div);
}

function hideGlobalLoader() {
    const el = document.getElementById('global-loader');
    if (el) el.remove();
}

/**
 * Validação de formulário
 */
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;

    const inputs = form.querySelectorAll('input[required], textarea[required]');
    let isValid = true;

    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }
    });

    return isValid;
}

/**
 * Limpar erros de formulário
 */
function clearFormErrors(formId) {
    const form = document.getElementById(formId);
    if (!form) return;

    const inputs = form.querySelectorAll('input, textarea');
    inputs.forEach(input => {
        input.classList.remove('is-invalid');
    });
}

/**
 * Animar barra de progresso
 */
function animateProgressBar(elementId, targetPercent, duration = 1000) {
    const element = document.getElementById(elementId);
    if (!element) return;

    const startTime = Date.now();
    const startPercent = parseFloat(element.style.width) || 0;

    const animate = () => {
        const now = Date.now();
        const elapsed = now - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const current = startPercent + (targetPercent - startPercent) * progress;

        element.style.width = current + '%';

        if (progress < 1) {
            requestAnimationFrame(animate);
        }
    };

    requestAnimationFrame(animate);
}

/**
 * Enviar formulário via AJAX (inclui token CSRF automaticamente)
 */
function submitFormAjax(formId, successCallback) {
    const form = document.getElementById(formId);
    if (!form) return;

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const submitButton = form.querySelector('button[type="submit"], input[type="submit"]');
        const restoreSubmitButton = () => {
            if (!submitButton) return;
            submitButton.disabled = false;
            submitButton.removeAttribute('aria-busy');
            if (submitButton.tagName === 'BUTTON' && submitButton.dataset.originalText) {
                submitButton.textContent = submitButton.dataset.originalText;
            }
        };

        const formData = new FormData(form);

        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : null;

        // Use Headers instance to make sure headers are properly sent
        const headers = new Headers();
        headers.append('Accept', 'application/json');
        headers.append('X-Requested-With', 'XMLHttpRequest');
        if (csrfToken) headers.append('X-CSRF-Token', csrfToken);

        // Use the literal action attribute to avoid collisions when the form contains an
        // input named "action" which can shadow the DOM property and become an element
        // (resulting in requests to '/[object HTMLInputElement]').
        const targetUrl = form.getAttribute('action') || window.location.href;
        csrfFetch(targetUrl, {
            method: 'POST',
            headers: headers,
            body: formData,
            credentials: 'same-origin'
        })
            .then(response => {
                const ct = response.headers.get('content-type') || '';
                // If server returned JSON, parse it
                if (ct.includes('application/json')) return response.json().then(d => ({ __json: d }));
                // If server returned HTML, return as HTML marker
                if (ct.includes('text/html') || ct.includes('application/xhtml+xml')) return response.text().then(html => ({ __html: html }));
                // Fallback: try to parse text as JSON, otherwise return text
                return response.text().then(text => {
                    try {
                        return { __json: JSON.parse(text) };
                    } catch (err) {
                        return { __text: text };
                    }
                });
            })
            .then(payload => {
                // Normalize payload: may contain __json, __html or __text
                let data = null;
                if (payload && payload.__json) data = payload.__json;
                else if (payload && payload.__text) {
                    // try parse again
                    try { data = JSON.parse(payload.__text); } catch (e) { data = null; }
                }

                // If the server returned HTML (search results, fragments), inject into modal
                if (payload && payload.__html) {
                    const overlay = document.getElementById('app-modal');
                    if (overlay) {
                        const bodyEl = overlay.querySelector('#modal-body');
                        if (bodyEl) {
                            const fragmentHtml = extractModalFragmentHtml(payload.__html || '');
                            const cleaned = sanitizeHTMLString(fragmentHtml);
                            bodyEl.innerHTML = cleaned;
                            // bind forms present in fragment
                            const form = bodyEl.querySelector('form');
                            if (form) {
                                const method = (form.getAttribute('method') || 'get').toLowerCase();
                                if (method !== 'get') {
                                    if (form.id) submitFormAjax(form.id); else attachAndBindForm(form);
                                } else {
                                    if (!form.id) form.id = 'form-' + Math.random().toString(36).substr(2, 8);
                                }
                            }
                            return;
                        }
                    }
                }

                if (data && data.sucesso) {
                    // Fechar modal e mostrar loader enquanto o redirect acontece
                    try { closeModal(); } catch (e) { }
                    showNotification(data.mensagem || 'Sucesso!', 'success');
                    if (successCallback) successCallback(data);

                    // seguir redirect retornado pelo servidor, se houver
                    if (data.redirect) {
                        try {
                            const redirectUrl = new URL(data.redirect, window.location.href);
                            if (redirectUrl.origin === window.location.origin) {
                                showGlobalLoader();
                                setTimeout(() => { window.location.href = redirectUrl.href; }, 450);
                                return;
                            } else {
                                console.warn('Ignorando redirect para origem diferente:', redirectUrl.href);
                            }
                        } catch (err) {
                            console.warn('Redirect inválido recebido:', data.redirect);
                        }
                    }

                    // reload se solicitado
                    if (data.reload) {
                        showGlobalLoader();
                        setTimeout(() => { window.location.reload(); }, 450);
                        return;
                    }
                    // se sucesso sem redirect, fechar loader e manter modal fechado
                    setTimeout(hideGlobalLoader, 800);
                } else {
                    // If we have data but sucesso is false, show error
                    if (data && data.mensagem) showNotification(data.mensagem || 'Erro ao processar', 'error');
                    else showNotification('Erro ao processar', 'error');
                    restoreSubmitButton();
                    if (data && data.redirect) {
                        setTimeout(() => { window.location.href = data.redirect; }, 700);
                    }
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showNotification('Erro ao processar requisição', 'error');
                restoreSubmitButton();
            });
    });
}

/**
 * Confirmar ação com modal
 */
function confirmAction(message) {
    return confirm(message);
}

/**
 * Debounce para busca
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Busca em tempo real
 */
function initSearchDebounce() {
    const searchInput = document.querySelector('.search-input');
    if (!searchInput) return;

    const handleSearch = debounce(function () {
        // Implementar busca
        console.log('Buscando:', searchInput.value);
    }, 300);

    searchInput.addEventListener('input', handleSearch);
}

/**
 * Copiar para clipboard
 */
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showNotification('Copiado para clipboard!', 'success');
    }).catch(err => {
        console.error('Erro ao copiar:', err);
    });
}

/**
 * Formatar data
 */
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('pt-BR', options);
}

/**
 * Animar números (contadores)
 */
function animateNumber(elementId, targetNumber, duration = 2000) {
    const element = document.getElementById(elementId);
    if (!element) return;

    const startTime = Date.now();
    const startNumber = parseInt(element.textContent) || 0;

    const animate = () => {
        const now = Date.now();
        const elapsed = now - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const current = Math.floor(startNumber + (targetNumber - startNumber) * progress);

        element.textContent = current;

        if (progress < 1) {
            requestAnimationFrame(animate);
        }
    };

    requestAnimationFrame(animate);
}

// CSS para animação de fade out
if (!document.querySelector('style[data-fade-out]')) {
    const style = document.createElement('style');
    style.setAttribute('data-fade-out', 'true');
    style.innerHTML = `
        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: translateY(0);
            }
            to {
                opacity: 0;
                transform: translateY(-10px);
            }
        }
    `;
    document.head.appendChild(style);
}

/* ==========================
   Modal helpers
   ========================== */

function openModal({ title = '', body = '', footer = '' } = {}) {
    const overlay = document.getElementById('app-modal');
    if (!overlay) return;

    const titleEl = overlay.querySelector('#modal-title');
    const bodyEl = overlay.querySelector('#modal-body');
    const footerEl = overlay.querySelector('#modal-footer');

    // Sanitize title and content before inserting
    titleEl.textContent = title || '';
    if (typeof body === 'string') {
        bodyEl.innerHTML = sanitizeHTMLString(body);
    } else if (body instanceof Node) {
        bodyEl.innerHTML = '';
        bodyEl.appendChild(body);
    }
    footerEl.innerHTML = sanitizeHTMLString(footer || '');

    // ensure overlay is appended to body to avoid stacking/context issues
    if (!document.body.contains(overlay)) document.body.appendChild(overlay);
    overlay.classList.remove('hidden');
    overlay.setAttribute('aria-hidden', 'false');
    document.body.classList.add('modal-open');
}

function closeModal() {
    const overlay = document.getElementById('app-modal');
    if (!overlay) return;
    overlay.classList.add('hidden');
    overlay.setAttribute('aria-hidden', 'true');
    const titleEl = overlay.querySelector('#modal-title');
    const bodyEl = overlay.querySelector('#modal-body');
    const footerEl = overlay.querySelector('#modal-footer');
    titleEl.innerHTML = '';
    bodyEl.innerHTML = '';
    footerEl.innerHTML = '';
    document.body.classList.remove('modal-open');
}

// Restore body scroll when modal is closed via other events
document.addEventListener('click', function (e) {
    const overlay = document.getElementById('app-modal');
    if (!overlay) return;
    if (overlay.classList.contains('hidden')) {
        document.body.classList.remove('modal-open');
    }
});

// Delegação para fechar modal
document.addEventListener('click', function (e) {
    if (e.target.matches('[data-modal-close]') || e.target.classList.contains('modal-overlay')) {
        closeModal();
    }
});

// Fechar com ESC
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeModal();
});

// Fechar ao clicar no overlay (fora da modal)
document.addEventListener('click', function (e) {
    const overlay = document.getElementById('app-modal');
    if (!overlay) return;
    if (e.target === overlay) closeModal();
});

/* ==========================
   Fragment loader (fetch partials into modal)
   ========================== */

function initFragmentLoaders() {
    document.querySelectorAll('[data-fragment]').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const url = this.getAttribute('data-fragment');
            const title = this.getAttribute('data-fragment-title') || '';
            loadFragmentToModal(url, { title });
        });
    });

    // Delegação: dentro do modal, interceptar cliques em links que retornam partials
    document.addEventListener('click', function (e) {
        const anchor = e.target.closest('a');
        if (!anchor) return;
        const href = anchor.getAttribute('href') || '';
        if (href.indexOf('partial=1') !== -1) {
            // apenas interceptar quando modal estiver aberto
            const modal = document.getElementById('app-modal');
            if (modal && !modal.classList.contains('hidden')) {
                e.preventDefault();
                loadFragmentToModal(href, { title: anchor.getAttribute('data-fragment-title') || '' });
            }
        }
    });

    // Interceptar submissões GET de formulários dentro do modal para recarregar fragment
    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!form) return;
        const method = (form.getAttribute('method') || 'get').toLowerCase();
        if (method !== 'get') return;
        const action = form.getAttribute('action') || window.location.href;
        if (action.indexOf('partial=1') !== -1) {
            e.preventDefault();
            // construir query string a partir do form
            const params = new URLSearchParams(new FormData(form));

            // Normalizar action: se começar com '?', prefixar com pathname para preservar index.php
            let baseAction = action;
            if (action.startsWith('?')) {
                baseAction = window.location.pathname + action; // e.g. /Plataforma-EAD/public/index.php?page=meus-cursos&partial=1
            }

            const separator = baseAction.includes('?') ? '&' : '?';
            const url = baseAction + (params.toString() ? (separator + params.toString()) : '');

            // debug
            try { console.debug('Modal GET submit — loading fragment URL:', url); } catch (e) { }

            loadFragmentToModal(url, { title: form.getAttribute('data-fragment-title') || '' });
        }
    });
}

function loadFragmentToModal(url, opts = {}) {
    // Abrir modal com estado de carregamento imediatamente
    openModal({ title: opts.title || '', body: '<div class="modal-loading"><div class="modal-spinner"></div><div class="modal-loading-text">Carregando...</div></div>', footer: '' });

    // Garantir estilos do spinner apenas uma vez
    if (!document.getElementById('modal-loading-style')) {
        const s = document.createElement('style');
        s.id = 'modal-loading-style';
        s.innerHTML = `
            .modal-loading{display:flex;align-items:center;gap:12px;padding:18px;justify-content:center}
            .modal-spinner{width:36px;height:36px;border-radius:50%;border:4px solid rgba(0,0,0,0.08);border-top-color:#667eea;animation:modal-spin 900ms linear infinite}
            .modal-loading-text{color:#374151;font-weight:600}
            @keyframes modal-spin{to{transform:rotate(360deg)}}
        `;
        document.head.appendChild(s);
    }

    csrfFetch(url, { method: 'GET' })
        .then(res => res.text())
        .then(rawHtml => {
            // Parse returned HTML and extract the meaningful fragment (form or .card)
            try {
                const cleaned = extractModalFragmentHtml(rawHtml);
                const sanitized = sanitizeHTMLString(cleaned);
                const overlay = document.getElementById('app-modal');
                if (overlay) {
                    const bodyEl = overlay.querySelector('#modal-body');
                    if (bodyEl) {
                        bodyEl.innerHTML = sanitized;
                        initQuizBuilders(bodyEl);
                        initQuizPlayers(bodyEl);
                        initExpandableCopies(bodyEl);
                    }
                } else {
                    openModal({ title: opts.title || '', body: sanitized, footer: '' });
                }

                // if the fragment contains a form, bind submitFormAjax (non-GET forms only)
                const modal = document.getElementById('app-modal');
                if (!modal) return;
                const form = modal.querySelector('form');
                if (form) {
                    const method = (form.getAttribute('method') || 'get').toLowerCase();
                    if (method !== 'get') {
                        if (form.id) submitFormAjax(form.id);
                        else attachAndBindForm(form);
                    } else {
                        if (!form.id) form.id = 'form-' + Math.random().toString(36).substr(2, 8);
                    }
                }
            } catch (err) {
                console.error('Erro parsing fragmento:', err);
                closeModal();
                showNotification('Nao foi possivel carregar este formulario no modal.', 'error');
            }
        })
        .catch(err => {
            console.error('Erro ao carregar fragmento:', err);
            showNotification('Erro ao carregar o formulário', 'error');
        });
}

// Helper to attach an id to a form if missing and return it
function attachAndBindForm(form) {
    if (!form.id) form.id = 'form-' + Math.random().toString(36).substr(2, 8);
    // Bind submitFormAjax only for non-GET methods to avoid POSTing when the
    // form is intended to be a fragment GET (search/pagination inside modal).
    const method = (form.getAttribute('method') || 'get').toLowerCase();
    if (method !== 'get') submitFormAjax(form.id);
    return form.id;
}

function initExpandableCopies(root = document) {
    root.querySelectorAll('[data-expandable-copy]').forEach((wrapper) => {
        if (wrapper.dataset.expandableReady === '1') return;
        wrapper.dataset.expandableReady = '1';

        const trigger = wrapper.querySelector('[data-expandable-trigger]');
        if (!trigger) return;

        const setState = (expanded) => {
            wrapper.classList.toggle('is-collapsed', !expanded);
            trigger.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            trigger.textContent = expanded ? 'Minimizar descrição' : 'Expandir descrição';
        };

        setState(false);
        trigger.addEventListener('click', () => {
            const expanded = trigger.getAttribute('aria-expanded') === 'true';
            setState(!expanded);
        });
    });
}

function initCollapsibleModules(root = document) {
    root.querySelectorAll('[data-module-collapsible]').forEach((module) => {
        if (module.dataset.moduleReady === '1') return;
        module.dataset.moduleReady = '1';

        const trigger = module.querySelector('[data-module-toggle]');
        const panel = module.querySelector('[data-module-panel]');
        const label = trigger ? trigger.querySelector('.module-block__toggle-label') : null;

        if (!trigger || !panel || !label) return;

        const setState = (expanded) => {
            module.classList.toggle('is-collapsed', !expanded);
            trigger.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            label.textContent = expanded ? 'Fechar' : 'Expandir';
            panel.style.maxHeight = expanded ? `${panel.scrollHeight}px` : '0px';
        };

        setState(false);

        trigger.addEventListener('click', () => {
            const expanded = trigger.getAttribute('aria-expanded') === 'true';
            setState(!expanded);
        });

        window.addEventListener('resize', () => {
            if (trigger.getAttribute('aria-expanded') === 'true') {
                panel.style.maxHeight = `${panel.scrollHeight}px`;
            }
        });
    });
}

function initQuizBuilders(root = document) {
    const builders = root.querySelectorAll('[data-quiz-builder]');
    builders.forEach((builder) => {
        if (builder.dataset.quizBuilderReady === '1') return;
        builder.dataset.quizBuilderReady = '1';
        setupQuizBuilder(builder);
    });
}

function setupQuizBuilder(builder) {
    const typeSelect = builder.querySelector('[data-quiz-type]');
    const difficultySelect = builder.querySelector('[data-difficulty-select]');
    const lessonField = builder.querySelector('[data-lesson-field]');
    const lessonSelect = builder.querySelector('[data-lesson-select]');
    const lessonHiddenInput = builder.querySelector('[data-lesson-hidden-input]');
    const moduleField = builder.querySelector('[data-module-field]');
    const moduleSelect = builder.querySelector('[data-module-select]');
    const questionList = builder.querySelector('[data-question-list]');
    const feedback = builder.querySelector('[data-quiz-builder-feedback]');
    const draftStatus = builder.querySelector('[data-draft-status]');
    const draftKey = builder.querySelector('[data-draft-key]')?.value || '';
    const clearDraftButton = builder.querySelector('[data-clear-draft]');
    const questionTemplate = document.getElementById('quiz-question-template');
    const optionTemplate = document.getElementById('quiz-option-template');
    const addQuestionButton = builder.querySelector('[data-add-question]');
    const totalPointsValue = builder.querySelector('[data-total-points]');
    const totalPointsCopy = builder.querySelector('[data-total-points-copy]');
    const builderProgress = builder.querySelector('[data-builder-progress]');
    const builderPrev = builder.querySelector('[data-builder-prev]');
    const builderNext = builder.querySelector('[data-builder-next]');
    const builderPositionLabels = Array.from(builder.querySelectorAll('[data-builder-position-label]'));
    let dragSource = null;
    let draftTimer = null;
    let activeQuestionIndex = 0;

    if (!questionList || !questionTemplate || !optionTemplate) return;

    const syncRelationFields = () => {
        if (!typeSelect) return;
        const isLessonQuiz = typeSelect.value === 'aula';
        const isModuleQuiz = typeSelect.value === 'modulo';

        if (lessonField) {
            lessonField.hidden = !isLessonQuiz;
        }
        if (lessonSelect) {
            lessonSelect.required = isLessonQuiz;
            if (!isLessonQuiz && lessonHiddenInput) {
                lessonSelect.value = '';
            }
        }
        if (lessonHiddenInput) {
            lessonHiddenInput.disabled = !!lessonSelect;
        }

        if (!moduleField) return;
        moduleField.hidden = !isModuleQuiz;
        if (moduleSelect) {
            moduleSelect.required = isModuleQuiz;
            if (!isModuleQuiz) moduleSelect.value = '';
        }
    };

    const updateOptionLabels = (card) => {
        const optionItems = card.querySelectorAll('[data-option-item]');
        const correctSelect = card.querySelector('[data-correct-select]');
        const previousValue = correctSelect ? correctSelect.value : '';

        optionItems.forEach((item, index) => {
            const label = item.querySelector('[data-option-label]');
            const input = item.querySelector('[data-option-input]');
            const inputName = `questions[0][alternativas][${index}]`;
            if (label) label.textContent = String.fromCharCode(65 + index);
            if (input) input.name = inputName;
        });

        if (correctSelect) {
            correctSelect.innerHTML = '';
            optionItems.forEach((item, index) => {
                const input = item.querySelector('[data-option-input]');
                const option = document.createElement('option');
                option.value = String(index);
                option.textContent = input && input.value.trim() !== ''
                    ? `${String.fromCharCode(65 + index)} - ${input.value.trim()}`
                    : `Alternativa ${String.fromCharCode(65 + index)}`;
                correctSelect.appendChild(option);
            });

            if (previousValue !== '' && Number(previousValue) < optionItems.length) {
                correctSelect.value = previousValue;
            } else {
                correctSelect.value = optionItems.length > 0 ? '0' : '';
            }
        }
    };

    const updateQuestionIndices = () => {
        const cards = questionList.querySelectorAll('[data-question-card]');
        cards.forEach((card, index) => {
            const number = card.querySelector('[data-question-number]');
            if (number) number.textContent = `Pergunta ${index + 1}`;

            const textField = card.querySelector('[data-field="texto"]');
            const pointsField = card.querySelector('[data-field="pontos"]');
            const correctField = card.querySelector('[data-field="correta"]');

            if (textField) textField.name = `questions[${index}][texto]`;
            if (pointsField) pointsField.name = `questions[${index}][pontos]`;
            if (correctField) correctField.name = `questions[${index}][correta]`;

            const options = card.querySelectorAll('[data-option-input]');
            options.forEach((input, optionIndex) => {
                input.name = `questions[${index}][alternativas][${optionIndex}]`;
            });
        });
    };

    const renderBuilderProgress = () => {
        if (!builderProgress) return;
        const cards = getQuestionCards();
        builderProgress.innerHTML = '';

        cards.forEach((card, index) => {
            const step = document.createElement('button');
            step.type = 'button';
            step.className = 'quiz-builder__question-progress-step';
            if (index === activeQuestionIndex) {
                step.classList.add('is-active');
            }

            const hasText = (card.querySelector('[data-field="texto"]')?.value || '').trim() !== '';
            const hasOptions = Array.from(card.querySelectorAll('[data-option-input]')).some((input) => input.value.trim() !== '');
            if (hasText || hasOptions) {
                step.classList.add('is-filled');
            }

            step.setAttribute('aria-label', `Ir para pergunta ${index + 1}`);
            step.addEventListener('click', () => updateQuestionStage(index));
            builderProgress.appendChild(step);
        });
    };

    const updateQuestionStage = (nextIndex) => {
        const cards = getQuestionCards();
        if (!cards.length) {
            activeQuestionIndex = 0;
            builderPositionLabels.forEach((label) => {
                label.textContent = '0 / 0';
            });
            if (builderPrev) builderPrev.disabled = true;
            if (builderNext) builderNext.disabled = true;
            renderBuilderProgress();
            return;
        }

        activeQuestionIndex = Math.max(0, Math.min(nextIndex, cards.length - 1));

        cards.forEach((card, index) => {
            const active = index === activeQuestionIndex;
            card.classList.toggle('is-active', active);
            card.hidden = !active;
        });

        builderPositionLabels.forEach((label) => {
            label.textContent = `${activeQuestionIndex + 1} / ${cards.length}`;
        });

        if (builderPrev) {
            builderPrev.disabled = activeQuestionIndex === 0;
        }

        if (builderNext) {
            builderNext.disabled = cards.length <= 1;
            builderNext.textContent = activeQuestionIndex === cards.length - 1 ? 'Última pergunta' : 'Next';
        }

        renderBuilderProgress();
    };

    const addOption = (card, value = '') => {
        const optionNode = optionTemplate.content.firstElementChild.cloneNode(true);
        const input = optionNode.querySelector('[data-option-input]');
        if (input) input.value = value;
        card.querySelector('[data-options-list]')?.appendChild(optionNode);
        updateOptionLabels(card);
        updateQuestionIndices();
        updateTotalPointsSummary();
    };

    const addQuestion = (initial = {}) => {
        const card = questionTemplate.content.firstElementChild.cloneNode(true);
        const textField = card.querySelector('[data-field="texto"]');
        const pointsField = card.querySelector('[data-field="pontos"]');

        if (textField) textField.value = initial.texto || '';
        if (pointsField) pointsField.value = initial.pontos || '1';

        questionList.appendChild(card);
        addOption(card, initial.alternativas?.[0] || '');
        addOption(card, initial.alternativas?.[1] || '');
        if (Array.isArray(initial.alternativas) && initial.alternativas.length > 2) {
            initial.alternativas.slice(2).forEach((option) => addOption(card, option));
        }
        updateOptionLabels(card);
        updateQuestionIndices();
        updateTotalPointsSummary();
        updateQuestionStage(getQuestionCards().length - 1);
    };

    const showFeedback = (message) => {
        if (!feedback) return;
        feedback.textContent = message;
        feedback.classList.add('is-visible', 'is-error');
    };

    const clearFeedback = () => {
        if (!feedback) return;
        feedback.textContent = '';
        feedback.classList.remove('is-visible', 'is-error');
    };

    const setDraftStatus = (message) => {
        if (!draftStatus) return;
        draftStatus.textContent = message;
    };

    const serializeBuilder = () => {
        const cards = Array.from(questionList.querySelectorAll('[data-question-card]')).map((card) => ({
            texto: card.querySelector('[data-field="texto"]')?.value || '',
            pontos: card.querySelector('[data-field="pontos"]')?.value || '1',
            correta: card.querySelector('[data-field="correta"]')?.value || '0',
            alternativas: Array.from(card.querySelectorAll('[data-option-input]')).map((input) => input.value || '')
        }));

        return {
            titulo: builder.querySelector('input[name="titulo"]')?.value || '',
            descricao: builder.querySelector('textarea[name="descricao"]')?.value || '',
            tipo: typeSelect?.value || 'final',
            dificuldade: difficultySelect?.value || 'normal',
            lesson_id: lessonSelect?.value || lessonHiddenInput?.value || '',
            module_id: moduleSelect?.value || '',
            tempo_limite: builder.querySelector('input[name="tempo_limite"]')?.value || '0',
            tentativas_maximas: builder.querySelector('input[name="tentativas_maximas"]')?.value || '3',
            obrigatorio: !!builder.querySelector('input[name="obrigatorio"]')?.checked,
            embaralhar_perguntas: !!builder.querySelector('input[name="embaralhar_perguntas"]')?.checked,
            embaralhar_respostas: !!builder.querySelector('input[name="embaralhar_respostas"]')?.checked,
            mostrar_respostas: !!builder.querySelector('input[name="mostrar_respostas"]')?.checked,
            mostrar_nota: !!builder.querySelector('input[name="mostrar_nota"]')?.checked,
            questions: cards
        };
    };

    const persistDraft = () => {
        if (!draftKey || typeof window.localStorage === 'undefined') return;
        window.localStorage.setItem(draftKey, JSON.stringify(serializeBuilder()));
        setDraftStatus('Rascunho salvo automaticamente.');
    };

    const formatValue = (value) => `${Number(value || 0).toFixed(1).replace(/\.0$/, '')} / 20`;

    const getQuestionCards = () => Array.from(questionList.querySelectorAll('[data-question-card]'));

    const getCurrentPointsTotal = () => getQuestionCards().reduce((sum, card) => {
        const points = Number(card.querySelector('[data-field="pontos"]')?.value || 0);
        return sum + Math.max(0, points);
    }, 0);

    const updateTotalPointsSummary = () => {
        if (!totalPointsValue) return;
        const currentTotal = getCurrentPointsTotal();
        totalPointsValue.textContent = `${currentTotal} / 20`;
        if (!totalPointsCopy) return;

        if (currentTotal === 20) {
            totalPointsCopy.textContent = 'Perfeito. A soma das perguntas fechou exatamente 20 valores.';
            return;
        }

        if (currentTotal < 20) {
            totalPointsCopy.textContent = `Ainda faltam ${20 - currentTotal} valores para completar o quiz.`;
            return;
        }

        totalPointsCopy.textContent = `O quiz passou do limite em ${currentTotal - 20} valores. Ajuste as perguntas antes de salvar.`;
    };

    const queueDraftSave = () => {
        if (!draftKey) return;
        window.clearTimeout(draftTimer);
        draftTimer = window.setTimeout(persistDraft, 250);
    };

    const populateBuilder = (draft) => {
        if (!draft || typeof draft !== 'object') return;

        const titleField = builder.querySelector('input[name="titulo"]');
        const descriptionField = builder.querySelector('textarea[name="descricao"]');
        const timeField = builder.querySelector('input[name="tempo_limite"]');
        const attemptsField = builder.querySelector('input[name="tentativas_maximas"]');

        if (titleField) titleField.value = draft.titulo || '';
        if (descriptionField) descriptionField.value = draft.descricao || '';
        if (typeSelect && draft.tipo) typeSelect.value = draft.tipo;
        if (difficultySelect && draft.dificuldade) difficultySelect.value = draft.dificuldade;
        if (lessonSelect && typeof draft.lesson_id !== 'undefined') lessonSelect.value = draft.lesson_id;
        if (moduleSelect && typeof draft.module_id !== 'undefined') moduleSelect.value = draft.module_id;
        if (timeField) timeField.value = draft.tempo_limite || '0';
        if (attemptsField) attemptsField.value = draft.tentativas_maximas || '3';

        ['obrigatorio', 'embaralhar_perguntas', 'embaralhar_respostas', 'mostrar_respostas', 'mostrar_nota'].forEach((name) => {
            const field = builder.querySelector(`input[name="${name}"]`);
            if (field) field.checked = !!draft[name];
        });

        questionList.innerHTML = '';
        if (Array.isArray(draft.questions) && draft.questions.length > 0) {
            draft.questions.forEach((question) => addQuestion(question));
        } else {
            addQuestion();
        }

        syncRelationFields();
        updateTotalPointsSummary();
        setDraftStatus('Rascunho restaurado.');
        updateQuestionStage(0);
    };

    const restoreDraft = () => {
        if (!draftKey || typeof window.localStorage === 'undefined') {
            addQuestion();
            return;
        }

        const raw = window.localStorage.getItem(draftKey);
        if (!raw) {
            addQuestion();
            return;
        }

        try {
            populateBuilder(JSON.parse(raw));
        } catch (error) {
            window.localStorage.removeItem(draftKey);
            addQuestion();
        }
    };

    const removeDraftStorage = (message = 'Rascunho removido.') => {
        if (draftKey && typeof window.localStorage !== 'undefined') {
            window.localStorage.removeItem(draftKey);
        }
        setDraftStatus(message);
    };

    const clearDraft = () => {
        removeDraftStorage();
        if (typeof builder.reset === 'function') {
            builder.reset();
        }
        questionList.innerHTML = '';
        addQuestion();
        syncRelationFields();
        updateTotalPointsSummary();
        updateQuestionStage(0);
    };

    if (typeSelect) {
        typeSelect.addEventListener('change', () => {
            if (difficultySelect && typeSelect.value === 'final' && !difficultySelect.dataset.userChanged) {
                difficultySelect.value = 'dificil';
            }
            if (difficultySelect && typeSelect.value === 'modulo' && !difficultySelect.dataset.userChanged) {
                difficultySelect.value = 'normal';
            }
            syncRelationFields();
        });
        syncRelationFields();
    }

    if (difficultySelect) {
        difficultySelect.addEventListener('change', () => {
            difficultySelect.dataset.userChanged = '1';
        });
    }

    if (addQuestionButton) {
        addQuestionButton.addEventListener('click', () => {
            addQuestion();
            updateTotalPointsSummary();
            queueDraftSave();
        });
    }

    if (clearDraftButton) {
        clearDraftButton.addEventListener('click', () => clearDraft());
    }

    builder.addEventListener('click', (event) => {
        const addOptionTrigger = event.target.closest('[data-add-option]');
        if (addOptionTrigger) {
            const card = addOptionTrigger.closest('[data-question-card]');
            if (card) addOption(card);
            queueDraftSave();
            return;
        }

        const removeOptionTrigger = event.target.closest('[data-remove-option]');
        if (removeOptionTrigger) {
            const card = removeOptionTrigger.closest('[data-question-card]');
            const options = card ? card.querySelectorAll('[data-option-item]') : [];
            if (card && options.length > 2) {
                removeOptionTrigger.closest('[data-option-item]')?.remove();
                updateOptionLabels(card);
                updateQuestionIndices();
                updateTotalPointsSummary();
                renderBuilderProgress();
                queueDraftSave();
            } else {
                showFeedback('Cada pergunta precisa ter pelo menos 2 alternativas.');
            }
            return;
        }

        const removeQuestionTrigger = event.target.closest('[data-remove-question]');
        if (removeQuestionTrigger) {
            const cards = questionList.querySelectorAll('[data-question-card]');
            if (cards.length <= 1) {
                showFeedback('O quiz precisa ter pelo menos uma pergunta.');
                return;
            }
            removeQuestionTrigger.closest('[data-question-card]')?.remove();
            updateQuestionIndices();
            updateTotalPointsSummary();
            updateQuestionStage(Math.max(0, activeQuestionIndex - 1));
            clearFeedback();
            queueDraftSave();
            return;
        }

        const moveTrigger = event.target.closest('[data-move-question]');
        if (moveTrigger) {
            const card = moveTrigger.closest('[data-question-card]');
            if (!card) return;
            const direction = moveTrigger.getAttribute('data-move-question');
            if (direction === 'up' && card.previousElementSibling) {
                questionList.insertBefore(card, card.previousElementSibling);
            }
            if (direction === 'down' && card.nextElementSibling) {
                questionList.insertBefore(card.nextElementSibling, card);
            }
            updateQuestionIndices();
            updateTotalPointsSummary();
            updateQuestionStage(getQuestionCards().indexOf(card));
            queueDraftSave();
        }
    });

    builder.addEventListener('input', (event) => {
        const card = event.target.closest('[data-question-card]');
        if (!card) return;
        if (event.target.matches('[data-option-input]')) {
            updateOptionLabels(card);
            updateQuestionIndices();
        }
        clearFeedback();
        updateTotalPointsSummary();
        renderBuilderProgress();
        queueDraftSave();
    });

    builder.addEventListener('change', () => {
        clearFeedback();
        updateTotalPointsSummary();
        renderBuilderProgress();
        queueDraftSave();
    });

    builder.addEventListener('dragstart', (event) => {
        const handle = event.target.closest('[data-drag-question]');
        const card = event.target.closest('[data-question-card]');
        if (!card || !handle) {
            event.preventDefault();
            return;
        }
        dragSource = card;
        card.classList.add('dragging');
        event.dataTransfer.effectAllowed = 'move';
    });

    builder.addEventListener('dragend', (event) => {
        const card = event.target.closest('[data-question-card]');
        if (card) card.classList.remove('dragging');
        dragSource = null;
        updateTotalPointsSummary();
        queueDraftSave();
    });

    builder.addEventListener('dragover', (event) => {
        const overCard = event.target.closest('[data-question-card]');
        if (!dragSource || !overCard || overCard === dragSource) return;
        event.preventDefault();
        const rect = overCard.getBoundingClientRect();
        const shouldInsertAfter = (event.clientY - rect.top) > rect.height / 2;
        questionList.insertBefore(dragSource, shouldInsertAfter ? overCard.nextSibling : overCard);
        updateQuestionIndices();
        updateQuestionStage(getQuestionCards().indexOf(dragSource));
    });

    if (builderPrev) {
        builderPrev.addEventListener('click', () => {
            updateQuestionStage(activeQuestionIndex - 1);
        });
    }

    if (builderNext) {
        builderNext.addEventListener('click', () => {
            const cards = getQuestionCards();
            if (!cards.length) return;
            if (activeQuestionIndex < cards.length - 1) {
                updateQuestionStage(activeQuestionIndex + 1);
            }
        });
    }

    builder.addEventListener('submit', (event) => {
        clearFeedback();

        const title = builder.querySelector('input[name="titulo"]');
        if (!title || title.value.trim() === '') {
            event.preventDefault();
            showFeedback('Informe o título do quiz.');
            title?.focus();
            return;
        }

        if (typeSelect && typeSelect.value === 'modulo' && moduleSelect && moduleSelect.value === '') {
            event.preventDefault();
            showFeedback('Selecione o módulo vinculado para um quiz de módulo.');
            moduleSelect.focus();
            return;
        }

        const cards = questionList.querySelectorAll('[data-question-card]');
        if (cards.length === 0) {
            event.preventDefault();
            showFeedback('Adicione pelo menos uma pergunta ao quiz.');
            return;
        }

        const totalPoints = getCurrentPointsTotal();
        if (totalPoints !== 20) {
            event.preventDefault();
            showFeedback(totalPoints < 20
                ? `A soma das perguntas precisa fechar 20 valores. Ainda faltam ${20 - totalPoints}.`
                : `A soma das perguntas passou de 20 valores. Remova ${totalPoints - 20}.`);
            return;
        }

        for (const card of cards) {
            const textField = card.querySelector('[data-field="texto"]');
            const pointsField = card.querySelector('[data-field="pontos"]');
            const correctField = card.querySelector('[data-field="correta"]');
            const optionInputs = Array.from(card.querySelectorAll('[data-option-input]'));
            const filledOptions = optionInputs.filter((input) => input.value.trim() !== '');

            if (!textField || textField.value.trim() === '') {
                event.preventDefault();
                showFeedback('Todas as perguntas precisam ter enunciado.');
                textField?.focus();
                return;
            }

            if (filledOptions.length < 2) {
                event.preventDefault();
                showFeedback('Cada pergunta precisa ter pelo menos 2 alternativas preenchidas.');
                optionInputs[0]?.focus();
                return;
            }

            if (!correctField || correctField.value === '') {
                event.preventDefault();
                showFeedback('Selecione a resposta correta de cada pergunta.');
                correctField?.focus();
                return;
            }

            if (!pointsField || Number(pointsField.value) < 1) {
                event.preventDefault();
                showFeedback('A pontuação de cada pergunta deve ser pelo menos 1.');
                pointsField?.focus();
                return;
            }
        }

        removeDraftStorage('Rascunho limpo após envio.');
    });

    restoreDraft();
    updateTotalPointsSummary();
    updateQuestionStage(0);
}

function initQuizRuntime(root = document) {
    const forms = root.querySelectorAll('[data-quiz-runtime]');
    forms.forEach((form) => {
        if (form.dataset.quizRuntimeReady === '1') return;
        form.dataset.quizRuntimeReady = '1';

        const timer = document.querySelector('[data-quiz-timer]');
        const timerValue = timer ? timer.querySelector('[data-quiz-timer-value]') : null;
        const timeoutInput = form.querySelector('[data-quiz-timeout-input]');
        const elapsedInput = form.querySelector('[data-quiz-elapsed-input]');
        const timeoutMessage = form.querySelector('[data-quiz-timeout-message]');
        const interactiveFields = form.querySelectorAll('input:not([type="hidden"]), textarea, select, button[type="submit"]');

        if (!timer || !timerValue) {
            form.addEventListener('submit', () => {
                if (elapsedInput) elapsedInput.value = '0';
            });
            return;
        }

        const startedAt = Number(timer.dataset.startedAt || 0);
        const expiresAt = Number(timer.dataset.expiresAt || 0);
        if (!startedAt || !expiresAt) return;

        let finished = false;
        let intervalId = null;

        const formatRemaining = (seconds) => {
            const safeSeconds = Math.max(0, seconds);
            const minutes = Math.floor(safeSeconds / 60);
            const remainingSeconds = safeSeconds % 60;
            return `${String(minutes).padStart(2, '0')}:${String(remainingSeconds).padStart(2, '0')}`;
        };

        const disableQuizInteraction = () => {
            interactiveFields.forEach((field) => {
                if (field.type === 'hidden') return;
                if (field.matches('input[type="radio"], input[type="checkbox"], select, textarea')) return;
                field.disabled = true;
            });
        };

        const updateClock = () => {
            const now = Math.floor(Date.now() / 1000);
            const remaining = Math.max(0, expiresAt - now);
            const elapsed = Math.max(0, now - startedAt);

            timerValue.textContent = formatRemaining(remaining);
            timer.classList.toggle('is-warning', remaining <= 30);
            if (elapsedInput) elapsedInput.value = String(elapsed);

            if (remaining > 0 || finished) {
                return;
            }

            finished = true;
            form.noValidate = true;
            if (timeoutInput) timeoutInput.value = '1';
            if (elapsedInput) elapsedInput.value = String(Math.max(0, expiresAt - startedAt));
            if (timeoutMessage) {
                timeoutMessage.hidden = false;
                timeoutMessage.textContent = 'Tempo esgotado. O quiz foi enviado automaticamente.';
            }
            disableQuizInteraction();
            if (intervalId) {
                window.clearInterval(intervalId);
            }
            showNotification('Tempo esgotado. O quiz foi enviado automaticamente.', 'error');
            window.setTimeout(() => {
                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit();
                } else {
                    form.submit();
                }
            }, 120);
        };

        form.addEventListener('submit', () => {
            const now = Math.floor(Date.now() / 1000);
            if (elapsedInput) elapsedInput.value = String(Math.max(0, now - startedAt));
            if (intervalId) {
                window.clearInterval(intervalId);
            }
        });

        updateClock();
        intervalId = window.setInterval(updateClock, 1000);
    });
}

function initQuizPlayers(root = document) {
    const players = root.querySelectorAll('[data-quiz-player]');
    players.forEach((player) => {
        if (player.dataset.quizPlayerReady === '1') return;
        player.dataset.quizPlayerReady = '1';

        const questions = Array.from(player.querySelectorAll('[data-quiz-question]'));
        const steps = Array.from(player.querySelectorAll('[data-quiz-step]'));
        const prevButton = player.querySelector('[data-quiz-prev]');
        const nextButton = player.querySelector('[data-quiz-next]');
        const submitButton = player.querySelector('[data-quiz-submit]');
        const labels = Array.from(player.querySelectorAll('[data-quiz-position-label]'));

        if (!questions.length) return;

        let currentIndex = 0;

        const refreshAnswersState = () => {
            questions.forEach((question, index) => {
                const hasRadioAnswer = !!question.querySelector('input[type="radio"]:checked');
                const textarea = question.querySelector('textarea');
                const hasTextAnswer = textarea ? textarea.value.trim() !== '' : false;
                const answered = hasRadioAnswer || hasTextAnswer;
                const step = steps[index];
                if (step) {
                    step.classList.toggle('is-answered', answered);
                }
            });
        };

        const updateState = (nextIndex) => {
            currentIndex = Math.max(0, Math.min(nextIndex, questions.length - 1));

            questions.forEach((question, index) => {
                const active = index === currentIndex;
                question.classList.toggle('is-active', active);
                question.hidden = !active;
            });

            steps.forEach((step, index) => {
                const isActive = index === currentIndex;
                step.classList.toggle('is-active', isActive);
                step.classList.toggle('is-complete', index < currentIndex);
            });

            labels.forEach((label) => {
                label.textContent = `${currentIndex + 1} / ${questions.length}`;
            });

            if (prevButton) {
                prevButton.disabled = currentIndex === 0;
            }

            const isLast = currentIndex === questions.length - 1;
            if (nextButton) {
                nextButton.hidden = isLast;
            }
            if (submitButton) {
                submitButton.hidden = !isLast;
            }

            refreshAnswersState();
        };

        if (prevButton) {
            prevButton.addEventListener('click', () => updateState(currentIndex - 1));
        }

        if (nextButton) {
            nextButton.addEventListener('click', () => updateState(currentIndex + 1));
        }

        steps.forEach((step, index) => {
            step.addEventListener('click', () => updateState(index));
        });

        player.addEventListener('change', refreshAnswersState);
        player.addEventListener('input', refreshAnswersState);

        updateState(0);
    });
}

// Initialize fragment loaders on DOM ready
document.addEventListener('DOMContentLoaded', function () {
    initFragmentLoaders();
});

// Delegation: handler para remover matrícula via AJAX com confirmação
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-remove-enrollment');
    if (!btn) return;
    e.preventDefault();

    const courseId = btn.getAttribute('data-course-id');
    const userId = btn.getAttribute('data-user-id');

    if (!courseId || !userId) return showNotification('Dados inválidos para remoção', 'error');

    const confirmed = confirmAction('Tem certeza que deseja remover este aluno do curso? Esta ação não pode ser desfeita.');
    if (!confirmed) return;

    const formData = new FormData();
    formData.append('acao', 'remover_matricula');
    formData.append('course_id', courseId);
    formData.append('user_id', userId);

    // Enviar como AJAX
    csrfFetch(window.location.pathname + window.location.search, {
        method: 'POST',
        body: formData,
        headers: new Headers({ 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' })
    })
        .then(res => res.json())
        .then(data => {
            if (data && data.sucesso) {
                showNotification(data.mensagem || 'Aluno removido', 'success');
                // remover linha da tabela se presente
                const row = btn.closest('tr');
                let removedRowHtml = null;
                if (row) { removedRowHtml = row.outerHTML; row.remove(); }

                // Criar toast com opção desfazer (criação segura via DOM APIs)
                const undoToast = document.createElement('div');
                undoToast.className = 'toast toast-info';
                undoToast.style.minWidth = '260px';

                const msgSpan = document.createElement('span');
                msgSpan.textContent = 'Aluno removido';
                undoToast.appendChild(msgSpan);

                const undoBtn = document.createElement('button');
                undoBtn.className = 'btn-undo';
                undoBtn.textContent = 'Desfazer';
                undoBtn.style.marginLeft = '12px';
                undoBtn.style.background = 'transparent';
                undoBtn.style.border = 'none';
                undoBtn.style.color = '#fff';
                undoBtn.style.textDecoration = 'underline';
                undoBtn.style.cursor = 'pointer';
                undoToast.appendChild(undoBtn);

                const container = document.getElementById('toast-container') || (function () { const c = document.createElement('div'); c.id = 'toast-container'; c.style.position = 'fixed'; c.style.right = '20px'; c.style.top = '20px'; c.style.zIndex = '12000'; c.style.display = 'flex'; c.style.flexDirection = 'column'; c.style.gap = '10px'; document.body.appendChild(c); return c; })();
                container.appendChild(undoToast);

                const undoTimeout = setTimeout(() => { undoToast.remove(); }, 8000);

                undoBtn.addEventListener('click', function () {
                    clearTimeout(undoTimeout);
                    // chamar endpoint para restaurar
                    const fd = new FormData();
                    fd.append('acao', 'restaurar_matricula');
                    fd.append('course_id', courseId);
                    fd.append('user_id', userId);

                    csrfFetch(window.location.pathname + window.location.search, {
                        method: 'POST', body: fd, headers: new Headers({ 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' })
                    })
                        .then(r => r.json())
                        .then(d => {
                            if (d && d.sucesso) {
                                showNotification(d.mensagem || 'Matrícula restaurada', 'success');
                                // re-inserir row HTML se disponível
                                if (removedRowHtml) {
                                    const tbody = document.querySelector('table tbody');
                                    if (tbody) tbody.insertAdjacentHTML('afterbegin', removedRowHtml);
                                }
                            } else {
                                showNotification(d.mensagem || 'Erro ao restaurar matrícula', 'error');
                            }
                        })
                        .catch(err => { console.error('Erro ao restaurar matrícula:', err); showNotification('Erro ao restaurar matrícula', 'error'); })
                        .finally(() => { undoToast.remove(); });
                });
            } else {
                showNotification(data.mensagem || 'Erro ao remover aluno', 'error');
            }
        })
        .catch(err => {
            console.error('Erro ao remover matrícula:', err);
            showNotification('Erro ao remover aluno', 'error');
        });
});

// Handler para editar progresso — abre modal customizado
function openProgressModal(courseId, userId, currentVal) {
    const inputId = 'progress-input-' + Math.random().toString(36).substr(2, 6);
    const rangeId = 'progress-range-' + Math.random().toString(36).substr(2, 6);

    const body = `
        <div style="display:flex;flex-direction:column;gap:10px;padding:6px">
            <label for="${inputId}">Progresso (%)</label>
            <div style="display:flex;gap:8px;align-items:center">
                <input id="${rangeId}" type="range" min="0" max="100" value="${Number(currentVal || 0)}" style="flex:1">
                <input id="${inputId}" type="number" min="0" max="100" value="${Number(currentVal || 0)}" style="width:72px;padding:6px;border:1px solid #ddd;border-radius:4px">
            </div>
            <small style="color:#666">Defina o progresso concluído deste aluno neste curso (0–100%).</small>
        </div>
    `;

    const footer = `
        <button id="progress-save" class="btn btn-primary">Salvar</button>
        <button class="btn btn-outline" data-modal-close>Cancelar</button>
    `;

    openModal({ title: 'Editar Progresso', body: body, footer: footer });

    // Bind inputs
    const modal = document.getElementById('app-modal');
    if (!modal) return;
    const range = modal.querySelector('#' + rangeId);
    const num = modal.querySelector('#' + inputId);
    const saveBtn = modal.querySelector('#progress-save');

    if (range && num) {
        range.addEventListener('input', () => { num.value = range.value; });
        num.addEventListener('input', () => { let v = parseInt(num.value); if (isNaN(v)) v = 0; if (v < 0) v = 0; if (v > 100) v = 100; num.value = v; range.value = v; });
    }

    if (saveBtn) {
        saveBtn.addEventListener('click', function onSave(e) {
            e.preventDefault();
            const val = parseInt(num.value);
            if (isNaN(val) || val < 0 || val > 100) { showNotification('Progresso inválido (0-100)', 'error'); return; }

            // Prevent double submissions
            saveBtn.disabled = true;
            saveBtn.setAttribute('aria-busy', 'true');

            const fd = new FormData();
            fd.append('acao', 'atualizar_progresso');
            fd.append('course_id', courseId);
            fd.append('user_id', userId);
            fd.append('progress', val);

            csrfFetch(window.location.pathname + window.location.search, {
                method: 'POST', body: fd, headers: new Headers({ 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' })
            })
                .then(r => r.json())
                .then(d => {
                    if (d && d.sucesso) {
                        showNotification(d.mensagem || 'Progresso atualizado', 'success');
                        // Atualizar célula na tabela
                        try {
                            const rows = document.querySelectorAll('table tbody tr');
                            for (const row of rows) {
                                const el = row.querySelector('.btn-edit-progress');
                                if (!el) continue;
                                const cId = el.getAttribute('data-course-id');
                                const uId = el.getAttribute('data-user-id');
                                if (String(cId) === String(courseId) && String(uId) === String(userId)) {
                                    const cell = row.querySelector('td:nth-child(4)');
                                    if (cell) cell.textContent = (d.progress ?? val) + '%';
                                    break;
                                }
                            }
                        } catch (err) { console.warn('Erro ao atualizar célula de progresso:', err); }
                        closeModal();
                    } else {
                        showNotification(d.mensagem || 'Erro ao atualizar progresso', 'error');
                        saveBtn.disabled = false;
                        saveBtn.removeAttribute('aria-busy');
                    }
                })
                .catch(err => { console.error('Erro ao atualizar progresso:', err); showNotification('Erro ao atualizar progresso', 'error'); saveBtn.disabled = false; saveBtn.removeAttribute('aria-busy'); });
        }, { once: true });
    }
}

document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-edit-progress');
    if (!btn) return;
    e.preventDefault();

    const courseId = btn.getAttribute('data-course-id');
    const userId = btn.getAttribute('data-user-id');
    if (!courseId || !userId) return showNotification('Dados inválidos', 'error');

    const currentRow = btn.closest('tr');
    const currentProgressText = currentRow ? currentRow.querySelector('td:nth-child(4)') : null;
    let currentVal = 0;
    if (currentProgressText) {
        const m = currentProgressText.textContent.match(/(\d+)/);
        if (m) currentVal = parseInt(m[1]);
    }

    openProgressModal(courseId, userId, currentVal);
});

/* ==========================
   Counter bootstrap helper
   ========================== */

function initDashboardCounters(data) {
    // data: { cursos: N, alunos: N, aulas: N, atividades: N }
    try {
        if (data.cursos !== undefined) animateNumber('counter-cursos', data.cursos, 800);
        if (data.alunos !== undefined) animateNumber('counter-alunos', data.alunos, 800);
        if (data.aulas !== undefined) animateNumber('counter-aulas', data.aulas, 800);
        if (data.atividades !== undefined) animateNumber('counter-atividades', data.atividades, 800);
    } catch (err) {
        console.warn('Erro ao iniciar contadores:', err);
    }
}
