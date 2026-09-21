<x-filament-panels::page>
    <div class="ks-sken">
        <p class="ks-sken-hint">Namiř telefon na QR kód na štítku zařízení nebo na dokladu – po rozpoznání tě systém
            rovnou přepne na tu zakázku.</p>

        <div class="ks-sken-viewport">
            <video id="ks-sken-video" playsinline muted></video>
            <div id="ks-sken-frame"></div>
        </div>

        <p id="ks-sken-status" class="ks-sken-status">Spouštím kameru…</p>

        <button type="button" id="ks-sken-retry" class="fi-btn fi-btn-color-gray fi-btn-size-md" style="display:none">
            Zkusit znovu
        </button>
    </div>

    <canvas id="ks-sken-canvas" style="display:none"></canvas>

    <style>
        .ks-sken { max-width: 480px; margin: 0 auto; text-align: center; }
        .ks-sken-hint { color: rgb(113 113 122); margin-bottom: 1rem; }
        .ks-sken-viewport { position: relative; border-radius: .75rem; overflow: hidden; background: #000;
            aspect-ratio: 1 / 1; }
        #ks-sken-video { width: 100%; height: 100%; object-fit: cover; }
        #ks-sken-frame { position: absolute; inset: 12%; border: 3px solid rgba(200,153,46,.85);
            border-radius: .75rem; pointer-events: none;
            box-shadow: 0 0 0 999px rgba(0,0,0,.35); }
        .ks-sken-status { margin-top: 1rem; font-weight: 600; }
        .ks-sken-status.err { color: rgb(220 38 38); }
        .ks-sken-status.ok { color: rgb(5 150 105); }
    </style>

    <script src="{{ asset('js/jsqr.min.js') }}"></script>
    <script>
        (function () {
            var video = document.getElementById('ks-sken-video');
            var canvas = document.getElementById('ks-sken-canvas');
            var ctx = canvas.getContext('2d', { willReadFrequently: true });
            var status = document.getElementById('ks-sken-status');
            var retryBtn = document.getElementById('ks-sken-retry');
            var stream = null;
            var rafId = null;
            var zpracovano = false;

            function chyba(text) {
                status.textContent = text;
                status.className = 'ks-sken-status err';
                retryBtn.style.display = 'inline-flex';
            }

            function zastavKameru() {
                if (rafId) cancelAnimationFrame(rafId);
                if (stream) stream.getTracks().forEach(function (t) { t.stop(); });
            }

            function zpracujOdkaz(text) {
                // Očekáváme URL naší veřejné stránky stavu: .../z/{id}/{token}
                var shoda = text.match(/\/z\/(\d+)\/([a-zA-Z0-9]+)/);
                if (!shoda) {
                    chyba('Tohle není QR kód ze štítku Konzolák. Zkus to znovu.');
                    return;
                }
                zpracovano = true;
                zastavKameru();
                status.textContent = 'Zakázka nalezena, otevírám…';
                status.className = 'ks-sken-status ok';
                window.location.href = '/stitek/' + shoda[1] + '/' + shoda[2];
            }

            function tick() {
                if (zpracovano) return;
                if (video.readyState === video.HAVE_ENOUGH_DATA) {
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                    var data = ctx.getImageData(0, 0, canvas.width, canvas.height);
                    var kod = window.jsQR(data.data, data.width, data.height);
                    if (kod && kod.data) {
                        zpracujOdkaz(kod.data);
                        return;
                    }
                }
                rafId = requestAnimationFrame(tick);
            }

            function spustKameru() {
                zpracovano = false;
                retryBtn.style.display = 'none';
                status.textContent = 'Spouštím kameru…';
                status.className = 'ks-sken-status';

                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    chyba('Prohlížeč nepodporuje přístup ke kameře.');
                    return;
                }
                if (typeof window.jsQR !== 'function') {
                    chyba('Nepodařilo se načíst skener QR kódů.');
                    return;
                }

                navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
                    .then(function (s) {
                        stream = s;
                        video.srcObject = s;
                        video.setAttribute('playsinline', true);
                        video.play();
                        status.textContent = 'Namiř na QR kód…';
                        rafId = requestAnimationFrame(tick);
                    })
                    .catch(function () {
                        chyba('Nepovolil jsi přístup ke kameře. Zkontroluj oprávnění prohlížeče.');
                    });
            }

            retryBtn.addEventListener('click', spustKameru);
            window.addEventListener('beforeunload', zastavKameru);
            spustKameru();
        })();
    </script>
</x-filament-panels::page>
