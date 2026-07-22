<?php

namespace App\Providers;

use App\Contracts\AIParserServiceInterface;
use App\Services\AI\ClaudeAIParserService;
use App\Services\AI\FakeAIParserService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * Vincula AIParserServiceInterface a la implementación correcta
     * según la variable de entorno AI_PROVIDER:
     *   - 'fake'   → FakeAIParserService   (desarrollo / tests)
     *   - 'claude' → ClaudeAIParserService (producción con API real)
     *
     * Para alternar entre proveedores solo hay que cambiar AI_PROVIDER en .env.
     */
    public function register(): void
    {
        $this->app->singleton(AIParserServiceInterface::class, function ($app) {
            $provider = config('ai.provider', 'fake');

            return match ($provider) {
                'claude' => new ClaudeAIParserService(
                    apiKey: config('ai.api_key', ''),
                    model:  config('ai.model', 'claude-3-5-sonnet-20241022'),
                ),
                default  => new FakeAIParserService(),
            };
        });
    }

    /**
     * Bootstrap any application services.
     *
     * Registra el morph map para que los tipos polimórficos se guarden
     * con nombres cortos en BD ('compound', 'entity') en lugar del FQCN.
     * Esto es consistente con los CHECKs definidos en las migraciones.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'compound' => \App\Models\Compound::class,
            'entity'   => \App\Models\Entity::class,
        ]);
    }
}
