{{-- Patička dokumentu – mPDF ji vykreslí vždy u spodního okraje stránky --}}
@php
    // Hlavní firemní razítko + podpis nad levým (firemním) podpisovým řádkem na všech dokladech.
    $razitkoSoubor = public_path('images/razitko.png');
    $podpisSoubor  = public_path('images/podpis.png');
    $zobrazitRazitko = ! empty($podpisL) && is_file($razitkoSoubor);
@endphp
<htmlpagefooter name="paticka">
    <div class="paticka">
        <table class="podpisy">
            <tr>
                <td>
                    @if ($zobrazitRazitko)
                        <div class="razitko-wrap">
                            <img src="{{ $razitkoSoubor }}" class="razitko-otisk">
                            @if (is_file($podpisSoubor))
                                <img src="{{ $podpisSoubor }}" class="razitko-podpis">
                            @endif
                        </div>
                        <div class="cara">&nbsp;</div>
                    @else
                        <div class="cara">{{ $podpisL ?? 'Za servis (razítko a podpis)' }}</div>
                    @endif
                </td>
                <td class="podpis-zakaznik">@isset($podpisR)<div class="cara">{!! $podpisR !!}</div>@endisset</td>
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
