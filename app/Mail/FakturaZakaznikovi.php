<?php

namespace App\Mail;

use App\Models\Faktura;
use App\Models\Firma;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FakturaZakaznikovi extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Faktura $faktura, public string $pdf)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Faktura ' . $this->faktura->cislo
                . ($this->faktura->zakazka ? ' k zakázce ' . $this->faktura->zakazka->cislo : ''),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.faktura-zakaznikovi',
            with: ['firma' => Firma::get(), 'f' => $this->faktura],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => $this->pdf, 'faktura-' . $this->faktura->cislo . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
