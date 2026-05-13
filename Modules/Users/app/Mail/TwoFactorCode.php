<?php

namespace Modules\Users\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Users\Models\User;

/**
 * Email contenant le code OTP pour authentification à deux facteurs
 */
class TwoFactorCode extends Mailable
{
  use Queueable, SerializesModels;

  /**
   * Create a new message instance.
   */
  public function __construct(
    public User $user,
    public string $code
  ) {}

  /**
   * Get the message envelope.
   */
  public function envelope(): Envelope
  {
    return new Envelope(
      from: new Address(
        config('mail.from.address'),
        config('mail.from.name')
      ),
      subject: 'Code de validation - ' . config('app.name'),
    );
  }

  /**
   * Get the message content definition.
   */
  public function content(): Content
  {
    return new Content(
      view: 'users::emails.two-factor-code',
      with: [
        'user' => $this->user,
        'code' => $this->code,
        'appName' => config('app.name'),
      ],
    );
  }

  /**
   * Get the attachments for the message.
   *
   * @return array<int, \Illuminate\Mail\Mailables\Attachment>
   */
  public function attachments(): array
  {
    return [];
  }
}
