@props([
    'data' => [],
    'height' => 400,
    'threshold' => -20,
])

{{--
Lollipop Chart D3 — Desviación respecto a meta de gasto.
Cada item en $data:
  - nombre: string (e.g., 'Programa A')
  - desviacion: number (porcentaje, e.g., -23)
  - programado: number (monto programado)
  - ejercido: number (monto ejercido)
--}}

@php $uid = 'lollipop-' . Str::random(8); @endphp

<div wire:ignore
     x-data="{ init() { this.$nextTick(() => this.render()); },
        render() {
            const container = this.$refs.chart;
            const cfg = JSON.parse(document.getElementById('{{ $uid }}').textContent);
            const rawData = cfg.data;
            const threshold = cfg.threshold;
            const chartHeight = cfg.height;

            if (!rawData.length || typeof d3 === 'undefined') return;

            d3.select(container).selectAll('*').remove();

            const data = [...rawData].sort((a, b) => Math.abs(b.desviacion) - Math.abs(a.desviacion));

            const margin = { top: 20, right: 60, bottom: 30, left: 200 };
            const width = (container.clientWidth || 600) - margin.left - margin.right;
            const rowH = 32;
            const computedHeight = Math.max(chartHeight - margin.top - margin.bottom, data.length * rowH);

            const svg = d3.select(container)
                .append('svg')
                .attr('width', width + margin.left + margin.right)
                .attr('height', computedHeight + margin.top + margin.bottom)
                .append('g')
                .attr('transform', 'translate(' + margin.left + ',' + margin.top + ')');

            const maxAbs = d3.max(data, d => Math.abs(d.desviacion)) || 30;
            const domainMax = Math.max(maxAbs * 1.2, Math.abs(threshold) * 1.3);

            const x = d3.scaleLinear()
                .domain([-domainMax, domainMax])
                .range([0, width]);

            const y = d3.scaleBand()
                .domain(data.map(d => d.nombre))
                .range([0, computedHeight])
                .padding(0.3);

            svg.append('g')
                .attr('transform', 'translate(0,' + computedHeight + ')')
                .call(d3.axisBottom(x).ticks(7).tickFormat(v => v + '%'))
                .selectAll('text')
                .style('font-size', '11px');

            svg.append('g')
                .call(d3.axisLeft(y))
                .selectAll('text')
                .style('font-size', '11px')
                .each(function() {
                    const text = d3.select(this);
                    const label = text.text();
                    if (label.length > 28) {
                        text.text(label.substring(0, 28) + '...');
                    }
                });

            svg.append('line')
                .attr('x1', x(0))
                .attr('x2', x(0))
                .attr('y1', 0)
                .attr('y2', computedHeight)
                .attr('stroke', '#9CA3AF')
                .attr('stroke-width', 1);

            svg.append('line')
                .attr('x1', x(threshold))
                .attr('x2', x(threshold))
                .attr('y1', 0)
                .attr('y2', computedHeight)
                .attr('stroke', '#EF4444')
                .attr('stroke-width', 1.5)
                .attr('stroke-dasharray', '6,4');

            svg.append('text')
                .attr('x', x(threshold))
                .attr('y', -6)
                .attr('text-anchor', 'middle')
                .attr('fill', '#EF4444')
                .attr('font-size', '10px')
                .attr('font-weight', 600)
                .text('Alerta ' + threshold + '%');

            const formatMoney = (v) => {
                if (v >= 1e6) return '$' + (v / 1e6).toFixed(2) + 'M';
                if (v >= 1e3) return '$' + (v / 1e3).toFixed(0) + 'K';
                return '$' + new Intl.NumberFormat().format(v);
            };

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
                .style('max-width', '280px');

            data.forEach(d => {
                const cy = y(d.nombre) + y.bandwidth() / 2;
                const color = d.desviacion < 0 ? '#EF4444' : '#22C55E';

                svg.append('line')
                    .attr('x1', x(0))
                    .attr('x2', x(d.desviacion))
                    .attr('y1', cy)
                    .attr('y2', cy)
                    .attr('stroke', color)
                    .attr('stroke-width', 1.5);

                const circle = svg.append('circle')
                    .attr('cx', x(d.desviacion))
                    .attr('cy', cy)
                    .attr('r', 6)
                    .attr('fill', color)
                    .style('cursor', 'pointer');

                const sign = d.desviacion > 0 ? '+' : '';
                const labelX = d.desviacion >= 0 ? x(d.desviacion) + 14 : x(d.desviacion) - 14;
                const anchor = d.desviacion >= 0 ? 'start' : 'end';

                svg.append('text')
                    .attr('x', labelX)
                    .attr('y', cy + 4)
                    .attr('text-anchor', anchor)
                    .attr('fill', '#374151')
                    .attr('font-size', '11px')
                    .attr('font-weight', 500)
                    .text(sign + d.desviacion + '%');

                const hitArea = svg.append('rect')
                    .attr('x', Math.min(x(0), x(d.desviacion)) - 6)
                    .attr('y', cy - y.bandwidth() / 2)
                    .attr('width', Math.abs(x(d.desviacion) - x(0)) + 12)
                    .attr('height', y.bandwidth())
                    .attr('fill', 'transparent')
                    .style('cursor', 'pointer');

                hitArea
                    .on('mouseover', () => {
                        circle.attr('r', 8);
                        const diff = Math.abs(d.ejercido - d.programado);
                        let html = '<strong>' + d.nombre + '</strong>';
                        html += '<br>Programado: ' + formatMoney(d.programado);
                        html += '<br>Ejercido: ' + formatMoney(d.ejercido);
                        html += '<br>Diferencia: ' + formatMoney(diff);
                        html += '<br>Desviación: ' + sign + d.desviacion + '%';
                        tooltip.html(html).style('opacity', 1);
                    })
                    .on('mousemove', (event) => {
                        const rect = container.getBoundingClientRect();
                        tooltip
                            .style('left', (event.clientX - rect.left + 12) + 'px')
                            .style('top', (event.clientY - rect.top - 10) + 'px');
                    })
                    .on('mouseout', () => {
                        circle.attr('r', 6);
                        tooltip.style('opacity', 0);
                    });
            });
        }
     }"
     x-init="init()"
     {{ $attributes->merge(['class' => 'relative']) }}>
    @php $jsonData = ['data' => $data, 'height' => $height, 'threshold' => $threshold]; @endphp
    <script type="application/json" id="{{ $uid }}">{!! json_encode($jsonData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    <div x-ref="chart"></div>
</div>

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/d3/7.8.5/d3.min.js"></script>
@endpush
