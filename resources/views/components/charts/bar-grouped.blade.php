@props([
    'categories' => [],
    'series' => [],
    'colors' => ['#3B82F6', '#F59E0B', '#14B8A6'],
    'height' => 350,
    'yaxisFormat' => 'number',
    'referenceLine' => null,
])

<div wire:ignore
     x-data="{
        chart: null,
        init() {
            const options = {
                chart: {
                    type: 'bar',
                    height: {{ $height }},
                    toolbar: { show: false },
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '60%',
                        borderRadius: 4,
                    },
                },
                xaxis: {
                    categories: @js($categories),
                    labels: { style: { fontSize: '12px' } },
                },
                yaxis: {
                    labels: {
                        formatter: (val) => {
                            @if($yaxisFormat === 'currency')
                                return '$' + (val / 1000000).toFixed(1) + 'M';
                            @elseif($yaxisFormat === 'percent')
                                return Math.round(val) + '%';
                            @else
                                return Math.round(val);
                            @endif
                        },
                    },
                },
                series: @js($series),
                colors: @js($colors),
                dataLabels: { enabled: false },
                legend: { position: 'top', fontSize: '13px' },
                tooltip: {
                    y: {
                        formatter: (val) => {
                            @if($yaxisFormat === 'currency')
                                return '$' + new Intl.NumberFormat().format(val);
                            @elseif($yaxisFormat === 'percent')
                                return Math.round(val * 10) / 10 + '%';
                            @else
                                return val;
                            @endif
                        },
                    },
                },
                annotations: {
                    yaxis: @if($referenceLine !== null)[{
                        y: {{ $referenceLine }},
                        borderColor: '#6B7280',
                        strokeDashArray: 4,
                        label: {
                            text: @if($yaxisFormat === 'currency') '$' + ({{ $referenceLine }} / 1000000).toFixed(1) + 'M' @elseif($yaxisFormat === 'percent') '{{ $referenceLine }}%' @else '{{ $referenceLine }}' @endif,
                            borderColor: '#6B7280',
                            style: { color: '#fff', background: '#6B7280', fontSize: '11px', padding: { left: 6, right: 6, top: 2, bottom: 2 } },
                        },
                    }]@else[]@endif,
                },
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
