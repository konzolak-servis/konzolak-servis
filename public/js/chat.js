// Konzolák Zlín – AI asistent (chat) s animovanou postavičkou. Bez knihoven; konverzace jen v paměti prohlížeče.
(function () {
    var root = document.getElementById('ks-chat');
    if (!root) { return; }
    var fab = root.querySelector('.kc__fab');
    var panel = root.querySelector('.kc__panel');
    var list = root.querySelector('.kc__list');
    var form = root.querySelector('.kc__form');
    var input = root.querySelector('.kc__input');
    var send = root.querySelector('.kc__send');
    var status = root.querySelector('.kc__status');
    var teaser = root.querySelector('.kc__teaser');
    var url = root.getAttribute('data-url');
    var token = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
    var KEY = 'ks_chat_v1';
    var hist = [];
    var busy = false;
    var klidne = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var tplM = document.getElementById('ks-mascot');

    try { hist = JSON.parse(sessionStorage.getItem(KEY) || '[]') || []; } catch (e) { hist = []; }

    // postavička do všech míst (tlačítko, hlavička)
    if (tplM) {
        root.querySelectorAll('[data-mascot]').forEach(function (m) { m.appendChild(tplM.content.cloneNode(true)); });
    }

    function stav(s) {
        root.setAttribute('data-state', s);
        if (status) {
            status.textContent = s === 'think' ? 'přemýšlí…' : (s === 'talk' ? 'píše…' : 'virtuální asistent (AI)');
        }
    }

    function ulozit() {
        try { sessionStorage.setItem(KEY, JSON.stringify(hist.slice(-16))); } catch (e) {}
    }

    // text → DOM s odkazy (jen http/https a relativní adresy webu), bez innerHTML; odstraní markdown značky
    function plnit(el, text) {
        el.textContent = '';
        text = String(text).replace(/\*\*([^*]+)\*\*/g, '$1').replace(/^#{1,4}\s+/gm, '').replace(/(^|\s)\*(?=\S)/g, '$1');
        var re = /(https?:\/\/[^\s)]+|\/(?:cenik|kontakt|zaslani-zarizeni|vykup-zarizeni|kontrola-zakazky|pravni\/[a-z-]+)[^\s)]*)/g;
        var last = 0, m;
        while ((m = re.exec(text)) !== null) {
            if (m.index > last) { el.appendChild(document.createTextNode(text.slice(last, m.index))); }
            var a = document.createElement('a');
            a.href = m[0]; a.textContent = m[0]; a.rel = 'noopener';
            if (m[0].charAt(0) !== '/') { a.target = '_blank'; }
            el.appendChild(a);
            last = m.index + m[0].length;
        }
        if (last < text.length) { el.appendChild(document.createTextNode(text.slice(last))); }
    }

    function bublina(role, text) {
        var d = document.createElement('div');
        d.className = 'kc__m kc__m--' + (role === 'user' ? 'u' : 'a');
        if (text) { plnit(d, text); }
        list.appendChild(d);
        list.scrollTop = list.scrollHeight;
        return d;
    }

    function pisu() {
        var d = bublina('assistant', '');
        d.classList.add('kc__m--typing');
        d.innerHTML = '<i></i><i></i><i></i>';
        return d;
    }

    // odpověď se „napíše" po částech a postavička u toho mluví
    function napsat(el, text, hotovo) {
        el.classList.remove('kc__m--typing');
        var plny = String(text);
        if (klidne || plny.length < 4) { plnit(el, plny); hotovo(); return; }
        stav('talk');
        var i = 0, krok = Math.max(2, Math.ceil(plny.length / 140));
        var t = setInterval(function () {
            i += krok;
            if (i >= plny.length) {
                clearInterval(t);
                plnit(el, plny);
                list.scrollTop = list.scrollHeight;
                stav('idle');
                hotovo();
                return;
            }
            el.textContent = plny.slice(0, i);
            list.scrollTop = list.scrollHeight;
        }, 22);
    }

    function uvod() {
        var d = bublina('assistant', 'Čau, jsem Konzolák – virtuální asistent servisu. Poradím s opravou a orientační cenou, zjistím stav zakázky (stačí číslo zakázky a příjmení, e-mail nebo telefon z dokladu) nebo pomohu odeslat poptávku. Co se děje se zařízením?');
        d.classList.add('kc__m--pop');
    }

    function otevrit(ano) {
        panel.hidden = !ano;
        fab.setAttribute('aria-expanded', ano ? 'true' : 'false');
        root.classList.toggle('is-open', ano);
        if (teaser) { teaser.hidden = true; }
        utisitPozdrav();
        if (ano) {
            stav('hello');
            setTimeout(function () { if (root.getAttribute('data-state') === 'hello') { stav('idle'); } }, 1400);
            setTimeout(function () { input.focus(); }, 60);
        }
    }

    fab.addEventListener('click', function () { otevrit(panel.hidden); });
    root.querySelector('.kc__close').addEventListener('click', function () { otevrit(false); });

    if (hist.length) {
        hist.forEach(function (m) { bublina(m.role, m.text); });
    } else {
        uvod();
    }

    // pozdravy „vykouknou" postupně (každý jiný), dokud je zákazník nezavře křížkem nebo neotevře chat (pak 24 h klid)
    var POZDRAVY = [
        'Něco nefunguje? Zeptej se mě!',
        'Konzole hučí nebo se sama vypíná? Poradím!',
        'Chceš znát orientační cenu opravy? Napiš model.',
        'Stav zakázky ti zjistím – stačí číslo zakázky.',
        'Driftuje ovladač? Tohle znám, zeptej se.',
        'Nemáš čas jezdit? Poradím, jak zařízení poslat poštou.',
        'Kupuju i staré konzole a ovladače – nabídni mi je.',
        'Nevíš, co se zařízením je? Popiš mi to.'
    ];
    function utisitPozdrav() {
        try { localStorage.setItem('ks_chat_teaser_do', String(Date.now() + 24 * 3600 * 1000)); } catch (e) {}
    }
    var klid = false, idx = 0;
    try {
        klid = Date.now() < Number(localStorage.getItem('ks_chat_teaser_do') || 0);
        idx = Number(localStorage.getItem('ks_chat_teaser_i') || 0) % POZDRAVY.length;
    } catch (e) {}

    function ukazPozdrav(pocet) {
        if (!teaser || pocet <= 0) { return; }
        if (!panel.hidden || Date.now() < Number(safeGet('ks_chat_teaser_do'))) { return; }
        var txt = teaser.querySelector('span');
        if (txt) { txt.textContent = POZDRAVY[idx % POZDRAVY.length]; }
        idx = (idx + 1) % POZDRAVY.length;
        try { localStorage.setItem('ks_chat_teaser_i', String(idx)); } catch (e) {}
        teaser.hidden = false;
        // restart animace, aby bublina pokaždé „vyskočila"
        teaser.style.animation = 'none'; void teaser.offsetWidth; teaser.style.animation = '';
        stav('hello');
        setTimeout(function () { if (root.getAttribute('data-state') === 'hello') { stav('idle'); } }, 1600);
        setTimeout(function () {
            teaser.hidden = true;
            setTimeout(function () { ukazPozdrav(pocet - 1); }, 18000);
        }, 9000);
    }
    function safeGet(k) { try { return Number(localStorage.getItem(k) || 0); } catch (e) { return 0; } }

    if (teaser && !klid) {
        setTimeout(function () { ukazPozdrav(3); }, 3500);
        teaser.addEventListener('click', function (e) {
            if (e.target.closest('.kc__teaser-x')) { teaser.hidden = true; utisitPozdrav(); return; }
            otevrit(true);
        });
    }

    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); }
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var text = input.value.trim();
        if (!text || busy) { return; }
        busy = true; send.disabled = true;
        input.value = '';
        var u = bublina('user', text);
        u.classList.add('kc__m--pop');
        hist.push({ role: 'user', text: text });
        var cekam = pisu();
        stav('think');

        fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify({ zpravy: hist.slice(-14) })
        }).then(function (r) {
            if (!r.ok) { throw new Error('http ' + r.status); }
            return r.json();
        }).then(function (j) {
            var o = (j && j.odpoved) ? String(j.odpoved) : 'Odpověď se nepodařilo načíst.';
            hist.push({ role: 'assistant', text: o });
            ulozit();
            cekam.classList.add('kc__m--pop');
            napsat(cekam, o, function () { busy = false; send.disabled = false; input.focus(); });
        }).catch(function () {
            stav('idle');
            cekam.classList.remove('kc__m--typing');
            plnit(cekam, 'Spojení se nepovedlo. Zkuste to prosím znovu, nebo zavolejte či napište přes formulář.');
            hist.pop(); // poslední zpráva zákazníka se neodeslala
            ulozit();
            busy = false; send.disabled = false;
        });
    });
})();
