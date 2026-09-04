<x-filament-panels::page>
    <div class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Passkey umožní přihlášení pouze otiskem prstu / obličejem, bez hesla. Zaregistruj si každé
            zařízení (telefon, počítač), ze kterého se chceš takto přihlašovat. Funguje jen pro účet administrátora.
        </p>

        <div class="mt-4">
            <button type="button" id="ks-passkey-add"
                class="fi-btn fi-btn-size-md inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white hover:bg-primary-500">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 11c0-3 2-4 2-4"/><path d="M7 8a5 5 0 0 1 10 0v3"/><path d="M7 12v3a5 5 0 0 0 .5 2"/><path d="M12 12v4"/><path d="M17 14v1a7 7 0 0 1-1 3.5"/></svg>
                Přidat toto zařízení
            </button>
            <span id="ks-passkey-add-msg" class="ml-3 text-sm"></span>
        </div>
    </div>

    <div class="fi-section mt-6 rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="border-b border-gray-200 px-6 py-3 text-sm font-semibold dark:border-white/10">
            Zaregistrovaná zařízení ({{ $klice->count() }})
        </div>

        @forelse ($klice as $k)
            <div class="flex items-center justify-between gap-4 border-b border-gray-100 px-6 py-3 text-sm last:border-0 dark:border-white/5">
                <div>
                    <div class="font-medium">{{ $k->alias ?: 'Zařízení' }}</div>
                    <div class="text-xs text-gray-500">přidáno {{ $k->created_at?->format('d.m.Y H:i') }}</div>
                </div>
                <x-filament::button color="danger" size="sm" outlined
                    wire:click="smazat('{{ $k->id }}')"
                    wire:confirm="Opravdu odebrat tento passkey?">
                    Odebrat
                </x-filament::button>
            </div>
        @empty
            <div class="px-6 py-4 text-sm text-gray-500">Zatím žádné. Přidej si toto zařízení tlačítkem výše.</div>
        @endforelse
    </div>

    <script>
        window.addEventListener('load', function () {
            var btn = document.getElementById('ks-passkey-add');
            var msg = document.getElementById('ks-passkey-add-msg');
            if (!btn) return;

            if (typeof PublicKeyCredential === 'undefined' || !window.isSecureContext) {
                btn.disabled = true; btn.style.opacity = '.5';
                msg.textContent = 'Toto zařízení / prohlížeč passkeys nepodporuje (nebo není HTTPS).';
                msg.className = 'ml-3 text-sm text-amber-600';
                return;
            }

            btn.disabled = false; btn.style.opacity = '1';

            btn.addEventListener('click', async function () {
                if (!window.KsPasskey) { msg.textContent = 'Načítám… zkus znovu za chvíli.'; return; }
                var alias = prompt('Název zařízení (např. Můj telefon):', 'Můj telefon');
                if (alias === null) return;
                btn.disabled = true; btn.style.opacity = '.6';
                msg.textContent = 'Vyžádej si otisk / obličej…'; msg.className = 'ml-3 text-sm text-gray-500';
                try {
                    await window.KsPasskey.register(alias.trim());
                    msg.textContent = 'Přidáno ✓'; msg.className = 'ml-3 text-sm text-green-600';
                    setTimeout(function () { window.location.reload(); }, 600);
                } catch (e) {
                    msg.textContent = (e && e.name === 'NotAllowedError')
                        ? 'Zrušeno nebo vypršel čas.'
                        : ('Nepodařilo se přidat: ' + (e && e.message ? e.message : e));
                    msg.className = 'ml-3 text-sm text-red-600';
                    btn.disabled = false; btn.style.opacity = '1';
                }
            });
        });
    </script>
</x-filament-panels::page>
