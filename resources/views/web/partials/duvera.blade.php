@php
    $ikony = [
        'shield' => '<path d="M12 3l7 3v6c0 4-3 7-7 9-4-2-7-5-7-9V6l7-3z"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
        'pin' => '<path d="M12 21s-7-6-7-11a7 7 0 1114 0c0 5-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
        'tag' => '<path d="M3 12l9-9 9 9-9 9-9-9z"/><circle cx="12" cy="12" r="2"/>',
    ];
@endphp
<div class="trust">
    @foreach($duvera as $d)
        <div class="trust__i">
            <svg class="trust__ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                 stroke-linecap="round" stroke-linejoin="round">{!! $ikony[$d['ikona']] ?? '' !!}</svg>
            <b>{{ $d['titulek'] }}</b>
            <span>{{ $d['text'] }}</span>
        </div>
    @endforeach
</div>
