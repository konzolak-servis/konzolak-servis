// Konzolák Zlín – veřejný web. Minimum JS, bez závislostí.
(function () {
    var t = document.querySelector('.navtoggle');
    var n = document.getElementById('nav');
    if (t && n) {
        t.addEventListener('click', function () {
            var open = n.classList.toggle('is-open');
            t.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }
})();
