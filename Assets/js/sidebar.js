(() => {
    const sidebar = document.getElementById('sidebar');
    if (!sidebar) return;
    const toggle = document.getElementById('sidebarToggle');
    const close = document.getElementById('sidebarClose');
    const backdrop = document.getElementById('sidebarBackdrop');
    const workspace = document.querySelector('.admin-shell .workspace');
    const mobile = window.matchMedia('(max-width: 760px)');
    const storageKey = 'ejecuttrans.sidebar.collapsed';
    let collapsed = false;
    try { collapsed = localStorage.getItem(storageKey) === '1'; } catch (_) {}

    function sync() {
        const isMobile = mobile.matches;
        const open = isMobile ? document.body.classList.contains('sidebar-open') : !collapsed;
        document.body.classList.toggle('sidebar-collapsed', !isMobile && collapsed);
        if (!isMobile) document.body.classList.remove('sidebar-open');
        toggle.setAttribute('aria-expanded', String(open));
        toggle.setAttribute('aria-label', open ? 'Ocultar menú' : 'Mostrar menú');
        sidebar.inert = !open;
        workspace.inert = isMobile && open;
        backdrop.hidden = !isMobile || !open;
    }

    toggle.addEventListener('click', () => {
        if (mobile.matches) document.body.classList.toggle('sidebar-open');
        else {
            collapsed = !collapsed;
            try { localStorage.setItem(storageKey, collapsed ? '1' : '0'); } catch (_) {}
        }
        sync();
        if (mobile.matches && document.body.classList.contains('sidebar-open')) close.focus();
    });
    function closeMobile() {
        document.body.classList.remove('sidebar-open');
        sync();
        toggle.focus();
    }
    close.addEventListener('click', () => {
        if (mobile.matches) closeMobile();
        else {
            collapsed = true;
            try { localStorage.setItem(storageKey, '1'); } catch (_) {}
            sync();
            toggle.focus();
        }
    });
    backdrop.addEventListener('click', closeMobile);
    sidebar.querySelectorAll('a').forEach(link => link.addEventListener('click', () => {
        if (mobile.matches) document.body.classList.remove('sidebar-open');
    }));
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && mobile.matches && document.body.classList.contains('sidebar-open')) closeMobile();
    });
    mobile.addEventListener('change', sync);
    sync();
})();
