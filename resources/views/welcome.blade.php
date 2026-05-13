<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>API LoftKeeper</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #0a0a0a;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      overflow: hidden;
    }

    .container {
      text-align: center;
      animation: fadeIn 0.6s ease-in-out;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .logo {
      width: 80px;
      height: 80px;
      background: linear-gradient(to right, #10b981, #8b5cf6);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
      box-shadow: 0 8px 24px rgba(16, 185, 129, 0.3);
    }

    .logo-text {
      font-size: 2rem;
      font-weight: 700;
      color: white;
      letter-spacing: -1px;
    }

    h1 {
      font-size: 1.5rem;
      color: #ffffff;
      margin-bottom: 8px;
      font-weight: 600;
    }

    .subtitle {
      font-size: 0.875rem;
      color: #9ca3af;
      margin-bottom: 30px;
    }

    .info-grid {
      display: flex;
      gap: 16px;
      justify-content: center;
      margin-bottom: 24px;
    }

    .info-card {
      background: #1c1c1c;
      padding: 12px 20px;
      border-radius: 8px;
      border: 1px solid #333333;
      min-width: 100px;
    }

    .info-card .label {
      font-size: 0.7rem;
      color: #6b7280;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 4px;
    }

    .info-card .value {
      font-size: 0.875rem;
      color: #ffffff;
      font-weight: 600;
    }

    .badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: #1c1c1c;
      color: #10b981;
      padding: 6px 16px;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 600;
      border: 1px solid #10b981;
    }

    .badge::before {
      content: '';
      width: 6px;
      height: 6px;
      background: #10b981;
      border-radius: 50%;
      animation: pulse 2s infinite;
    }

    @keyframes pulse {

      0%,
      100% {
        opacity: 1;
      }

      50% {
        opacity: 0.5;
      }
    }

    .footer {
      margin-top: 30px;
      color: #6b7280;
      font-size: 0.75rem;
    }

    @media (max-width: 640px) {
      .logo {
        width: 64px;
        height: 64px;
      }

      .logo-text {
        font-size: 1.5rem;
      }

      h1 {
        font-size: 1.25rem;
      }

      .info-grid {
        flex-direction: column;
        gap: 8px;
      }

      .info-card {
        min-width: 200px;
      }
    }
  </style>
</head>

<body>
  <div class="container">
    <div class="logo">
      <span class="logo-text">LK</span>
    </div>

    <h1>API LoftKeeper</h1>
    <p class="subtitle">Gestion de volière colombophile</p>

    <div class="info-grid">
      <div class="info-card">
        <div class="label">Laravel</div>
        <div class="value">{{ app()->version() }}</div>
      </div>
      <div class="info-card">
        <div class="label">PHP</div>
        <div class="value">{{ PHP_VERSION }}</div>
      </div>
      <div class="info-card">
        <div class="label">Env</div>
        <div class="value">{{ config('app.env') }}</div>
      </div>
    </div>

    <div class="badge">API Opérationnelle</div>

    <div class="footer">
      © {{ date('Y') }} LoftKeeper
    </div>
  </div>
</body>

</html>