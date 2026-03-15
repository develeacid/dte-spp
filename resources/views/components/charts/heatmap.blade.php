@props([
    'rows' => [],
    'columns' => [],
    'values' => [],
    'clickable' => false,
    'clickRoute' => null,
])

{{--
Heatmap D3 — Matriz de semáforos.
- $rows: array de ['id' => x, 'nombre' => 'Programa A']
- $columns: array de strings ['T1', 'T2', 'T3', 'T4']
- $values: array bidimensional [row_index][col_index] => ['valor' => 75, 'semaforo' => 'verde', 'detalle' => '...']
- $clickable: si true, click en celda navega
- $clickRoute: nombre de la ruta para generar URL (recibe row id)
--}}

<div wire:ignore
     x-data="{
        init() {
            this.$nextTick(() => this.render());
        },
        render() {
            const container = this.$refs.chart;
            const rows = @js($rows);
            const columns = @js($columns);
            const values = @js($values);
            const clickable = @js($clickable);

            if (!rows.length || !columns.length || typeof d3 === 'undefined') return;

            const cellW = 80;
            const cellH = 40;
            const labelW = 200;
            const headerH = 32;
            const gap = 3;

            const width = labelW + columns.length * (cellW + gap);
            const contentHeight = rows.length * (cellH + gap);
            const maxVisible = 15 * (cellH + gap);

            d3.select(container).selectAll('*').remove();

            const colorMap = {
                verde: '#22C55E',
                amarillo: '#EAB308',
                rojo: '#EF4444',
                gris: '#D1D5DB',
            };

            const textColorMap = {
                verde: '#064E3B',
                amarillo: '#78350F',
                rojo: '#7F1D1D',
                gris: '#6B7280',
            };

            const headerSvg = d3.select(container)
                .append('svg')
                .attr('width', width)
                .attr('height', headerH)
                .style('display', 'block');

            columns.forEach((col, j) => {
                headerSvg.append('text')
                    .attr('x', labelW + j * (cellW + gap) + cellW / 2)
                    .attr('y', headerH - 8)
                    .attr('text-anchor', 'middle')
                    .attr('fill', '#6B7280')
                    .attr('font-size', '12px')
                    .attr('font-weight', 600)
                    .text(col);
            });

            const scrollDiv = d3.select(container)
                .append('div')
                .style('max-height', maxVisible + 'px')
                .style('overflow-y', contentHeight > maxVisible ? 'auto' : 'visible');

            const svg = scrollDiv
                .append('svg')
                .attr('width', width)
                .attr('height', contentHeight);

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
                .style('max-width', '260px');

            rows.forEach((row, i) => {
                const y = i * (cellH + gap);

                svg.append('text')
                    .attr('x', labelW - 8)
                    .attr('y', y + cellH / 2 + 4)
                    .attr('text-anchor', 'end')
                    .attr('fill', '#374151')
                    .attr('font-size', '12px')
                    .text(row.nombre?.length > 28 ? row.nombre.substring(0, 28) + '...' : row.nombre);

                columns.forEach((col, j) => {
                    const cell = values[i]?.[j] ?? { valor: null, semaforo: 'gris' };
                    const x = labelW + j * (cellW + gap);
                    const semaforo = cell.semaforo ?? 'gris';

                    const rect = svg.append('rect')
                        .attr('x', x)
                        .attr('y', y)
                        .attr('width', cellW)
                        .attr('height', cellH)
                        .attr('rx', 4)
                        .attr('fill', colorMap[semaforo] || colorMap.gris)
                        .style('cursor', clickable ? 'pointer' : 'default');

                    if (cell.valor != null) {
                        svg.append('text')
                            .attr('x', x + cellW / 2)
                            .attr('y', y + cellH / 2 + 4)
                            .attr('text-anchor', 'middle')
                            .attr('fill', textColorMap[semaforo] || '#6B7280')
                            .attr('font-size', '11px')
                            .attr('font-weight', 500)
                            .style('pointer-events', 'none')
                            .text(cell.valor + '%');
                    }

                    const hitArea = svg.append('rect')
                        .attr('x', x)
                        .attr('y', y)
                        .attr('width', cellW)
                        .attr('height', cellH)
                        .attr('fill', 'transparent')
                        .style('cursor', clickable ? 'pointer' : 'default');

                    hitArea
                        .on('mouseover', () => {
                            rect.attr('opacity', 0.8);
                            let html = `<strong>${row.nombre}</strong> — ${col}`;
                            if (cell.valor != null) html += `<br>Avance: ${cell.valor}%`;
                            if (cell.detalle) html += `<br>${cell.detalle}`;
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

                    if (clickable && row.id) {
                        hitArea.on('click', () => {
                            @if($clickRoute)
                                window.location.href = @js(url('/')) + '/{{ $clickRoute }}/' + row.id;
                            @endif
                        });
                    }
                });
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
