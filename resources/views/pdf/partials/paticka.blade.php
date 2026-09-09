{{-- Patička dokumentu – mPDF ji vykreslí vždy u spodního okraje stránky --}}
@php
    $razitkoSoubor = public_path('images/razitko.png');
    $zobrazitRazitko = \Illuminate\Support\Str::contains($podpisL ?? '', 'razítko') && is_file($razitkoSoubor);
@endphp
<htmlpagefooter name="paticka">
    <div class="paticka">
        <table class="podpisy">
            <tr>
                <td>
                    @if ($zobrazitRazitko)
                        <img src="{{ $razitkoSoubor }}" class="razitko-otisk">
                    @endif
                    <div class="cara">{{ $podpisL ?? 'Za servis (razítko a podpis)' }}</div>
                </td>
                <td>@isset($podpisR)<div class="cara">{{ $podpisR }}</div>@endisset</td>
            </tr>
        </table>
        <div class="meta">
            {{ $firma->nazev ?: 'Konzolák Zlín' }} · {{ $doklad ?? '' }} · vytištěno {{ now()->format('d.m.Y H:i') }} · strana {PAGENO}/{nbpg}
        </div>
        @if (! empty($gdpr))
            <div class="meta">Osobní údaje zpracováváme pro účely servisu dle nařízení GDPR; podrobnosti na vyžádání.</div>
        @endif
    </div>
</htmlpagefooter>
<sethtmlpagefooter name="paticka" value="on" />
