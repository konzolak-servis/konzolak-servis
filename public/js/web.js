// Konzolák Zlín – veřejný web. Minimum JS, bez závislostí.
(function () {
    // mobilní menu
    var t = document.querySelector('.navtoggle');
    var n = document.getElementById('nav');
    if (t && n) {
        t.addEventListener('click', function () {
            var open = n.classList.toggle('is-open');
            t.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    // jemné světlo pod kurzorem na panelech
    var SEL = '.card, .trust__i, .kroky__i, .misto, .faq details, .stav-card';
    if (window.matchMedia && window.matchMedia('(pointer: fine)').matches) {
        var raf = 0, last = null, lx = 0, ly = 0;
        document.addEventListener('pointermove', function (e) {
            var el = e.target.closest ? e.target.closest(SEL) : null;
            if (!el) { return; }
            last = el; lx = e.clientX; ly = e.clientY;
            if (raf) { return; }
            raf = requestAnimationFrame(function () {
                raf = 0;
                if (!last) { return; }
                var r = last.getBoundingClientRect();
                last.style.setProperty('--mx', (lx - r.left).toFixed(1) + 'px');
                last.style.setProperty('--my', (ly - r.top).toFixed(1) + 'px');
            });
        }, { passive: true });
        document.addEventListener('pointerout', function (e) {
            var el = e.target.closest ? e.target.closest(SEL) : null;
            if (el && (!e.relatedTarget || !el.contains(e.relatedTarget))) {
                el.style.removeProperty('--mx');
                el.style.removeProperty('--my');
            }
        }, { passive: true });
    }
})();
