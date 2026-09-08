@php $vyzvednuti = trim((string) ($firma->email_vyzvednuti ?? '')); @endphp
@if($vyzvednuti)
    <p style="white-space:pre-line;margin:0;color:#3b4658">{{ $vyzvednuti }}</p>
@else
    <p style="margin:0;color:#3b4658">
        @if($firma->ulice){{ $firma->ulice }}, {{ trim(($firma->psc ?? '') . ' ' . ($firma->mesto ?? '')) }}<br>@endif
        Volejte prosím vždy předem.
    </p>
@endif
