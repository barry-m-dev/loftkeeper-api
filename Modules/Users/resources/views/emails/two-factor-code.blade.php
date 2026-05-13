@extends('emails.layouts.main')

@section('title', 'Code de validation')

@section('styles')
<style type="text/css">
  .otp-box {
    margin: 20px 0 15px 0;
    border: 2px dashed #10B981;
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border-radius: 12px;
    padding: 25px;
    text-align: center;
  }

  .otp-label {
    font-size: 13px;
    letter-spacing: 0.8px;
    color: #6b7280;
    text-transform: uppercase;
    margin: 0 0 8px 0;
    font-weight: 600;
  }

  .otp-code {
    font-size: 38px;
    font-weight: bold;
    color: #10B981;
    letter-spacing: 10px;
    margin: 12px 0;
    font-family: 'Courier New', monospace;
  }

  .otp-alert {
    margin: 18px 0 0 0;
    background: linear-gradient(135deg, #fff8e6 0%, #fff3cd 100%);
    border: 1px solid #ffeaa7;
    color: #856404;
    border-radius: 8px;
    padding: 14px 16px;
    font-size: 14px;
  }

  .otp-security {
    background: linear-gradient(135deg, #f3e8ff 0%, #e9d5ff 100%);
    border: 1px solid #c084fc;
    border-radius: 8px;
    padding: 20px;
    margin: 25px 0;
    font-size: 14px;
    color: #6b21a8;
    text-align: left;
  }

  .otp-security-title {
    margin: 0 0 10px 0;
    color: #6b21a8;
    font-size: 16px;
    font-weight: 700;
  }

  .otp-list {
    margin: 0;
    padding-left: 20px;
  }

  .otp-list li {
    margin: 8px 0;
    line-height: 1.5;
  }
</style>
@endsection

@section('content')
<p class="p1">Bonjour <span class="salut">{{ $user->first_name ?? 'Utilisateur' }} {{ $user->last_name ?? '' }}</span>,
</p>

<p class="p1">Voici votre code de validation pour accéder à votre compte {{ $appName }}.</p>

<div class="otp-box">
  <p class="otp-label">🔐 Votre code de validation</p>
  <p class="otp-code">{{ $code }}</p>
</div>

<div class="otp-alert">
  ⏰ <strong>Important :</strong> Ce code expire dans <strong>10 minutes</strong> pour votre sécurité.
</div>

<div class="otp-security">
  <div class="otp-security-title">🛡️ Conseils de sécurité</div>
  <ul class="otp-list">
    <li>Ne partagez jamais ce code avec personne</li>
    <li>Notre équipe ne vous demandera jamais ce code par téléphone ou email</li>
    <li>Si vous n'avez pas demandé ce code, veuillez ignorer cet email</li>
    <li>En cas de doute, contactez notre support technique</li>
  </ul>
</div>
@endsection