<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="x-apple-disable-message-reformatting">
  <meta name="format-detection" content="telephone=no,address=no,email=no,date=no,url=no">
  <title>@yield('title', config('app.name'))</title>
  <style>
    @include('emails.partials.inline-styles')
  </style>
  @yield('styles')
</head>

<body>
  <div class="container">
    <div class="cadre">
      <div class="header">
        <div class="entete">
          <h1 class="app-name">{{ config('app.name') }}</h1>
        </div>
      </div>

      <div class="msgContainer">
        <h2 class="titre">@yield('title', 'Notification')</h2>
        @yield('content')
        <p class="origin">Cordialement,<br>L'équipe {{ config('app.name') }}</p>
      </div>

      <div class="footer">
        <div class="contact">
          <p><strong>{{ config('app.name') }}</strong> - Gestion de volière pour colombophiles</p>
          <p>
            <a href="mailto:{{ config('mail.from.address') }}">{{ config('mail.from.address') }}</a>
            <br>
            <small>Cet email a été envoyé automatiquement. Merci de ne pas y répondre.</small>
          </p>
        </div>
      </div>
    </div>
  </div>
</body>

</html>