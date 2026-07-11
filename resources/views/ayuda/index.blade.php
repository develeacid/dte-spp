<x-page.container title="Ayuda" subtitle="Glosario de términos y marco normativo del PbR-SED">
    <div x-data="{ tab: 'marco' }" class="space-y-4">
        <div class="flex gap-2 border-b border-gray-200">
            <button @click="tab = 'marco'"
                :class="tab === 'marco' ? 'border-brand text-brand-dark' : 'border-transparent text-gray-500'"
                class="border-b-2 px-3 py-2 text-sm font-medium">
                Marco Normativo
            </button>
            <button @click="tab = 'glosario'"
                :class="tab === 'glosario' ? 'border-brand text-brand-dark' : 'border-transparent text-gray-500'"
                class="border-b-2 px-3 py-2 text-sm font-medium">
                Glosario
            </button>
        </div>

        {{-- Marco Normativo (M01 req 7) --}}
        <div x-show="tab === 'marco'" class="space-y-4">
            <div class="rounded-lg border border-gray-200 bg-white p-4 text-sm text-gray-700 space-y-2">
                <p>El sistema opera bajo el modelo de <strong>Presupuesto basado en Resultados (PbR)</strong> y el <strong>Sistema de Evaluación del Desempeño (SED)</strong>, sustentados en:</p>
                <ul class="list-disc pl-5 space-y-1">
                    <li><strong>LFPRH art. 111</strong> — obliga al SED y a los indicadores de desempeño del gasto federalizado.</li>
                    <li><strong>LGCG art. 46-III-C</strong> — exige la información programática con indicadores de resultados.</li>
                    <li><strong>Lineamientos SHCP-CONEVAL</strong> — metodología de la MIR y construcción de indicadores.</li>
                </ul>
            </div>

            @foreach ($ordenamientos as $jerarquia => $items)
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $jerarquia }}</h3>
                    <ul class="mt-2 space-y-1">
                        @foreach ($items as $o)
                            <li class="text-sm text-gray-800">
                                <span class="font-mono font-semibold text-gray-600">{{ $o->abreviatura }}</span>
                                — {{ $o->nombre }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>

        {{-- Glosario (M01 req 27) --}}
        <div x-show="tab === 'glosario'" x-cloak class="rounded-lg border border-gray-200 bg-white p-6">
            <div class="prose prose-sm max-w-none">
                {!! $glosarioHtml !!}
            </div>
        </div>
    </div>
</x-page.container>
