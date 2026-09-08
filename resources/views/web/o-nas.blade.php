@extends('web.layout')
@section('title', 'O servisu | ' . ($firma->nazev ?? 'Konzolák Zlín'))
@section('desc', 'Servis herních konzolí a PC ve Zlíně – osobní přístup jednoho technika.')

@section('body')
<section class="hero">
    <div class="wrap hero__in">
        <h1>O servisu</h1>
        <p>{{ $firma->nazev ?? 'Konzolák Zlín' }} – opravy herních konzolí, notebooků a počítačů ve Zlíně.</p>
    </div>
</section>

<section class="section">
    <div class="wrap prose">
        <p><em>(Sem přijde tvůj text – kdo jsi, jak dlouho to děláš, čím ses k tomu dostal,
            proč zrovna konzole, reference. Můžu pomoct s formulací, stačí body.)</em></p>

        <h2>Čemu se věnuji</h2>
        <ul>
            <li>Opravy PlayStation, Xbox a Nintendo všech generací</li>
            <li>Opravy herních ovladačů (drift páček, konektory, tlačítka)</li>
            <li>Servis notebooků a stolních PC, čištění, výměna komponent</li>
            <li>Návrh a upgrade herních i pracovních sestav</li>
        </ul>

        <h2>Jak pracuji</h2>
        <p>Zařízení projde jen mýma rukama. O ceně i postupu se domluvíme předem a opravuji
            až po tvém odsouhlasení. Na hotovou opravu dostaneš doklad, stav zakázky si můžeš
            kdykoli ověřit online.</p>

        <p><a class="btn btn--primary" href="{{ route('web.kontakt') }}#poptavka">Objednat opravu</a></p>
    </div>
</section>
@endsection
