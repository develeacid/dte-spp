@props([
    'labels' => [],
    'series' => [],
    'colors' => ['#22C55E', '#EAB308', '#EF4444'],
    'height' => 280,
    'centerText' => null,
    'centerSubtext' => null,
])

<div wire:ignore
     x-data="{
        chart: null,
        init() {
            if (this.$refs.chart && this.$refs.chart.querySelector('.apexcharts-canvas')) return;
            const options = {
                chart: {
                    type: 'donut',
                    height: {{ $height }},
                    animations: { enabled: true, easing: 'easeout', speed: 800 },
                },
                labels: @js($labels),
                series: @js($series),
                colors: @js($colors),
                legend: { position: 'bottom', fontSize: '13px' },
                dataLabels: { enabled: true, formatter: (val) => Math.round(val) + '%' },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '65%',
                            labels: {
                                show: {{ $centerText ? 'true' : 'false' }},
                                name: {
                                    show: {{ $centerText ? 'true' : 'false' }},
                                    fontSize: '16px',
                                    fontWeight: 600,
                                    offsetY: {{ $centerSubtext ? '-8' : '0' }},
                                },
                                value: {
                                    show: {{ $centerSubtext ? 'true' : 'false' }},
                                    fontSize: '12px',
                                    fontWeight: 400,
                                    offsetY: 4,
                                },
                                total: {
                                    show: {{ $centerText ? 'true' : 'false' }},
                                    showAlways: {{ $centerText ? 'true' : 'false' }},
                                    label: @js($centerText ?? ''),
                                    formatter: () => @js($centerSubtext ?? ''),
                                },
                            },
                        },
                    },
                },
                responsive: [{ breakpoint: 480, options: { chart: { height: 240 }, legend: { position: 'bottom' } } }],
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
     {{ $attributes->merge(['class' => '']) }}
     style="min-height: 200px">
    <div x-ref="chart"></div>
</div>
