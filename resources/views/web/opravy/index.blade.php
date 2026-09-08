@extends('web.layout')
@section('title', 'Co opravujeme – konzole a PC | ' . ($firma->nazev ?? 'Konzolák Zlín'))
@section('desc', 'Opravy PlayStation, Xbox, Nintendo, PC a notebooků a herních ovladačů ve Zlíně.')

@section('body')
<section class="hero">
    <div class="wrap hero__in">
        <h1>Co opravuji</h1>
        <p>Herní konzole všech značek a generací, ovladače, notebooky i stolní PC.
            Vyberte zařízení a uvidíte nejčastější opravy i orientační ceník.</p>
    </div>
</section>

<section class="section">
    <div class="wrap grid grid--2">
        @foreach($sluzby as $slug => $s)
            <div class="card">
                <h3>{{ $s['nadpis'] }}</h3>
                <p>{{ $s['perex'] }}</p>
                <div class="chips">
                    @foreach(array_slice($s['modely'], 0, 4) as $m)<span class="chip">{{ $m }}</span>@endforeach
                </div>
                <a class="more" href="{{ route('web.opravy.detail', $slug) }}">Nejčastější opravy a ceník</a>
            </div>
        @endforeach
    </div>
</section>

<section class="section section--tint">
    <div class="wrap center">
        <h2>Nevíte si rady se závadou?</h2>
        <p class="lead">Popište mi, co se děje – ozvu se s možnostmi a cenou.</p>
        <a class="btn btn--primary" href="{{ route('web.kontakt') }}#poptavka">Napsat o závadě</a>
    </div>
</section>
@endsection
