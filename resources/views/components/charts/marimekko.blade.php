@props([
    'data' => [],
    'height' => 350,
    'clickRoute' => null,
])

{{--
Marimekko/Mosaic D3 — Visualización de presupuesto.
- $data: array de ['id' => 1, 'nombre' => 'Programa A', 'monto_aprobado' => 45000000, 'porcentaje_ejercido' => 92, 'semaforo' => 'verde', 'detalle' => '...']
- $height: altura del chart (default 350)
- $clickRoute: prefijo de ruta para navegación por click (recibe id)
--}}

<div wire:ignore
     x-data="{
        init() {
            this.$nextTick(() => this.render());
        },
        render() {
            const container = this.$refs.chart;
            const data = @js($data);
            const chartHeight = @js($height);

            if (!data.length || typeof d3 === 'undefined') return;

            d3.select(container).selectAll('*').remove();

            const width = container.parentElement.clientWidth || 800;
            const gap = 2;
            const totalAprobado = d3.sum(data, d => d.monto_aprobado);

            if (totalAprobado === 0) return;

            const colorMap = {
                verde: '#22C55E',
                amarillo: '#EAB308',
                rojo: '#EF4444',
                gris: '#D1D5DB',
            };

            const textColorMap = {
                verde: '#FFFFFF',
                amarillo: '#78350F',
                rojo: '#FFFFFF',
                gris: '#374151',
            };

            const svg = d3.select(container)
                .append('svg')
                .attr('width', width)
                .attr('height', chartHeight);

            const tooltip = d3.select(container)
                .append('div')
                .style('position', 'absolute')
                .style('background', '#1F2937')
                .style('color', '#F9FAFB')
                .style('padding', '8px 12px')
                .style('border-radius', '6px')
                .style('font-size', '12px')
                .style('pointer-events', 'none')
                .style('opacity', 0)
                .style('z-index', 50)
                .style('max-width', '300px');

            const formatMoney = (v) => {
                if (v >= 1e9) return '$' + (v / 1e9).toFixed(1) + 'B';
                if (v >= 1e6) return '$' + (v / 1e6).toFixed(1) + 'M';
                if (v >= 1e3) return '$' + (v / 1e3).toFixed(0) + 'K';
                return '$' + v.toLocaleString('es-MX');
            };

            const formatMoneyFull = (v) => {
                return '$' + v.toLocaleString('es-MX', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
            };

            const totalGaps = (data.length - 1) * gap;
            const usableWidth = width - totalGaps;
            let x = 0;

            data.forEach((d, i) => {
                const proportion = d.monto_aprobado / totalAprobado;
                const colWidth = proportion * usableWidth;
                const pctEjercido = Math.min(100, Math.max(0, d.porcentaje_ejercido || 0));
                const colHeight = (pctEjercido / 100) * chartHeight;
                const y = chartHeight - colHeight;
                const semaforo = d.semaforo || 'gris';
                const fill = colorMap[semaforo] || colorMap.gris;
                const textFill = textColorMap[semaforo] || '#374151';

                const rect = svg.append('rect')
                    .attr('x', x)
                    .attr('y', y)
                    .attr('width', Math.max(colWidth, 1))
                    .attr('height', colHeight)
                    .attr('fill', fill)
                    .attr('rx', 4)
                    .attr('clip-path', `inset(0 0 -4px 0 round 4px)`);

                /* Rounded top corners only — overlay a rect at the bottom to square off */
                if (colHeight > 8) {
                    svg.append('rect')
                        .attr('x', x)
                        .attr('y', y + colHeight - 4)
                        .attr('width', Math.max(colWidth, 1))
                        .attr('height', 4)
                        .attr('fill', fill);
                }

                /* Labels inside column (only if wide enough) */
                if (colWidth >= 60) {
                    const cx = x + colWidth / 2;
                    const textY = y + Math.min(colHeight / 2, 40);

                    if (colHeight >= 50) {
                        svg.append('text')
                            .attr('x', cx)
                            .attr('y', textY - 8)
                            .attr('text-anchor', 'middle')
                            .attr('fill', textFill)
                            .attr('font-size', colWidth >= 100 ? '12px' : '10px')
                            .attr('font-weight', 600)
                            .style('pointer-events', 'none')
                            .text(d.nombre.length > (colWidth / 7) ? d.nombre.substring(0, Math.floor(colWidth / 7)) + '...' : d.nombre);

                        svg.append('text')
                            .attr('x', cx)
                            .attr('y', textY + 8)
                            .attr('text-anchor', 'middle')
                            .attr('fill', textFill)
                            .attr('font-size', '11px')
                            .attr('font-weight', 500)
                            .style('pointer-events', 'none')
                            .text(formatMoney(d.monto_aprobado));

                        svg.append('text')
                            .attr('x', cx)
                            .attr('y', textY + 23)
                            .attr('text-anchor', 'middle')
                            .attr('fill', textFill)
                            .attr('font-size', '10px')
                            .style('pointer-events', 'none')
                            .text(pctEjercido + '% ejercido');
                    } else if (colHeight >= 24) {
                        svg.append('text')
                            .attr('x', cx)
                            .attr('y', y + colHeight / 2 + 4)
                            .attr('text-anchor', 'middle')
                            .attr('fill', textFill)
                            .attr('font-size', '10px')
                            .attr('font-weight', 600)
                            .style('pointer-events', 'none')
                            .text(pctEjercido + '%');
                    }
                }

                /* Hit area for tooltip & click */
                const hitArea = svg.append('rect')
                    .attr('x', x)
                    .attr('y', 0)
                    .attr('width', Math.max(colWidth, 1))
                    .attr('height', chartHeight)
                    .attr('fill', 'transparent')
                    .style('cursor', @js($clickRoute) ? 'pointer' : 'default');

                hitArea
                    .on('mouseover', () => {
                        rect.attr('opacity', 0.8);
                        let html = `<strong>${d.nombre}</strong>`;
                        html += `<br>Aprobado: ${formatMoneyFull(d.monto_aprobado)}`;
                        html += `<br>Ejercido: ${pctEjercido}%`;
                        if (d.detalle) html += `<br>${d.detalle}`;
                        tooltip.html(html).style('opacity', 1);
                    })
                    .on('mousemove', (event) => {
                        const cRect = container.getBoundingClientRect();
                        tooltip
                            .style('left', (event.clientX - cRect.left + 12) + 'px')
                            .style('top', (event.clientY - cRect.top - 10) + 'px');
                    })
                    .on('mouseout', () => {
                        rect.attr('opacity', 1);
                        tooltip.style('opacity', 0);
                    });

                if (d.id) {
                    hitArea.on('click', () => {
                        @if($clickRoute)
                            window.location.href = @js(url('/')) + '/{{ $clickRoute }}/' + d.id;
                        @endif
                    });
                }

                x += colWidth + gap;
            });
        }
     }"
     x-init="init()"
     {{ $attributes->merge(['class' => 'relative']) }}>
    <div x-ref="chart"></div>
</div>

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/d3/7.8.5/d3.min.js"></script>
@endpush
