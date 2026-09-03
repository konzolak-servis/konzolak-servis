<x-mail-layout :firma="$firma">
    @foreach (preg_split('/\R/', trim($telo)) as $radek)
        @if (trim($radek) === '')
            <p style="margin:0 0 12px;">&nbsp;</p>
        @else
            <p style="margin:0 0 10px;">{{ $radek }}</p>
        @endif
    @endforeach

    @if (! empty($podpis))
        <div style="margin-top:22px; padding-top:14px; border-top:1px solid #eceae3; color:#5f5a4e; font-size:13px;">
            @foreach (preg_split('/\R/', trim($podpis)) as $radek)
                {{ $radek }}<br>
            @endforeach
        </div>
    @endif
</x-mail-layout>
