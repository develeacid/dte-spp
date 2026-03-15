@props([
    'data' => [],
    'height' => 300,
])

{{--
Treemap D3 — Distribución jerárquica de presupuesto por eje PED.
$data: objeto jerárquico:
  { 'name' => 'Presupuesto', 'children' => [
      { 'name' => 'Eje 1', 'value' => 50000000, 'semaforo' => 'verde', 'avance' => 75,
        'children' => [{ 'name' => 'Prog A', 'value' => 30000000, 'semaforo' => 'verde' }]
      }
  ]}
--}}

<div wire:ignore
     x-data="{
        init() {
            this.$nextTick(() => this.render());
        },
        render() {
            const container = this.$refs.chart;
            const data = @js($data);

            if (!data || !data.children || !data.children.length || typeof d3 === 'undefined') return;

            d3.select(container).selectAll('*').remove();

            const width = container.clientWidth || 600;
            const height = {{ $height }};

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

            const semaforoLabel = {
                verde: 'Verde',
                amarillo: 'Amarillo',
                rojo: 'Rojo',
                gris: 'Sin dato',
            };

            const semaforoDot = (s) => {
                const c = colorMap[s] || colorMap.gris;
                return `<span style=\"display:inline-block;width:8px;height:8px;border-radius:50%;background:${c};margin-right:4px;\"></span>`;
            };

            const formatMoney = (v) => {
                if (v >= 1e6) return '$' + (v / 1e6).toFixed(1) + 'M';
                if (v >= 1e3) return '$' + (v / 1e3).toFixed(0) + 'K';
                return '$' + v;
            };

            const root = d3.hierarchy(data)
                .sum(d => d.value || 0)
                .sort((a, b) => b.value - a.value);

            d3.treemap()
                .size([width, height])
                .tile(d3.treemapSquarify)
                .padding(2)
                .round(true)(root);

            const svg = d3.select(container)
                .append('svg')
                .attr('width', width)
                .attr('height', height);

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

            const leaves = root.leaves();

            const cell = svg.selectAll('g')
                .data(leaves)
                .join('g')
                .attr('transform', d => `translate(${d.x0},${d.y0})`);

            cell.append('rect')
                .attr('width', d => d.x1 - d.x0)
                .attr('height', d => d.y1 - d.y0)
                .attr('fill', d => colorMap[d.data.semaforo] || colorMap.gris)
                .attr('rx', 4)
                .style('cursor', 'pointer');

            cell.each(function(d) {
                const w = d.x1 - d.x0;
                const h = d.y1 - d.y0;
                const g = d3.select(this);
                const sem = d.data.semaforo || 'gris';

                if (w > 60 && h > 40) {
                    g.append('text')
                        .attr('x', 6)
                        .attr('y', 16)
                        .attr('fill', textColorMap[sem] || '#6B7280')
                        .attr('font-size', '12px')
                        .attr('font-weight', 600)
                        .text(d.data.name.length > Math.floor(w / 7) ? d.data.name.substring(0, Math.floor(w / 7)) + '...' : d.data.name);

                    g.append('text')
                        .attr('x', 6)
                        .attr('y', 32)
                        .attr('fill', textColorMap[sem] || '#6B7280')
                        .attr('font-size', '11px')
                        .text(formatMoney(d.value) + (d.data.avance != null ? ' · ' + d.data.avance + '%' : ''));
                } else if (w > 30 && h > 18) {
                    const abbr = d.data.name.substring(0, Math.max(2, Math.floor(w / 8)));
                    g.append('text')
                        .attr('x', 4)
                        .attr('y', Math.min(h - 4, 14))
                        .attr('fill', textColorMap[sem] || '#6B7280')
                        .attr('font-size', '10px')
                        .text(abbr);
                }
            });

            cell
                .on('mouseover', function(event, d) {
                    d3.select(this).select('rect').attr('opacity', 0.8);

                    let html = `<strong>${d.data.name}</strong>`;
                    html += `<br>Presupuesto: ${formatMoney(d.value)}`;
                    if (d.data.avance != null) html += `<br>Avance: ${d.data.avance}%`;
                    html += `<br>Semáforo: ${semaforoDot(d.data.semaforo || 'gris')}${semaforoLabel[d.data.semaforo] || 'Sin dato'}`;

                    const parent = d.parent?.data;
                    if (parent && parent.children && parent.children.length > 1) {
                        html += `<br><br><strong>${parent.name}</strong>`;
                        parent.children.forEach(c => {
                            html += `<br>${semaforoDot(c.semaforo || 'gris')}${c.name}: ${formatMoney(c.value || 0)}`;
                        });
                    }

                    tooltip.html(html).style('opacity', 1);
                })
                .on('mousemove', (event) => {
                    const rect = container.getBoundingClientRect();
                    tooltip
                        .style('left', (event.clientX - rect.left + 12) + 'px')
                        .style('top', (event.clientY - rect.top - 10) + 'px');
                })
                .on('mouseout', function() {
                    d3.select(this).select('rect').attr('opacity', 1);
                    tooltip.style('opacity', 0);
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
