@props([
    'categories' => [],
    'series' => [],
    'height' => 300,
])

<div wire:ignore
     x-data="{
        chart: null,
        init() {
            this.chart = new ApexCharts(this.$refs.chart, {
                chart: { type: 'bar', height: {{ $height }} },
                plotOptions: { bar: { horizontal: true, barHeight: '60%', borderRadius: 4 } },
                xaxis: { categories: @js($categories), labels: { formatter: (val) => Math.round(val) + '%' } },
                yaxis: { labels: { style: { fontSize: '12px' } } },
                series: @js($series),
                colors: ['rgb(var(--color-primary))', '#D1D5DB'],
                dataLabels: { enabled: true, formatter: (val) => Math.round(val) + '%', style: { fontSize: '11px' } },
                tooltip: { y: { formatter: (val) => Math.round(val * 10) / 10 + '%' } },
                legend: { position: 'top', fontSize: '13px' },
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
