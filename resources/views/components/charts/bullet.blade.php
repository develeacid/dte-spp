@props([
    'data' => [],
    'height' => 400,
    'showLabels' => true,
    'animate' => true,
])

{{--
Bullet Chart D3 para indicadores PbR.
Cada item en $data debe tener:
  - nombre: string
  - resultado: number (valor real)
  - meta: number (valor meta)
  - rango_verde_min, rango_verde_max: number
  - rango_amarillo_min, rango_amarillo_max: number
  - rango_rojo_min, rango_rojo_max: number
  - (opcional) variables: string, formula: string (para tooltip)
--}}

<div wire:ignore
     x-data="{
        init() {
            this.$nextTick(() => this.render());
        },
        render() {
            const container = this.$refs.chart;
            const data = @js($data);
            const showLabels = @js($showLabels);
            const animate = @js($animate);

            if (!data.length || typeof d3 === 'undefined') return;

            const itemHeight = 48;
            const margin = { top: 10, right: 80, bottom: 10, left: showLabels ? 200 : 20 };
            const width = container.clientWidth - margin.left - margin.right;
            const height = data.length * itemHeight;

            d3.select(container).selectAll('svg').remove();

            const svg = d3.select(container)
                .append('svg')
                .attr('width', width + margin.left + margin.right)
                .attr('height', height + margin.top + margin.bottom)
                .append('g')
                .attr('transform', `translate(${margin.left},${margin.top})`);

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

            data.forEach((d, i) => {
                const y = i * itemHeight;
                const allValues = [
                    d.rango_rojo_max ?? 0,
                    d.rango_amarillo_max ?? 0,
                    d.rango_verde_max ?? 0,
                    d.meta ?? 0,
                    d.resultado ?? 0,
                ];
                const maxVal = Math.max(...allValues) * 1.1 || 100;

                const x = d3.scaleLinear().domain([0, maxVal]).range([0, width]);

                const ranges = [
                    { min: d.rango_rojo_min ?? 0, max: d.rango_rojo_max ?? 0, color: '#FEE2E2' },
                    { min: d.rango_amarillo_min ?? 0, max: d.rango_amarillo_max ?? 0, color: '#FEF3C7' },
                    { min: d.rango_verde_min ?? 0, max: d.rango_verde_max ?? 0, color: '#D1FAE5' },
                ];

                ranges.forEach(r => {
                    if (r.max > r.min) {
                        svg.append('rect')
                            .attr('x', x(r.min))
                            .attr('y', y + 8)
                            .attr('width', x(r.max) - x(r.min))
                            .attr('height', 32)
                            .attr('fill', r.color)
                            .attr('rx', 2);
                    }
                });

                const barWidth = animate ? 0 : x(d.resultado ?? 0);
                const bar = svg.append('rect')
                    .attr('x', 0)
                    .attr('y', y + 14)
                    .attr('width', barWidth)
                    .attr('height', 20)
                    .attr('fill', '#374151')
                    .attr('rx', 2)
                    .attr('opacity', 0.85);

                if (animate) {
                    bar.transition()
                        .duration(800)
                        .ease(d3.easeQuadOut)
                        .attr('width', x(d.resultado ?? 0));
                }

                if (d.meta != null) {
                    svg.append('line')
                        .attr('x1', x(d.meta))
                        .attr('x2', x(d.meta))
                        .attr('y1', y + 6)
                        .attr('y2', y + 42)
                        .attr('stroke', '#111827')
                        .attr('stroke-width', 2);
                }

                if (showLabels) {
                    svg.append('text')
                        .attr('x', -8)
                        .attr('y', y + 28)
                        .attr('text-anchor', 'end')
                        .attr('fill', '#374151')
                        .attr('font-size', '12px')
                        .text(d.nombre?.length > 28 ? d.nombre.substring(0, 28) + '...' : d.nombre);
                }

                svg.append('text')
                    .attr('x', width + 8)
                    .attr('y', y + 28)
                    .attr('text-anchor', 'start')
                    .attr('fill', '#374151')
                    .attr('font-size', '12px')
                    .attr('font-weight', 600)
                    .text(`${d.resultado ?? 0}%`);

                svg.append('rect')
                    .attr('x', 0)
                    .attr('y', y)
                    .attr('width', width)
                    .attr('height', itemHeight)
                    .attr('fill', 'transparent')
                    .on('mouseover', (event) => {
                        let html = `<strong>${d.nombre}</strong><br>Resultado: ${d.resultado ?? 0}%`;
                        if (d.meta != null) html += `<br>Meta: ${d.meta}%`;
                        if (d.formula) html += `<br>Formula: ${d.formula}`;
                        if (d.variables) html += `<br>Variables: ${d.variables}`;
                        tooltip.html(html).style('opacity', 1);
                    })
                    .on('mousemove', (event) => {
                        const rect = container.getBoundingClientRect();
                        tooltip
                            .style('left', (event.clientX - rect.left + 12) + 'px')
                            .style('top', (event.clientY - rect.top - 10) + 'px');
                    })
                    .on('mouseout', () => tooltip.style('opacity', 0));
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
