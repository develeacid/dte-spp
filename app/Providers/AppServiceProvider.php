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
use App\Observers\MirNivelGeoBaseObserver;
use App\Observers\ProgramaPresupuestarioGeoBaseObserver;
use App\Observers\OdsMetaObserver;
use App\Observers\OdsObjetivoObserver;
use App\Observers\PedObserver;
use App\Observers\PndEjeObserver;
use App\Observers\PndEstrategiaObserver;
use App\Observers\PndObjetivoObserver;
use App\Observers\ProgramaDerivadoObjetivoObserver;
use App\Observers\SustentoLegalObserver;
use App\Observers\DocumentoNormativoObserver;
use App\Models\Juridico\SustentoLegalPrograma;
use App\Models\Juridico\DocumentoNormativo;
use App\Models\Mml\MirNivel;
use App\Models\ProgramaPresupuestario;
use App\Contracts\LlmServiceInterface;
use App\Services\Embeddings\EmbeddingService;
use App\Services\Embeddings\SemanticSearchService;
use App\Services\Llm\LlmService;
use Illuminate\Support\Facades\Event;
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

        $this->app->singleton(LlmServiceInterface::class, LlmService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(function ($user, $ability, $arguments = []) {
            if (! $user->hasRole('admin')) {
                return null;
            }

            // Segregación de funciones: las abilities listadas en
            // DatasetAbiertoPolicy::SEGREGATED_ABILITIES NO admiten bypass de admin
            // cuando el sujeto es un DatasetAbierto (instancia o FQCN).
            $arg = $arguments[0] ?? null;
            $sujetoEsDataset = $arg instanceof \App\Models\Transparencia\DatasetAbierto
                || (is_string($arg) && is_a($arg, \App\Models\Transparencia\DatasetAbierto::class, true));

            if ($sujetoEsDataset
                && in_array($ability, \App\Policies\Transparencia\DatasetAbiertoPolicy::SEGREGATED_ABILITIES, true)) {
                return null; // No bypass; deferir al Policy → niega por permission
            }

            return true;
        });

        Gate::policy(
            \App\Models\Transparencia\DatasetAbierto::class,
            \App\Policies\Transparencia\DatasetAbiertoPolicy::class
        );

        // Registrar listeners GeoBase
        Event::listen(
            \App\Events\GeoBase\EnrollmentStatusChanged::class,
            \App\Listeners\GeoBase\UpdateAvanceFromEnrollment::class,
        );

        Event::listen(
            \App\Events\GeoBase\SnapshotGenerated::class,
            \App\Listeners\GeoBase\StoreSnapshotHash::class,
        );

        // Registrar Observers Jurídico (siempre activos)
        SustentoLegalPrograma::observe(SustentoLegalObserver::class);
        DocumentoNormativo::observe(DocumentoNormativoObserver::class);

        // Auto-replicate MIR Componentes to GeoBase whenever the parent
        // programa has padron_geobase_activo=true.
        MirNivel::observe(MirNivelGeoBaseObserver::class);

        // Auto-replicate programa identifying fields (clave/nombre/ejercicio)
        // to GeoBase, with cascading component re-sync when clave changes.
        ProgramaPresupuestario::observe(ProgramaPresupuestarioGeoBaseObserver::class);

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
