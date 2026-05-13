@extends('emails.layouts.main')

@section('title', 'Réinitialisation de mot de passe')

@section('styles')
<style type="text/css">
  .reset-box {
    margin: 20px 0;
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border: 1px solid #86efac;
    border-radius: 12px;
    padding: 25px;
    text-align: center;
  }

  .reset-btn {
    display: inline-block;
    background: linear-gradient(135deg, #10B981 0%, #059669 100%);
    color: #ffffff !important;
    text-decoration: none;
    padding: 14px 35px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 16px;
    margin: 15px 0;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
  }

  .reset-btn:hover {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
  }

  .reset-alert {
    margin: 18px 0 0 0;
    background: linear-gradient(135deg, #fff8e6 0%, #fff3cd 100%);
    border: 1px solid #ffeaa7;
    color: #856404;
    border-radius: 8px;
    padding: 14px 16px;
    font-size: 14px;
  }

  .reset-code-box {
    margin: 20px 0;
    background: #f1f5f9;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
  }

  .reset-code {
    font-size: 32px;
    font-weight: bold;
    color: #10B981;
    letter-spacing: 8px;
    margin: 10px 0;
    font-family: 'Courier New', monospace;
  }

  .reset-security {
    background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
    border: 1px solid #fecaca;
    border-radius: 8px;
    padding: 20px;
    margin: 25px 0;
    font-size: 14px;
    color: #991b1b;
    text-align: left;
  }

  .reset-security-title {
    margin: 0 0 10px 0;
    color: #991b1b;
    font-size: 16px;
    font-weight: 700;
  }

  .reset-list {
    margin: 0;
    padding-left: 20px;
  }

  .reset-list li {
    margin: 8px 0;
    line-height: 1.5;
  }
</style>
@endsection

@section('content')
<p class="p1">Bonjour <span class="salut">{{ $user->first_name ?? 'Utilisateur' }} {{ $user->last_name ?? '' }}</span>,
</p>

<p class="p1">Vous avez demandé la réinitialisation de votre mot de passe sur {{ $appName }}. Utilisez le code ci-dessous pour créer un nouveau mot de passe.</p>

<div class="reset-code-box">
  <p class="otp-label">🔑 Code de réinitialisation</p>
  <p class="reset-code">{{ $token }}</p>
</div>

<div class="reset-alert">
  ⏰ <strong>Important :</strong> Ce code expire dans <strong>10 minutes</strong> pour votre sécurité.
</div>

<div class="reset-security">
  <div class="reset-security-title">⚠️ Vous n'avez pas fait cette demande ?</div>
  <ul class="reset-list">
    <li>Ignorez simplement cet email, votre mot de passe restera inchangé</li>
    <li>Quelqu'un a peut-être entré votre email par erreur</li>
    <li>Si vous recevez plusieurs emails de ce type, contactez notre support</li>
    <li>Ne partagez jamais ce code avec personne</li>
  </ul>
</div>
@endsection