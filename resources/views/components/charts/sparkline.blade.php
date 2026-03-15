@props([
    'data' => [],
    'color' => '#3B82F6',
    'width' => 100,
    'height' => 30,
    'type' => 'line',
])

<div wire:ignore
     x-data="{
        chart: null,
        init() {
            const chartType = @js($type);
            const options = {
                chart: {
                    type: chartType === 'bar' ? 'bar' : 'line',
                    width: {{ $width }},
                    height: {{ $height }},
                    sparkline: { enabled: true },
                    animations: { enabled: false },
                },
                series: [{ data: @js($data) }],
                colors: [@js($color)],
                stroke: { width: chartType === 'line' ? 1.5 : 0, curve: 'smooth' },
                plotOptions: {
                    bar: {
                        columnWidth: '80%',
                        borderRadius: 1,
                    },
                },
                tooltip: { enabled: false },
                xaxis: { labels: { show: false }, axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis: { labels: { show: false } },
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
     {{ $attributes->merge(['class' => 'inline-block']) }}>
    <div x-ref="chart"></div>
</div>
