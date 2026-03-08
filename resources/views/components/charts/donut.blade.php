@props([
    'labels' => [],
    'series' => [],
    'colors' => ['#059669', '#F59E0B', '#DC2626'],
    'height' => 280,
])

<div wire:ignore
     x-data="{
        chart: null,
        init() {
            this.chart = new ApexCharts(this.$refs.chart, {
                chart: { type: 'donut', height: {{ $height }} },
                labels: @js($labels),
                series: @js($series),
                colors: @js($colors),
                legend: { position: 'bottom', fontSize: '13px' },
                dataLabels: { enabled: true, formatter: (val) => Math.round(val) + '%' },
                plotOptions: { pie: { donut: { size: '55%' } } },
                responsive: [{ breakpoint: 480, options: { chart: { height: 240 }, legend: { position: 'bottom' } } }],
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
