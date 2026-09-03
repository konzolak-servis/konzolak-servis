<?php

namespace App\Mail;

use App\Models\Firma;
use App\Models\Zakazka;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ZakazkaHotova extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Zakazka $zakazka)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Vaše zakázka ' . $this->zakazka->cislo . ' je hotová a připravená k vyzvednutí',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.zakazka-hotova',
            with: ['firma' => Firma::get(), 'z' => $this->zakazka],
        );
    }
}
