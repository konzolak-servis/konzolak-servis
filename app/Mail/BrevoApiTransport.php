<?php

namespace App\Mail;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\MessageConverter;

/**
 * Laravel mail transport, který posílá přes Brevo API (v3/smtp/email).
 * Používá stejný BREVO_API_KEY jako App\Support\Posta – jedno API, žádné SMTP.
 */
class BrevoApiTransport extends AbstractTransport
{
    public function __construct(private string $apiKey)
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        $from = $email->getFrom()[0] ?? new Address(config('mail.from.address'), (string) config('mail.from.name'));

        $payload = [
            'sender' => ['email' => $from->getAddress(), 'name' => $from->getName() ?: null],
            'to' => $this->addresses($email->getTo()),
            'subject' => $email->getSubject() ?? '',
        ];

        if ($cc = $this->addresses($email->getCc())) {
            $payload['cc'] = $cc;
        }
        if ($bcc = $this->addresses($email->getBcc())) {
            $payload['bcc'] = $bcc;
        }
        if ($reply = $email->getReplyTo()[0] ?? null) {
            $payload['replyTo'] = ['email' => $reply->getAddress()];
        }
        if ($html = $email->getHtmlBody()) {
            $payload['htmlContent'] = is_string($html) ? $html : (string) $html;
        }
        if ($text = $email->getTextBody()) {
            $payload['textContent'] = is_string($text) ? $text : (string) $text;
        }
        if (empty($payload['htmlContent']) && empty($payload['textContent'])) {
            $payload['textContent'] = ' ';
        }

        $attachments = [];
        foreach ($email->getAttachments() as $part) {
            $name = method_exists($part, 'getFilename') ? $part->getFilename() : null;
            $attachments[] = [
                'name' => $name ?: 'priloha',
                'content' => base64_encode($part->getBody()),
            ];
        }
        if ($attachments) {
            $payload['attachment'] = $attachments;
        }

        $response = Http::withHeaders([
            'api-key' => $this->apiKey,
            'accept' => 'application/json',
        ])->post('https://api.brevo.com/v3/smtp/email', $payload);

        if (! $response->successful()) {
            throw new \RuntimeException('Brevo API: HTTP ' . $response->status() . ' – ' . $response->body());
        }
    }

    /** @return array<int, array{email:string, name?:string}> */
    private function addresses(array $addresses): array
    {
        return array_map(function (Address $a) {
            $row = ['email' => $a->getAddress()];
            if ($a->getName()) {
                $row['name'] = $a->getName();
            }

            return $row;
        }, $addresses);
    }

    public function __toString(): string
    {
        return 'brevo-api';
    }
}
