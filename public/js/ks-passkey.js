/**
 * Passkey / WebAuthn – minimální pomocník pro přihlášení otiskem (Konzolák servis).
 * Backend: laragear/webauthn, routy passkey/login|register(/options).
 */
(function () {
    function b64urlToBytes(s) {
        s = s.replace(/-/g, "+").replace(/_/g, "/");
        while (s.length % 4) s += "=";
        return Uint8Array.from(atob(s), function (c) { return c.charCodeAt(0); });
    }
    function bytesToB64url(buf) {
        var bin = String.fromCharCode.apply(null, new Uint8Array(buf));
        return btoa(bin).replace(/\+/g, "-").replace(/\//g, "_").replace(/=+$/, "");
    }
    function csrf() {
        var m = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
        return m ? decodeURIComponent(m[1]).replace(/%3D/g, "") : "";
    }
    function post(url, body) {
        return fetch(url, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/json",
                "Accept": "application/json",
                "X-Requested-With": "XMLHttpRequest",
                "X-XSRF-TOKEN": csrf(),
            },
            body: JSON.stringify(body || {}),
        });
    }
    function prepPublicKey(pk) {
        pk.challenge = b64urlToBytes(pk.challenge);
        if (pk.user && pk.user.id) pk.user.id = b64urlToBytes(pk.user.id);
        ["excludeCredentials", "allowCredentials"].forEach(function (k) {
            if (pk[k]) pk[k] = pk[k].map(function (c) { return Object.assign({}, c, { id: b64urlToBytes(c.id) }); });
        });
        return pk;
    }
    function serialize(cred) {
        var out = {
            id: cred.id,
            type: cred.type,
            rawId: bytesToB64url(cred.rawId),
            authenticatorAttachment: cred.authenticatorAttachment,
            clientExtensionResults: cred.getClientExtensionResults(),
            response: {},
        };
        ["clientDataJSON", "attestationObject", "authenticatorData", "signature", "userHandle"].forEach(function (k) {
            if (k in cred.response && cred.response[k]) out.response[k] = bytesToB64url(cred.response[k]);
        });
        return out;
    }

    window.KsPasskey = {
        supported: typeof PublicKeyCredential !== "undefined",

        async register(alias) {
            var r = await post("/passkey/register/options");
            if (!r.ok) throw new Error("options " + r.status);
            var pk = prepPublicKey(await r.json());
            var cred = await navigator.credentials.create({ publicKey: pk });
            var payload = serialize(cred);
            if (alias) payload.alias = alias;
            var save = await post("/passkey/register", payload);
            if (!save.ok) throw new Error("register " + save.status);
            return true;
        },

        async login(email) {
            var r = await post("/passkey/login/options", email ? { email: email } : {});
            if (!r.ok) throw new Error("options " + r.status);
            var pk = prepPublicKey(await r.json());
            var cred = await navigator.credentials.get({ publicKey: pk });
            var res = await post("/passkey/login", serialize(cred));
            return res.status === 204 || res.ok;
        },
    };
})();
