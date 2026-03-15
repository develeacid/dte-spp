@props([
    'data' => [],
    'size' => 400,
    'colorScheme' => ['#3B82F6', '#22C55E', '#F59E0B', '#EF4444', '#8B5CF6'],
])

{{--
Sunburst D3 — Anillos concéntricos de alineación PED.
- $data: objeto jerárquico { 'name' => 'Total', 'value' => 100000000, 'children' => [{ 'name' => 'Eje 1', 'value' => ..., 'children' => [...] }] }
- $size: ancho y alto en px (cuadrado, default 400)
- $colorScheme: colores por segmento de primer nivel
--}}

<div wire:ignore
     x-data="{
        init() {
            this.$nextTick(() => this.render());
        },
        render() {
            const container = this.$refs.chart;
            const rawData = @js($data);
            const size = @js($size);
            const baseColors = @js($colorScheme);

            if (!rawData || !rawData.children || !rawData.children.length || typeof d3 === 'undefined') return;

            d3.select(container).selectAll('*').remove();

            const radius = size / 2;

            const formatMoney = (v) => {
                if (v >= 1e9) return '$' + (v / 1e9).toFixed(1) + 'B';
                if (v >= 1e6) return '$' + (v / 1e6).toFixed(1) + 'M';
                if (v >= 1e3) return '$' + (v / 1e3).toFixed(0) + 'K';
                return '$' + v.toLocaleString('es-MX');
            };

            const formatMoneyFull = (v) => {
                return '$' + v.toLocaleString('es-MX', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
            };

            /* Build hierarchy */
            const root = d3.hierarchy(rawData)
                .sum(d => d.children ? 0 : (d.value || 0))
                .sort((a, b) => b.value - a.value);

            const partition = d3.partition()
                .size([2 * Math.PI, radius]);

            partition(root);

            /* Assign colors: top-level gets base color, descendants get lighter shades */
            const colorMap = new Map();
            root.children.forEach((child, i) => {
                const base = d3.color(baseColors[i % baseColors.length]);
                colorMap.set(child, base.formatHex());

                child.descendants().forEach((desc) => {
                    if (desc === child) return;
                    const depth = desc.depth - child.depth;
                    const lighter = d3.interpolate(base.formatHex(), '#FFFFFF')(depth * 0.2);
                    colorMap.set(desc, lighter);
                });
            });
            colorMap.set(root, '#F3F4F6');

            const svg = d3.select(container)
                .append('svg')
                .attr('width', size)
                .attr('height', size)
                .style('display', 'block');

            const g = svg.append('g')
                .attr('transform', `translate(${radius},${radius})`);

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
                .style('max-width', '320px');

            const arc = d3.arc()
                .startAngle(d => d.x0)
                .endAngle(d => d.x1)
                .innerRadius(d => d.y0)
                .outerRadius(d => d.y1)
                .padAngle(0.01)
                .padRadius(radius / 2);

            /* Current view state for zoom */
            let currentRoot = root;

            /* Get ancestor path as string */
            function getPath(d) {
                const path = d.ancestors().reverse().slice(1);
                return path.map(p => p.data.name).join(' > ');
            }

            /* Transition arcs to new root */
            function zoomTo(target) {
                currentRoot = target;

                const t = g.transition().duration(600);

                const newX = d3.scaleLinear()
                    .domain([target.x0, target.x1])
                    .range([0, 2 * Math.PI]);

                const newY = d3.scaleLinear()
                    .domain([target.y0, radius])
                    .range([0, radius]);

                paths.transition(t)
                    .attrTween('d', (d) => {
                        const xi = d3.interpolate(d._current.x0, newX(d.x0));
                        const xf = d3.interpolate(d._current.x1, newX(d.x1));
                        const yi = d3.interpolate(d._current.y0, newY(d.y0));
                        const yf = d3.interpolate(d._current.y1, newY(d.y1));
                        return (t) => {
                            d._current = { x0: xi(t), x1: xf(t), y0: yi(t), y1: yf(t) };
                            return arc(d._current);
                        };
                    })
                    .style('visibility', (d) => {
                        return d.x0 >= target.x0 && d.x1 <= target.x1 ? 'visible' : 'hidden';
                    });

                /* Update center label */
                centerLabel.text(target === root ? 'Presupuesto total' : target.data.name);
                centerValue.text(formatMoney(target.value));
            }

            /* Draw arcs (skip root center) */
            const paths = g.selectAll('path')
                .data(root.descendants().filter(d => d.depth > 0))
                .join('path')
                .attr('d', arc)
                .attr('fill', d => colorMap.get(d) || '#D1D5DB')
                .attr('stroke', '#FFFFFF')
                .attr('stroke-width', 1)
                .style('cursor', 'pointer')
                .each(function(d) {
                    d._current = { x0: d.x0, x1: d.x1, y0: d.y0, y1: d.y1 };
                });

            paths
                .on('mouseover', (event, d) => {
                    d3.select(event.currentTarget).attr('opacity', 0.8);
                    const path = getPath(d);
                    let html = `<strong>${path || d.data.name}</strong>`;
                    html += `<br>Valor: ${formatMoneyFull(d.value)}`;
                    tooltip.html(html).style('opacity', 1);
                })
                .on('mousemove', (event) => {
                    const cRect = container.getBoundingClientRect();
                    tooltip
                        .style('left', (event.clientX - cRect.left + 12) + 'px')
                        .style('top', (event.clientY - cRect.top - 10) + 'px');
                })
                .on('mouseout', (event) => {
                    d3.select(event.currentTarget).attr('opacity', 1);
                    tooltip.style('opacity', 0);
                })
                .on('click', (event, d) => {
                    if (d.children) {
                        zoomTo(d);
                    }
                });

            /* Center circle — click to drill up */
            g.append('circle')
                .attr('r', root.y1 > 0 ? root.children[0].y0 : radius * 0.2)
                .attr('fill', '#F9FAFB')
                .attr('stroke', '#E5E7EB')
                .attr('stroke-width', 1)
                .style('cursor', 'pointer')
                .on('click', () => {
                    if (currentRoot !== root && currentRoot.parent) {
                        zoomTo(currentRoot.parent);
                    }
                });

            const centerLabel = g.append('text')
                .attr('text-anchor', 'middle')
                .attr('dy', '-0.4em')
                .attr('fill', '#374151')
                .attr('font-size', '12px')
                .attr('font-weight', 600)
                .style('pointer-events', 'none')
                .text('Presupuesto total');

            const centerValue = g.append('text')
                .attr('text-anchor', 'middle')
                .attr('dy', '1em')
                .attr('fill', '#6B7280')
                .attr('font-size', '14px')
                .attr('font-weight', 700)
                .style('pointer-events', 'none')
                .text(formatMoney(root.value));
        }
     }"
     x-init="init()"
     {{ $attributes->merge(['class' => 'relative']) }}>
    <div x-ref="chart"></div>
</div>

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/d3/7.8.5/d3.min.js"></script>
@endpush
