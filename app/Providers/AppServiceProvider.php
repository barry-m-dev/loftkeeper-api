<?php

namespace App\Providers;

use App\Mail\Transport\BrevoTransport;
use App\Policies\CagePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use Modules\Cages\Models\Cage;
use Modules\Users\Models\PersonalAccessToken;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Forcer HTTPS en production
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // Utiliser notre modèle PersonalAccessToken personnalisé avec UUID
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        // Enregistrer les policies
        Gate::policy(Cage::class, CagePolicy::class);

        // Enregistrer le transport Brevo
        Mail::extend('brevo', function () {
            return new BrevoTransport(config('services.brevo.key'));
        });
    }
}
