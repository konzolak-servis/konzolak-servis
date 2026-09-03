@php
    $zaruka = $z->zaruka_mesice == 1 ? 'měsíc' : ($z->zaruka_mesice < 5 ? 'měsíce' : 'měsíců');
@endphp
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
                        @if ($z->datum_vyrizeni)
                            <div class="label">Vyřízeno</div><div class="val">{{ $z->datum_vyrizeni->format('d. m. Y') }}</div>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td style="padding-top:2mm"><div class="label">Záruka</div><div class="val">{{ $z->zaruka_mesice }} {{ $zaruka }}</div></td>
                    <td style="padding-top:2mm">
                        <div class="label">Zakázka</div><div class="val">{{ $z->cislo }}</div>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<div class="sekce">Zařízení</div>
<table class="items">
    <tr><th style="width:45%">Označení</th><th style="width:20%">Kategorie</th><th>Sériové číslo</th></tr>
    @if ($z->zarizeni)
        <tr>
            <td>{{ $z->zarizeni->oznaceni }}</td>
            <td>{{ \App\Models\Zarizeni::KATEGORIE[$z->zarizeni->kategorie] ?? ($z->zarizeni->kategorie ?: '—') }}</td>
            <td>{{ $z->zarizeni->seriove_cislo ?: '—' }}</td>
        </tr>
    @else
        <tr><td colspan="3" class="muted">Zařízení neuvedeno</td></tr>
    @endif
</table>
