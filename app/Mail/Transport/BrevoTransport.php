<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\MessageConverter;

class BrevoTransport extends AbstractTransport
{
  public function __construct(private string $apiKey)
  {
    parent::__construct();
  }

  protected function doSend(SentMessage $message): void
  {
    $email = MessageConverter::toEmail($message->getOriginalMessage());

    $to = [];
    foreach ($email->getTo() as $address) {
      $to[] = [
        'email' => $address->getAddress(),
        'name'  => $address->getName() ?: $address->getAddress()
      ];
    }

    $response = Http::withHeaders([
      'api-key'      => $this->apiKey,
      'Content-Type' => 'application/json',
      'Accept'       => 'application/json',
    ])->post('https://api.brevo.com/v3/smtp/email', [
      'sender'      => [
        'email' => $email->getFrom()[0]->getAddress(),
        'name'  => $email->getFrom()[0]->getName() ?: $email->getFrom()[0]->getAddress(),
      ],
      'to'          => $to,
      'subject'     => $email->getSubject(),
      'htmlContent' => $email->getHtmlBody(),
      'textContent' => $email->getTextBody() ?: strip_tags($email->getHtmlBody() ?? ''),
    ]);

    if (!$response->successful()) {
      throw new \RuntimeException('Brevo API error: ' . $response->body());
    }
  }

  public function __toString(): string
  {
    return 'brevo';
  }
}
