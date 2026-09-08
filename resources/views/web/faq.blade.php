@extends('web.layout')
@section('title', 'Časté dotazy | ' . ($firma->nazev ?? 'Konzolák Zlín'))
@section('desc', 'Odpovědi na časté dotazy k opravám herních konzolí a PC – cena, záruka, doba opravy, zaslání poštou.')

@section('body')
<section class="hero">
    <div class="wrap hero__in">
        <h1>Časté dotazy</h1>
        <p>Nenašli jste odpověď? <a href="{{ route('web.kontakt') }}#poptavka" style="color:#E8C77C">Napište mi</a>.</p>
    </div>
</section>

<section class="section">
    <div class="wrap faq" style="max-width:760px">
        @foreach($faq as $item)
            <details @if($loop->first) open @endif>
                <summary>{{ $item['q'] }}</summary>
                <p style="margin:0">{{ $item['a'] }}</p>
            </details>
        @endforeach
    </div>
</section>
@endsection
