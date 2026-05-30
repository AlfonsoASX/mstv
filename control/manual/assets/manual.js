(function () {
    const $ = (selector, root = document) => root.querySelector(selector);
    const $$ = (selector, root = document) => Array.from(root.querySelectorAll(selector));

    function normalize(value) {
        return (value || '')
            .toString()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase();
    }

    function setupSearch() {
        $$('[data-search-input]').forEach((input) => {
            const targetSelector = input.getAttribute('data-search-input');
            const root = targetSelector ? $(targetSelector) : document;
            const items = root ? $$('[data-search-item]', root) : [];
            const empty = root ? $('[data-no-results]', root) : null;

            input.addEventListener('input', () => {
                const term = normalize(input.value.trim());
                let visible = 0;

                items.forEach((item) => {
                    const haystack = normalize(item.textContent);
                    const matches = !term || haystack.includes(term);
                    item.classList.toggle('search-hidden', !matches);
                    if (matches) visible += 1;
                });

                if (empty) {
                    empty.classList.toggle('visible', visible === 0);
                }
            });
        });
    }

    function setupActiveNavigation() {
        const links = $$('.nav-list a[href^="#"], .module-subnav a[href^="#"]');
        const sections = links
            .map((link) => $(link.getAttribute('href')))
            .filter(Boolean);

        if (!sections.length) return;

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                links.forEach((link) => {
                    link.classList.toggle('active', link.getAttribute('href') === '#' + entry.target.id);
                });
            });
        }, { rootMargin: '-20% 0px -70% 0px', threshold: 0.01 });

        sections.forEach((section) => observer.observe(section));
    }

    function setupCopyButtons() {
        $$('[data-copy]').forEach((button) => {
            button.addEventListener('click', async () => {
                const text = button.getAttribute('data-copy') || '';
                try {
                    await navigator.clipboard.writeText(text);
                    const original = button.textContent;
                    button.textContent = 'Copiado';
                    setTimeout(() => { button.textContent = original; }, 1200);
                } catch (_) {
                    button.textContent = 'Copia manual';
                }
            });
        });

        $$('.copy-code').forEach((button) => {
            button.addEventListener('click', async () => {
                const block = button.closest('.code-block');
                const code = block ? $('pre', block) : null;
                if (!code) return;
                try {
                    await navigator.clipboard.writeText(code.textContent.trim());
                    button.textContent = 'Copiado';
                    setTimeout(() => { button.textContent = 'Copiar'; }, 1200);
                } catch (_) {
                    button.textContent = 'Selecciona';
                }
            });
        });
    }

    function setupLoadedTextBlocks() {
        $$('[data-load-text]').forEach(async (block) => {
            const source = block.getAttribute('data-load-text');
            if (!source) return;

            try {
                const response = await fetch(source, { cache: 'no-store' });
                if (!response.ok) {
                    throw new Error('No se pudo cargar el archivo');
                }
                block.textContent = await response.text();
            } catch (_) {
                block.textContent = 'No se pudo cargar el archivo SQL. Abre el enlace de descarga para consultarlo.';
            }
        });
    }

    function setupTabs() {
        $$('[data-tabs]').forEach((tabs) => {
            const buttons = $$('[data-tab-target]', tabs);
            const panels = $$('[data-tab-panel]', tabs);

            buttons.forEach((button) => {
                button.addEventListener('click', () => {
                    const target = button.getAttribute('data-tab-target');
                    buttons.forEach((candidate) => candidate.classList.toggle('active', candidate === button));
                    panels.forEach((panel) => panel.classList.toggle('active', panel.getAttribute('data-tab-panel') === target));
                });
            });
        });
    }

    function setupBackTop() {
        const button = $('[data-back-top]');
        if (!button) return;

        window.addEventListener('scroll', () => {
            button.classList.toggle('visible', window.scrollY > 700);
        });

        button.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    function setupAnchorButtons() {
        $$('.section[id]').forEach((section) => {
            const header = $('.section-header', section);
            if (!header) return;
            const button = document.createElement('button');
            button.className = 'anchor-copy';
            button.type = 'button';
            button.textContent = '#';
            button.title = 'Copiar enlace a esta sección';
            button.setAttribute('data-copy', window.location.href.split('#')[0] + '#' + section.id);
            header.appendChild(button);
        });

        $$('.module-detail[id]').forEach((module) => {
            const title = $('h3', module);
            if (!title) return;
            const button = document.createElement('button');
            button.className = 'anchor-copy';
            button.type = 'button';
            button.textContent = '#';
            button.title = 'Copiar enlace a este módulo';
            button.setAttribute('data-copy', window.location.href.split('#')[0] + '#' + module.id);
            title.appendChild(button);
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        setupAnchorButtons();
        setupLoadedTextBlocks();
        setupSearch();
        setupActiveNavigation();
        setupCopyButtons();
        setupTabs();
        setupBackTop();
    });
})();
