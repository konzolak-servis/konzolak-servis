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

    /**
     * @param  array<string, string>  $dalsiPrilohy  název souboru => obsah PDF (např. servisní protokol)
     */
    public function __construct(public Faktura $faktura, public string $pdf, public array $dalsiPrilohy = [])
    {
    }

    public function envelope(): Envelope
    {
        $sProtokolem = $this->dalsiPrilohy !== [];

        return new Envelope(
            subject: 'Faktura ' . $this->faktura->cislo
                . ($sProtokolem ? ' a servisní protokol' : '')
                . ($this->faktura->zakazka ? ' k zakázce ' . $this->faktura->zakazka->cislo : ''),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.faktura-zakaznikovi',
            with: ['firma' => Firma::get(), 'f' => $this->faktura, 'sProtokolem' => $this->dalsiPrilohy !== []],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $prilohy = [
            Attachment::fromData(fn () => $this->pdf, 'faktura-' . $this->faktura->cislo . '.pdf')
                ->withMime('application/pdf'),
        ];

        foreach ($this->dalsiPrilohy as $nazev => $obsah) {
            $prilohy[] = Attachment::fromData(fn () => $obsah, $nazev)->withMime('application/pdf');
        }

        return $prilohy;
    }
}
