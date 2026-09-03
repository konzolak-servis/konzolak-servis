<?php

namespace App\Mail;

use App\Models\Firma;
use App\Models\Zakazka;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DokladZakazky extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Zakazka $zakazka, public string $pdf)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Servisní doklad – převzetí zakázky ' . $this->zakazka->cislo,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.doklad-zakazky',
            with: ['firma' => Firma::get(), 'z' => $this->zakazka],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdf, 'servisni-doklad-' . $this->zakazka->cislo . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
