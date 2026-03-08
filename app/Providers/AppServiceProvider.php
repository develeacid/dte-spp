<?php

namespace App\Providers;

use App\Contracts\EmbeddingServiceInterface;
use App\Models\OdsMeta;
use App\Models\OdsObjetivo;
use App\Models\PedEje;
use App\Models\PedEstrategia;
use App\Models\PedLineaAccion;
use App\Models\PedObjetivoEstrategico;
use App\Models\PedPlan;
use App\Models\PedTema;
use App\Models\PndEje;
use App\Models\PndEstrategia;
use App\Models\PndObjetivo;
use App\Models\ProgramaDerivadoObjetivo;
use App\Observers\OdsMetaObserver;
use App\Observers\OdsObjetivoObserver;
use App\Observers\PedObserver;
use App\Observers\PndEjeObserver;
use App\Observers\PndEstrategiaObserver;
use App\Observers\PndObjetivoObserver;
use App\Observers\ProgramaDerivadoObjetivoObserver;
use App\Services\Embeddings\EmbeddingService;
use App\Services\Embeddings\SemanticSearchService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(EmbeddingServiceInterface::class, function ($app) {
            return new EmbeddingService();
        });

        $this->app->singleton(SemanticSearchService::class, function ($app) {
            return new SemanticSearchService($app->make(EmbeddingServiceInterface::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('admin')) {
                return true;
            }
        });

        // Registrar Observers para embeddings
        if ($this->shouldRegisterObservers()) {
            $this->registerEmbeddingObservers();
        }
    }

    /**
     * Determina si los observers deben registrarse.
     */
    protected function shouldRegisterObservers(): bool
    {
        // No registrar en testing a menos que se solicite explícitamente
        if ($this->app->environment('testing')) {
            return config('embedding.observers_enabled', false);
        }

        return config('embedding.observers_enabled', true);
    }

    /**
     * Registra los observers para generación de embeddings.
     */
    protected function registerEmbeddingObservers(): void
    {
        // ODS
        OdsObjetivo::observe(OdsObjetivoObserver::class);
        OdsMeta::observe(OdsMetaObserver::class);

        // PND
        PndEje::observe(PndEjeObserver::class);
        PndObjetivo::observe(PndObjetivoObserver::class);
        PndEstrategia::observe(PndEstrategiaObserver::class);

        // PED (todos usan el mismo observer base)
        PedPlan::observe(PedObserver::class);
        PedEje::observe(PedObserver::class);
        PedTema::observe(PedObserver::class);
        PedObjetivoEstrategico::observe(PedObserver::class);
        PedEstrategia::observe(PedObserver::class);
        PedLineaAccion::observe(PedObserver::class);

        // Programas Derivados
        ProgramaDerivadoObjetivo::observe(ProgramaDerivadoObjetivoObserver::class);
    }
}
