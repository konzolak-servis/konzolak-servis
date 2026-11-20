<?php

namespace App\Mail;

use App\Models\Firma;
use App\Models\Zapujcka;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Dokumenty zápůjčky zákazníkovi – odesílá se jen ručně tlačítkem „Poslat e-mailem". */
class ZapujckaDokumenty extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string, string>  $prilohy  název souboru => obsah PDF
     */
    public function __construct(public Zapujcka $zapujcka, public array $prilohy)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: ($this->zapujcka->stav === 'vraceno' ? 'Vrácení zápůjčky ' : 'Zápůjčka ') . $this->zapujcka->cislo,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.zapujcka-dokumenty',
            with: ['firma' => Firma::get(), 'z' => $this->zapujcka],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return collect($this->prilohy)
            ->map(fn (string $obsah, string $nazev) => Attachment::fromData(fn () => $obsah, $nazev)->withMime('application/pdf'))
            ->values()
            ->all();
    }
}
