<table class="cols" style="margin-bottom:1mm">
    <tr>
        <td width="52%">
            <div class="label">Zákazník</div>
            <div class="val"><strong>{{ $z->zakaznik?->nazev }}</strong></div>
            @php
                $z_kont = array_filter([
                    $z->zakaznik?->telefon ? 'tel. ' . $z->zakaznik->telefon : null,
                    $z->zakaznik?->email ?: null,
                    $z->zakaznik?->adresa_radek ?: null,
                    $z->zakaznik?->ico ? 'IČO ' . $z->zakaznik->ico : null,
                ]);
            @endphp
            <div class="muted">{!! implode('<br>', array_map('e', $z_kont)) !!}</div>
        </td>
        <td width="48%">
            <table class="cols">
                <tr>
                    <td><div class="label">Přijato</div><div class="val">{{ optional($z->datum_prijeti)->format('d. m. Y') ?: '—' }}</div></td>
                    <td>
                        @if ($z->datum_vyrizeni || ($jeProtokol ?? false))
                            <div class="label">Vyřízeno</div><div class="val">{{ ($z->datum_vyrizeni ?? now())->format('d. m. Y') }}</div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding-top:2mm"><div class="label">Zakázka</div><div class="val">{{ $z->cislo }}</div></td>
                    <td style="padding-top:2mm">
                        @if ($z->zaloha > 0)
                            <div class="label">Přijatá záloha</div><div class="val">{{ number_format($z->zaloha, 0, ',', ' ') }} Kč</div>
                        @endif
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<div class="sekce">Zařízení</div>
<table class="items">
    <tr><th style="width:45%">Označení</th><th style="width:20%">Kategorie</th><th>Sériové číslo</th></tr>
    @forelse ($z->vsechnaZarizeni() as $zar)
        <tr>
            <td>
                @if ($z->maVicZarizeni())<b>{{ $loop->iteration }}. {{ $zar->oznaceni }}</b>@else{{ $zar->oznaceni }}@endif
                @if ($zar->_zavada)<div style="font-size:8.5pt;margin-top:1mm"><b>Závada:</b> {{ $zar->_zavada }}</div>@endif
                @if ($zar->_pozadovane)<div style="font-size:8.5pt;margin-top:1mm"><b>Požadováno:</b> {{ $zar->_pozadovane }}</div>@endif
                @if ($zar->_prace)<div style="font-size:8.5pt;margin-top:1mm"><b>Provedeno:</b> {{ $zar->_prace }}</div>@endif
            </td>
            <td>{{ \App\Models\Zarizeni::KATEGORIE[$zar->kategorie] ?? ($zar->kategorie ?: '—') }}</td>
            <td>{{ $zar->seriove_cislo ?: '—' }}</td>
        </tr>
    @empty
        <tr><td colspan="3" class="muted">Zařízení neuvedeno</td></tr>
    @endforelse
</table>
