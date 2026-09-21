// Přihlášení/odhlášení k web push notifikacím (VAPID). Vanilla JS, žádné závislosti.
window.KsPush = (function () {
    function b64urlToUint8Array(base64url) {
        var padding = '='.repeat((4 - (base64url.length % 4)) % 4);
        var base64 = (base64url + padding).replace(/-/g, '+').replace(/_/g, '/');
        var raw = window.atob(base64);
        var arr = new Uint8Array(raw.length);
        for (var i = 0; i < raw.length; i++) arr[i] = raw.charCodeAt(i);
        return arr;
    }

    function getCsrf() {
        var m = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
        return m ? decodeURIComponent(m[1]) : '';
    }

    function post(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-XSRF-TOKEN': getCsrf() },
            credentials: 'same-origin',
            body: JSON.stringify(body || {}),
        });
    }

    function podporovano() {
        return 'serviceWorker' in navigator && 'PushManager' in window && window.isSecureContext;
    }

    function jeAktivni() {
        if (!podporovano()) return Promise.resolve(false);
        return navigator.serviceWorker.ready
            .then(function (reg) { return reg.pushManager.getSubscription(); })
            .then(function (sub) { return !!sub; });
    }

    function prihlasit(vapidPublicKey) {
        return navigator.serviceWorker.ready
            .then(function (reg) {
                return reg.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: b64urlToUint8Array(vapidPublicKey),
                });
            })
            .then(function (sub) {
                var json = sub.toJSON();
                json.zarizeni = navigator.userAgent.slice(0, 120);
                return post('/push/subscribe', json);
            });
    }

    function odhlasit() {
        return navigator.serviceWorker.ready
            .then(function (reg) { return reg.pushManager.getSubscription(); })
            .then(function (sub) {
                if (!sub) return;
                var endpoint = sub.endpoint;
                return sub.unsubscribe().then(function () {
                    return post('/push/unsubscribe', { endpoint: endpoint });
                });
            });
    }

    return { podporovano: podporovano, jeAktivni: jeAktivni, prihlasit: prihlasit, odhlasit: odhlasit };
})();
