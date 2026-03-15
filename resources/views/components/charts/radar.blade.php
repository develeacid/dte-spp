@props([
    'labels' => [],
    'series' => [],
    'colors' => ['#3B82F6', '#9CA3AF'],
    'height' => 300,
    'maxValue' => 100,
])

<div wire:ignore
     x-data="{
        chart: null,
        init() {
            const options = {
                chart: {
                    type: 'radar',
                    height: {{ $height }},
                    toolbar: { show: false },
                },
                xaxis: {
                    categories: @js($labels),
                },
                yaxis: {
                    max: {{ $maxValue }},
                    tickAmount: 5,
                    labels: { style: { fontSize: '11px' } },
                },
                series: @js($series),
                colors: @js($colors),
                fill: { opacity: 0.2 },
                stroke: { width: 2 },
                markers: { size: 3 },
                legend: { position: 'bottom', fontSize: '13px' },
                dataLabels: { enabled: false },
            };

            this.chart = new ApexCharts(this.$refs.chart, options);
            this.chart.render();
        },
        destroy() {
            if (this.chart) { this.chart.destroy(); this.chart = null; }
        }
     }"
     x-init="init()"
     x-on:remove="destroy()"
     {{ $attributes->merge(['class' => '']) }}>
    <div x-ref="chart"></div>
</div>
