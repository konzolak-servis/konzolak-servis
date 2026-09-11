@extends('web.layout')
@section('title', 'Reference a recenze | ' . ($firma->nazev ?? 'Konzolák Zlín'))
@section('desc', 'Co říkají zákazníci servisu Konzolák Zlín – hodnocení a recenze z Facebooku.')

@section('body')
<section class="hero">
    <div class="wrap hero__in">
        <h1>Reference</h1>
        <p>Hodnocení a recenze zákazníků servisu Konzolák Zlín z Facebooku.</p>
        <div class="stats" style="margin-top:1.6rem">
            <div><b>{{ $recenze['procento'] }} %</b><span>doporučuje na Facebooku</span></div>
            <div><b>{{ $recenze['pocet'] }}</b><span>{{ $recenze['pocet'] == 1 ? 'recenze' : ($recenze['pocet'] < 5 ? 'recenze' : 'recenzí') }}</span></div>
            <div><b>{{ $recenze['sledujici'] }}</b><span>sledujících na Facebooku</span></div>
        </div>
        <div class="hero__cta" style="margin-top:1.6rem">
            <a class="btn btn--primary" href="{{ $facebookUrl }}/reviews" target="_blank" rel="noopener">Všechny recenze na Facebooku</a>
            <a class="btn btn--ghost" href="{{ $facebookUrl }}" target="_blank" rel="noopener">Sledovat na Facebooku</a>
        </div>
    </div>
</section>

<section class="section">
    <div class="wrap grid grid--3">
        @foreach($recenze['seznam'] as $r)
            <div class="card">
                <p style="color:var(--gold-300);font-size:1.1rem;margin:0 0 .4rem">★★★★★</p>
                <p><em>„{{ $r['text'] }}"</em></p>
                <p style="color:#8a8578;margin:0">— {{ $r['jmeno'] }}, {{ $r['datum'] }}</p>
            </div>
        @endforeach
        <a class="card card--link" href="{{ $facebookUrl }}/reviews" target="_blank" rel="noopener"
            style="text-decoration:none;color:inherit;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;gap:.5rem">
            <span style="font-size:1.6rem">→</span>
            <span>Číst další recenze<br>na Facebooku</span>
        </a>
    </div>
</section>
@endsection
