<?php

declare(strict_types=1);

namespace Modules\Users\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Users\Models\User;

/**
 * Email de réinitialisation de mot de passe
 */
class PasswordResetMail extends Mailable
{
  use Queueable, SerializesModels;

  public function __construct(
    public readonly User $user,
    public readonly string $token
  ) {}

  public function envelope(): Envelope
  {
    return new Envelope(
      from: new Address(config('mail.from.address'), config('mail.from.name')),
      subject: 'Réinitialisation de votre mot de passe - ' . config('app.name'),
    );
  }

  public function content(): Content
  {
    $frontendUrl = config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:5173'));
    $resetUrl = "{$frontendUrl}/reset-password?token={$this->token}&email=" . urlencode($this->user->email);

    return new Content(
      view: 'users::emails.password-reset',
      with: [
        'user' => $this->user,
        'resetUrl' => $resetUrl,
        'token' => $this->token,
        'appName' => config('app.name'),
      ],
    );
  }
}
