@extends('web.layout')
@section('title', 'Reference a recenze | ' . ($firma->nazev ?? 'Konzolák Zlín'))
@section('desc', 'Co říkají zákazníci servisu Konzolák Zlín – hodnocení a recenze.')

@section('body')
<section class="hero">
    <div class="wrap hero__in">
        <h1>Reference</h1>
        <p>Hodnocení a recenze zákazníků servisu Konzolák Zlín.</p>

        <div class="stats" style="margin-top:1.6rem">
            @foreach($zdroje as $z)
                <div>
                    <b>{{ $z['procento'] !== null ? $z['procento'] . ' %' : '—' }}</b>
                    <span>{{ $z['nazev'] }}{{ $z['procento'] !== null ? ' doporučuje' : '' }}</span>
                </div>
            @endforeach
        </div>

        <div class="hero__cta" style="margin-top:1.4rem;gap:.6rem">
            @foreach($zdroje as $z)
                <a class="btn btn--ghost btn--sm" href="{{ $z['url'] }}" target="_blank" rel="noopener">
                    {{ $z['nazev'] }}{{ $z['popis'] ? ' · ' . $z['popis'] : '' }}
                </a>
            @endforeach
        </div>
    </div>
</section>

<section class="section">
    <div class="wrap">
        <div style="display:flex;flex-wrap:wrap;gap:1.15rem;justify-content:{{ count($recenze) < 3 ? 'center' : 'flex-start' }}">
            @foreach($recenze as $r)
                @php($z = $zdroje[$r['zdroj']] ?? null)
                <div class="card" style="flex:1 1 300px;max-width:380px">
                    <p style="color:var(--gold-300);font-size:1.1rem;margin:0 0 .4rem">★★★★★</p>
                    <p><em>„{{ $r['text'] }}"</em></p>
                    <p style="color:#8a8578;margin:0">
                        — {{ $r['jmeno'] }}, {{ $r['datum'] }}
                        @if($z)<br><span style="font-size:.82rem">přes {{ $z['nazev'] }}</span>@endif
                    </p>
                </div>
            @endforeach
        </div>
        <p style="text-align:center;margin-top:2rem">
            <a href="{{ $facebookUrl }}/reviews" target="_blank" rel="noopener"
                style="color:var(--gold-300);font-weight:600;text-decoration:none;font-size:.92rem">
                Zobrazit všechny recenze na Facebooku →
            </a>
        </p>
    </div>
</section>
@endsection
