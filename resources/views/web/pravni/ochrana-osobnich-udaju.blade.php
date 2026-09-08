@extends('web.layout')
@section('title', 'Ochrana osobních údajů | ' . ($firma->nazev ?? 'Konzolák Zlín'))
@section('robots', 'noindex,follow')

@section('body')
<section class="section">
    <div class="wrap prose">
        <p style="color:#b3261e"><strong>Pracovní verze (draft).</strong> Před spuštěním webu bude
            text zkontrolován.</p>

        <h1>Zásady zpracování osobních údajů</h1>

        <h2>1. Správce</h2>
        <p>{{ $firma->nazev ?? 'Konzolák Zlín' }}@if($firma->ico), IČO {{ $firma->ico }}@endif,
            @if($firma->ulice){{ $firma->ulice }}, {{ trim(($firma->psc ?? '').' '.($firma->mesto ?? '')) }}, @endif
            @if($firma->email)e-mail {{ $firma->email }}@endif@if($firma->telefon), tel. {{ $firma->telefon }}@endif.</p>

        <h2>2. Jaké údaje zpracováváme</h2>
        <ul>
            <li>identifikační a kontaktní: jméno a příjmení, e-mail, telefon, případně adresa,</li>
            <li>údaje o zakázce: popis závady, typ a stav zařízení, výrobní číslo, cena opravy,</li>
            <li>komunikace: obsah e-mailů a zpráv, které nám zašlete.</li>
        </ul>

        <h2>3. Účel a právní základ</h2>
        <ul>
            <li>vyřízení poptávky a provedení opravy – plnění smlouvy (čl. 6 odst. 1 písm. b GDPR),</li>
            <li>vedení účetnictví a plnění daňových povinností – zákonná povinnost (čl. 6 odst. 1 písm. c),</li>
            <li>vyřízení reklamací a ochrana právních nároků – oprávněný zájem (čl. 6 odst. 1 písm. f).</li>
        </ul>

        <h2>4. Doba uchování</h2>
        <p>Údaje o zakázce a účetní doklady uchováváme po dobu stanovenou právními předpisy
            (zpravidla 10 let u daňových dokladů). Kontaktní údaje k poptávkám, které nevedly
            k zakázce, uchováváme nejvýše 12 měsíců.</p>

        <h2>5. Příjemci</h2>
        <ul>
            <li>účetní / daňový poradce,</li>
            <li>přepravce (při zaslání zařízení),</li>
            <li>poskytovatel e-mailové služby (Brevo) a poskytovatel hostingu – jako zpracovatelé.</li>
        </ul>
        <p>Údaje nepředáváme mimo EU a nepoužíváme je k automatizovanému rozhodování.</p>

        <h2>6. Vaše práva</h2>
        <p>Máte právo na přístup k údajům, jejich opravu nebo výmaz, omezení zpracování, přenositelnost
            a právo vznést námitku. Žádost zašlete na @if($firma->email){{ $firma->email }}@endif.
            Máte také právo podat stížnost u Úřadu pro ochranu osobních údajů (uoou.gov.cz).</p>

        <h2>7. Cookies</h2>
        <p>Web používá pouze technicky nezbytné cookies potřebné pro jeho fungování (relace,
            zabezpečení formulářů). Nepoužíváme analytické ani reklamní cookies. Více na stránce
            <a href="{{ route('web.pravni', 'cookies') }}">Cookies</a>.</p>
    </div>
</section>
@endsection
