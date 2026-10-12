@php($zk = $z->zakaznik)
<table class="grid">
    <tr>
        <td width="50%">
            <div class="label">Zákazník</div>
            <div class="val"><strong>{{ $zk?->nazev }}</strong></div>
            @if ($zk?->telefon)<div>tel.: {{ $zk->telefon }}</div>@endif
            @if ($zk?->email)<div>{{ $zk->email }}</div>@endif
            @if ($zk?->adresa_radek)<div class="muted">{{ $zk->adresa_radek }}</div>@endif
            @if ($zk?->ico)<div class="muted">IČO: {{ $zk->ico }}@if ($zk->dic) · DIČ: {{ $zk->dic }}@endif</div>@endif
        </td>
        <td width="50%">
            <div class="label">Datum přijetí</div>
            <div class="val">{{ optional($z->datum_prijeti)->format('d.m.Y') ?: '—' }}</div>
            @if ($z->datum_vyrizeni)
                <div class="label" style="margin-top:2mm">Datum vyřízení</div>
                <div class="val">{{ $z->datum_vyrizeni->format('d.m.Y') }}</div>
            @endif
            <div class="label" style="margin-top:2mm">Záruka</div>
            <div class="val">{{ $z->zaruka_mesice }}
                {{ $z->zaruka_mesice == 1 ? 'měsíc' : ($z->zaruka_mesice < 5 ? 'měsíce' : 'měsíců') }}</div>
        </td>
    </tr>
</table>

@if ($z->vsechnaZarizeni()->isNotEmpty())
    <div class="sekce-nadpis">Přijaté zařízení</div>
    <table class="items">
        <tr><th>Označení</th><th>Kategorie</th><th>Sériové číslo</th></tr>
        @foreach ($z->vsechnaZarizeni() as $zar)
            <tr>
                <td>
                    @if ($z->maVicZarizeni())<b>{{ $loop->iteration }}. {{ $zar->oznaceni }}</b>@else{{ $zar->oznaceni }}@endif
                    @if ($zar->_zavada)<div style="font-size:8.5pt;margin-top:1mm"><b>Závada:</b> {{ $zar->_zavada }}</div>@endif
                    @if ($zar->_pozadovane)<div style="font-size:8.5pt;margin-top:1mm"><b>Požadováno:</b> {{ $zar->_pozadovane }}</div>@endif
                    @if ($zar->_prace)<div style="font-size:8.5pt;margin-top:1mm"><b>Provedeno:</b> {{ $zar->_prace }}</div>@endif
                </td>
                <td>{{ \App\Models\Zarizeni::KATEGORIE[$zar->kategorie] ?? $zar->kategorie }}</td>
                <td>{{ $zar->seriove_cislo ?: '—' }}</td>
            </tr>
        @endforeach
    </table>
@endif
