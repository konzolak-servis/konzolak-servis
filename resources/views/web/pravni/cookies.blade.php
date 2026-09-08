@extends('web.layout')
@section('title', 'Cookies | ' . ($firma->nazev ?? 'Konzolák Zlín'))
@section('robots', 'noindex,follow')

@section('body')
<section class="section">
    <div class="wrap prose">
        <p style="color:#b3261e"><strong>Pracovní verze (draft).</strong></p>

        <h1>Informace o cookies</h1>

        <p>Tento web používá pouze <strong>technicky nezbytné cookies</strong>, které jsou potřeba
            pro jeho základní fungování:</p>
        <ul>
            <li>udržení relace při odesílání formulářů,</li>
            <li>ochrana formulářů proti zneužití (CSRF token).</li>
        </ul>
        <p>Tyto cookies nevyžadují váš souhlas a nelze je vypnout bez omezení funkčnosti webu.
            Web <strong>nepoužívá</strong> analytické, marketingové ani cookies třetích stran a
            nesleduje vaše chování napříč weby.</p>
        <p>Pokud v budoucnu přidáme např. mapu, vložený obsah ze sociálních sítí nebo měření
            návštěvnosti, zobrazí se lišta pro udělení souhlasu a tato stránka bude doplněna.</p>
    </div>
</section>
@endsection
