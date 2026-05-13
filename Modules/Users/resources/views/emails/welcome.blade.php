@extends('emails.layouts.main')

@section('title', 'Bienvenue sur ' . config('app.name'))

@section('styles')
<style type="text/css">
  .welcome-box {
    margin: 30px 0;
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border: 2px solid #10B981;
    border-radius: 12px;
    padding: 25px;
    text-align: center;
  }

  .welcome-icon {
    font-size: 48px;
    margin-bottom: 15px;
  }

  .welcome-title {
    color: #10B981;
    font-size: 24px;
    font-weight: 700;
    margin: 10px 0;
  }

  .welcome-subtitle {
    color: #6b7280;
    font-size: 15px;
    margin: 10px 0;
  }

  .info-box {
    margin: 25px 0;
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 20px;
    text-align: left;
  }

  .info-box h4 {
    color: #8B5CF6;
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 15px 0;
  }

  .info-list {
    margin: 0;
    padding-left: 0;
    list-style: none;
  }

  .info-list li {
    padding: 10px 0;
    padding-left: 28px;
    position: relative;
    color: #475569;
    border-bottom: 1px solid #f1f5f9;
  }

  .info-list li:last-child {
    border-bottom: none;
  }

  .info-list li::before {
    content: "✓";
    position: absolute;
    left: 0;
    color: #10B981;
    font-weight: bold;
    font-size: 18px;
  }

  .btn-container {
    text-align: center;
    margin: 30px 0 20px 0;
  }

  .btn-login {
    display: inline-block;
    background: linear-gradient(135deg, #10B981 0%, #059669 100%);
    color: #ffffff !important;
    text-decoration: none;
    padding: 16px 40px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 16px;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
  }

  .security-note {
    background: linear-gradient(135deg, #f3e8ff 0%, #e9d5ff 100%);
    border: 1px solid #c084fc;
    border-radius: 8px;
    padding: 16px 20px;
    margin: 25px 0;
    font-size: 14px;
    color: #6b21a8;
  }

  .security-note strong {
    color: #581c87;
  }
</style>
@endsection

@section('content')
<div class="welcome-box">
  <div class="welcome-icon">🎉</div>
  <h3 class="welcome-title">Bienvenue {{ $user->first_name }} !</h3>
  <p class="welcome-subtitle">Votre compte a été créé avec succès</p>
</div>

<p class="p1">
  Nous sommes ravis de vous accueillir sur <strong style="color: #10B981;">{{ $appName }}</strong>.
  Votre inscription a été confirmée et votre compte est maintenant actif. Vous pouvez dès maintenant vous connecter et commencer à gérer vos pigeons.
</p>

<div class="info-box">
  <h4>🐦 Ce que vous pouvez faire avec {{ $appName }}</h4>
  <ul class="info-list">
    <li>Gérer vos pigeons (ajout, suivi, historique complet)</li>
    <li>Créer et organiser vos cases et colombiers</li>
    <li>Suivre les performances de vos pigeons en compétition</li>
    <li>Gérer les accouplements et la reproduction</li>
    <li>Consulter l'historique des vols et des résultats</li>
    <li>Recevoir des notifications importantes sur vos pigeons</li>
  </ul>
</div>

<div class="btn-container">
  <a href="{{ config('app.frontend_url', 'http://localhost:5173') }}/login" class="btn-login">
    🚀 Se connecter maintenant
  </a>
</div>

<p class="p1" style="text-align: center; color: #6b7280; font-size: 13px; margin-top: 20px;">
  Besoin d'aide ? Contactez notre support à <a href="mailto:{{ config('mail.from.address') }}" style="color: #10B981;">{{ config('mail.from.address') }}</a>
</p>
@endsection