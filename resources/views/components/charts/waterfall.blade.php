@props([
    'data' => [],
    'height' => 400,
])

{{--
Waterfall Chart D3 — Cascada financiera.
Cada item en $data:
  - label: string (e.g., 'Aprobado', 'Adecuaciones +', 'No ejercido', 'Ejercido')
  - value: number (monto absoluto para subtotales, delta para incrementos/decrementos)
  - type: 'total' | 'increment' | 'decrement'
--}}

<div wire:ignore
     x-data="{
        init() {
            this.$nextTick(() => this.render());
        },
        render() {
            const container = this.$refs.chart;
            const data = @js($data);

            if (!data.length || typeof d3 === 'undefined') return;

            d3.select(container).selectAll('*').remove();

            const margin = { top: 20, right: 30, bottom: 60, left: 80 };
            const width = container.clientWidth - margin.left - margin.right;
            const height = {{ $height }} - margin.top - margin.bottom;

            const svg = d3.select(container)
                .append('svg')
                .attr('width', width + margin.left + margin.right)
                .attr('height', height + margin.top + margin.bottom)
                .append('g')
                .attr('transform', `translate(${margin.left},${margin.top})`);

            let running = 0;
            const processed = data.map(d => {
                if (d.type === 'total') {
                    const item = { ...d, start: 0, end: d.value };
                    running = d.value;
                    return item;
                } else if (d.type === 'increment') {
                    const item = { ...d, start: running, end: running + d.value };
                    running += d.value;
                    return item;
                } else {
                    const item = { ...d, start: running, end: running - Math.abs(d.value) };
                    running -= Math.abs(d.value);
                    return item;
                }
            });

            const allVals = processed.flatMap(d => [d.start, d.end]);
            const yMax = d3.max(allVals) * 1.1;

            const x = d3.scaleBand()
                .domain(processed.map(d => d.label))
                .range([0, width])
                .padding(0.3);

            const y = d3.scaleLinear()
                .domain([0, yMax])
                .range([height, 0]);

            svg.append('g')
                .attr('transform', `translate(0,${height})`)
                .call(d3.axisBottom(x))
                .selectAll('text')
                .attr('transform', 'rotate(-25)')
                .style('text-anchor', 'end')
                .style('font-size', '11px');

            svg.append('g')
                .call(d3.axisLeft(y).ticks(6).tickFormat(v => {
                    if (v >= 1e6) return '$' + (v / 1e6).toFixed(1) + 'M';
                    if (v >= 1e3) return '$' + (v / 1e3).toFixed(0) + 'K';
                    return '$' + v;
                }))
                .selectAll('text')
                .style('font-size', '11px');

            const colorMap = { total: '#6B7280', increment: '#22C55E', decrement: '#EF4444' };

            processed.forEach((d, i) => {
                const barY = y(Math.max(d.start, d.end));
                const barH = Math.abs(y(d.start) - y(d.end)) || 1;

                svg.append('rect')
                    .attr('x', x(d.label))
                    .attr('y', barY)
                    .attr('width', x.bandwidth())
                    .attr('height', barH)
                    .attr('fill', colorMap[d.type])
                    .attr('rx', 3);

                const valFormatted = d.value >= 1e6
                    ? '$' + (d.value / 1e6).toFixed(1) + 'M'
                    : '$' + new Intl.NumberFormat().format(d.value);

                svg.append('text')
                    .attr('x', x(d.label) + x.bandwidth() / 2)
                    .attr('y', barY - 6)
                    .attr('text-anchor', 'middle')
                    .attr('fill', '#374151')
                    .attr('font-size', '11px')
                    .attr('font-weight', 500)
                    .text((d.type === 'decrement' ? '-' : '') + valFormatted);

                if (i < processed.length - 1 && d.type !== 'total') {
                    const nextD = processed[i + 1];
                    svg.append('line')
                        .attr('x1', x(d.label) + x.bandwidth())
                        .attr('x2', x(nextD.label))
                        .attr('y1', y(d.end))
                        .attr('y2', y(d.end))
                        .attr('stroke', '#9CA3AF')
                        .attr('stroke-width', 1)
                        .attr('stroke-dasharray', '3,3');
                }
            });

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
                .style('z-index', 50);

            svg.selectAll('rect')
                .on('mouseover', function(event, d) {
                    const idx = Math.floor((event.target.getAttribute('x') - x.range()[0]) / (x.step()));
                    const item = processed[idx];
                    if (!item) return;
                    tooltip
                        .html(`<strong>${item.label}</strong><br>$${new Intl.NumberFormat().format(item.value)}`)
                        .style('opacity', 1);
                })
                .on('mousemove', (event) => {
                    const rect = container.getBoundingClientRect();
                    tooltip
                        .style('left', (event.clientX - rect.left + 12) + 'px')
                        .style('top', (event.clientY - rect.top - 10) + 'px');
                })
                .on('mouseout', () => tooltip.style('opacity', 0));
        }
     }"
     x-init="init()"
     {{ $attributes->merge(['class' => 'relative']) }}>
    <div x-ref="chart"></div>
</div>

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/d3/7.8.5/d3.min.js"></script>
@endpush
