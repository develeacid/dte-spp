@props([
    'categories' => [],
    'series' => [],
    'height' => 250,
    'colors' => ['#3B82F6', '#F59E0B'],
    'yaxisFormatter' => null,
    'tooltipSuffix' => 'avances',
])

<div wire:ignore
     x-data="{
        chart: null,
        init() {
            if (this.$refs.chart && this.$refs.chart.querySelector('.apexcharts-canvas')) return;
            this.chart = new ApexCharts(this.$refs.chart, {
                chart: { type: 'area', height: {{ $height }}, toolbar: { show: false }, sparkline: { enabled: false } },
                stroke: { curve: 'smooth', width: 2 },
                fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.1, opacityTo: 0.1 } },
                xaxis: { categories: @js($categories) },
                yaxis: { labels: { formatter: (val) => {{ $yaxisFormatter === 'percent' ? "Math.round(val) + '%'" : 'Math.round(val)' }} } },
                series: @js($series),
                colors: @js($colors),
                dataLabels: { enabled: false },
                tooltip: { y: { formatter: (val) => val + ' ' + @js($tooltipSuffix) } },
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
