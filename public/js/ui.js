/**
 * UI Interactions - Plataforma EAD
 */

document.addEventListener('DOMContentLoaded', function() {
    initFormInteractions();
    initCardAnimations();
    initProgressAnimations();
    initTooltips();
});

/**
 * Interações de Formulário
 */
function initFormInteractions() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        const senhaInputs = form.querySelectorAll('input[type="password"]');
        senhaInputs.forEach(input => {
            if (input.parentElement && input.parentElement.classList.contains('field-with-toggle')) {
                if (input.parentElement.querySelector('.toggle-password')) {
                    return;
                }
            }

            const fieldWrapper = document.createElement('div');
            fieldWrapper.className = 'field-with-toggle';

            const currentParent = input.parentElement;
            if (!currentParent) {
                return;
            }

            currentParent.insertBefore(fieldWrapper, input);
            fieldWrapper.appendChild(input);

            const toggleBtn = document.createElement('button');
            toggleBtn.type = 'button';
            toggleBtn.textContent = '◉';
            toggleBtn.className = 'toggle-password';
            toggleBtn.setAttribute('aria-label', 'Mostrar senha');
            toggleBtn.setAttribute('aria-pressed', 'false');

            fieldWrapper.appendChild(toggleBtn);
            
            toggleBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const visible = input.type === 'password';
                input.type = visible ? 'text' : 'password';
                toggleBtn.textContent = visible ? '◎' : '◉';
                toggleBtn.setAttribute('aria-label', visible ? 'Ocultar senha' : 'Mostrar senha');
                toggleBtn.setAttribute('aria-pressed', String(visible));
            });
        });
        
        const inputs = form.querySelectorAll('input, textarea');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('is-invalid');
            });
        });
    });
}

/**
 * Animações nos Cards
 */
function initCardAnimations() {
    const cards = document.querySelectorAll('.course-card, .stat-card, .info-card');

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        
        cards.forEach(card => observer.observe(card));
    }
}

/**
 * Animações de Progresso
 */
function initProgressAnimations() {
    const progressBars = document.querySelectorAll('.progresso-fill');
    const compactProgressBars = document.querySelectorAll('.progress-small-fill');

    progressBars.forEach(bar => {
        const targetWidth = Number.parseFloat(bar.dataset.progress || '0');
        bar.style.setProperty('--progress', `${Math.max(0, Math.min(100, targetWidth))}%`);
    });

    compactProgressBars.forEach(bar => {
        const targetWidth = Number.parseFloat(bar.dataset.progress || '0');
        bar.style.setProperty('--progress', `${Math.max(0, Math.min(100, targetWidth))}%`);
    });
    
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const bar = entry.target;
                    const targetWidth = bar.style.getPropertyValue('--progress') || '0%';
                    bar.style.setProperty('--progress', '0%');
                    
                    setTimeout(() => {
                        bar.style.transition = 'width 1s ease';
                        bar.style.setProperty('--progress', targetWidth);
                    }, 100);
                    
                    observer.unobserve(bar);
                }
            });
        }, { threshold: 0.5 });
        
        progressBars.forEach(bar => observer.observe(bar));
    }
}

/**
 * Tooltips
 */
function initTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = this.getAttribute('data-tooltip');
            tooltip.style.cssText = `
                position: absolute;
                background: rgba(0, 0, 0, 0.8);
                color: white;
                padding: 8px 12px;
                border-radius: 4px;
                font-size: 12px;
                white-space: nowrap;
                z-index: 1000;
                pointer-events: none;
            `;
            
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.left = (rect.left + rect.width / 2 - tooltip.offsetWidth / 2) + 'px';
            tooltip.style.top = (rect.top - tooltip.offsetHeight - 8) + 'px';
            
            element.tooltip = tooltip;
        });
        
        element.addEventListener('mouseleave', function() {
            if (this.tooltip) {
                this.tooltip.remove();
                delete this.tooltip;
            }
        });
    });
}

/**
 * Lazy Loading de Imagens
 */
if ('IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.classList.add('loaded');
                imageObserver.unobserve(img);
            }
        });
    });
    
    document.querySelectorAll('img[data-src]').forEach(img => imageObserver.observe(img));
}

/**
 * Smooth scroll anchor links
 */
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        const href = this.getAttribute('href');
        if (href !== '#') {
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth' });
            }
        }
    });
});

/**
 * Ao focar em campo de busca, mostrar histórico
 */
function initSearchHistory() {
    const searchInput = document.querySelector('.search-input');
    if (!searchInput) return;
    
    searchInput.addEventListener('focus', function() {
        const history = localStorage.getItem('searchHistory');
        if (history) {
            console.log('Histórico de buscas:', JSON.parse(history));
        }
    });
    
    searchInput.addEventListener('change', function() {
        if (this.value.trim()) {
            let history = JSON.parse(localStorage.getItem('searchHistory') || '[]');
            if (!history.includes(this.value)) {
                history.unshift(this.value);
                history = history.slice(0, 5);
                localStorage.setItem('searchHistory', JSON.stringify(history));
            }
        }
    });
}

// Inicializar histórico de busca
initSearchHistory();

// CSS para animações
if (!document.querySelector('style[data-ui-animations]')) {
    const style = document.createElement('style');
    style.setAttribute('data-ui-animations', 'true');
    style.innerHTML = `
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }
        
        img.loaded {
            animation: fadeInUp 0.5s ease;
        }
    `;
    document.head.appendChild(style);
}
