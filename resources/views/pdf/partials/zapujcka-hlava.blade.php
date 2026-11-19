{{-- Zákazník + údaje zápůjčky (předávací protokol / vrácení) --}}
@php($zk = $z->zakaznik)
<table class="cols" style="margin-bottom:2mm">
    <tr>
        <td width="50%">
            <div class="label">Vypůjčitel</div>
            <div class="val"><strong>{{ $zk?->nazev }}</strong></div>
            @if ($zk?->telefon)<div>tel.: {{ $zk->telefon }}</div>@endif
            @if ($zk?->email)<div>{{ $zk->email }}</div>@endif
            @if ($zk?->adresa_radek)<div class="muted">{{ $zk->adresa_radek }}</div>@endif
            @if ($zk?->ico)<div class="muted">IČO: {{ $zk->ico }}</div>@endif
        </td>
        <td width="25%">
            <div class="label">Zapůjčeno dne</div>
            <div class="val">{{ $z->datum_zapujceni?->format('d.m.Y') }}</div>
            <div class="label" style="margin-top:2mm">Vrátit do</div>
            <div class="val">{{ $z->vratit_do?->format('d.m.Y') ?: 'dle dohody' }}</div>
        </td>
        <td width="25%">
            <div class="label">Kauce</div>
            <div class="val"><strong>{{ number_format((float) $z->kauce, 0, ',', ' ') }} Kč</strong></div>
            @if ($z->zakazka)
                <div class="label" style="margin-top:2mm">K zakázce</div>
                <div class="val">{{ $z->zakazka->cislo }}</div>
            @endif
        </td>
    </tr>
</table>
