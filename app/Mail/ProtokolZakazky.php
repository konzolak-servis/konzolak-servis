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

class ProtokolZakazky extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, string>  $prilohy  název souboru => binární obsah PDF
     */
    public function __construct(public Zakazka $zakazka, public array $prilohy)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Servisní protokol k zakázce ' . $this->zakazka->cislo,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.protokol-zakazky',
            with: ['firma' => Firma::get(), 'z' => $this->zakazka],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return collect($this->prilohy)
            ->map(fn (string $obsah, string $nazev) => Attachment::fromData(fn () => $obsah, $nazev)
                ->withMime('application/pdf'))
            ->values()
            ->all();
    }
}
