@props([
    'categories' => [],
    'series' => [],
    'height' => 250,
])

<div wire:ignore
     x-data="{
        chart: null,
        init() {
            this.chart = new ApexCharts(this.$refs.chart, {
                chart: { type: 'area', height: {{ $height }}, toolbar: { show: false }, sparkline: { enabled: false } },
                stroke: { curve: 'smooth', width: 2 },
                fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.1 } },
                xaxis: { categories: @js($categories) },
                yaxis: { labels: { formatter: (val) => Math.round(val) } },
                series: @js($series),
                colors: ['rgb(var(--color-primary))'],
                dataLabels: { enabled: false },
                tooltip: { y: { formatter: (val) => val + ' avances' } },
            });
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
