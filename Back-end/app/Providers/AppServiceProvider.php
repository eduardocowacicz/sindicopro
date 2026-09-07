<?php

namespace App\Providers;

use App\Models\Usuario;
use App\Services\Autorizacao\PermissaoService;
use App\Sessions\ManipuladorSessaoBanco;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Session::extend('sindicopro_database', function ($app): ManipuladorSessaoBanco {
            return new ManipuladorSessaoBanco(
                $app->make('db')->connection(config('session.connection')),
                (string) config('session.table'),
                (int) config('session.lifetime'),
                $app,
            );
        });

        RateLimiter::for('login', function (Request $request): Limit {
            $email = mb_strtolower(trim((string) $request->input('email')));

            return Limit::perMinute(5)->by(hash('sha256', $email.'|'.$request->ip()));
        });

        Gate::before(function (Usuario $usuario): ?bool {
            return app(PermissaoService::class)->possuiAcessoIntegral($usuario) ? true : null;
        });
    }
}
