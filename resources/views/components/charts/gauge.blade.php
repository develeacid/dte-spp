@props([
    'value' => 0,
    'max' => 100,
    'ranges' => [
        ['min' => 0, 'max' => 60, 'color' => '#EF4444'],
        ['min' => 60, 'max' => 85, 'color' => '#EAB308'],
        ['min' => 85, 'max' => 100, 'color' => '#22C55E'],
    ],
    'label' => '',
    'animate' => true,
])

{{--
Gauge D3 — Velocímetro ejecutivo semicircular (180°).
200×120px compacto para tarjetas KPI.
--}}

@php $uid = 'gauge-' . Str::random(8); @endphp

<div wire:ignore
     x-data="{ init() { this.$nextTick(() => this.render()); },
        render() {
            const container = this.$refs.chart;
            const cfg = JSON.parse(document.getElementById('{{ $uid }}').textContent);
            const value = Math.min(cfg.value, cfg.max);
            const max = cfg.max;
            const ranges = cfg.ranges;
            const label = cfg.label;
            const animate = cfg.animate;

            if (typeof d3 === 'undefined') return;

            d3.select(container).selectAll('svg').remove();

            const width = 200;
            const height = 120;
            const cx = width / 2;
            const cy = height - 10;
            const outerR = 80;
            const innerR = 55;

            const svg = d3.select(container)
                .append('svg')
                .attr('width', width)
                .attr('height', height)
                .attr('viewBox', `0 0 ${width} ${height}`)
                .style('display', 'block')
                .style('margin', '0 auto');

            const g = svg.append('g').attr('transform', `translate(${cx},${cy})`);

            const angleScale = d3.scaleLinear().domain([0, max]).range([-Math.PI / 2, Math.PI / 2]);

            const arc = d3.arc().innerRadius(innerR).outerRadius(outerR);

            ranges.forEach(r => {
                g.append('path')
                    .attr('d', arc({
                        startAngle: angleScale(r.min) - Math.PI / 2,
                        endAngle: angleScale(r.max) - Math.PI / 2,
                    }))
                    .attr('fill', r.color)
                    .attr('opacity', 0.7)
                    .attr('transform', 'rotate(180)');
            });

            const needleLen = outerR - 8;
            const targetAngle = (value / max) * 180 - 90;

            const needle = g.append('line')
                .attr('x1', 0)
                .attr('y1', 0)
                .attr('x2', 0)
                .attr('y2', -needleLen)
                .attr('stroke', '#1F2937')
                .attr('stroke-width', 2)
                .attr('stroke-linecap', 'round')
                .attr('transform', 'rotate(-90)');

            if (animate) {
                needle.transition()
                    .duration(1200)
                    .ease(d3.easeQuadOut)
                    .attrTween('transform', () => {
                        const interp = d3.interpolate(-90, targetAngle);
                        return t => `rotate(${interp(t)})`;
                    });
            } else {
                needle.attr('transform', `rotate(${targetAngle})`);
            }

            g.append('circle').attr('r', 4).attr('fill', '#1F2937');

            g.append('text')
                .attr('y', -20)
                .attr('text-anchor', 'middle')
                .attr('fill', '#111827')
                .attr('font-size', '28px')
                .attr('font-weight', 700)
                .text(Math.round(value) + '%');

            if (label) {
                g.append('text')
                    .attr('y', -4)
                    .attr('text-anchor', 'middle')
                    .attr('fill', '#6B7280')
                    .attr('font-size', '11px')
                    .text(label);
            }
        }
     }"
     x-init="init()"
     {{ $attributes->merge(['class' => 'relative']) }}>
    @php $jsonData = ['value' => $value, 'max' => $max, 'ranges' => $ranges, 'label' => $label, 'animate' => $animate]; @endphp
    <script type="application/json" id="{{ $uid }}">{!! json_encode($jsonData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    <div x-ref="chart"></div>
</div>

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/d3/7.8.5/d3.min.js"></script>
@endpush
