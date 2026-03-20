@props([
    'categories' => [],
    'series' => [],
    'height' => 300,
    'referenceLine' => null,
    'referenceLabel' => 'Meta',
    'sorted' => false,
])

<div wire:ignore
     x-data="{
        chart: null,
        init() {
            if (this.$refs.chart && this.$refs.chart.querySelector('.apexcharts-canvas')) return;
            let categories = @js($categories);
            let series = @js($series);

            @if($sorted)
            if (series.length > 0 && series[0].data && series[0].data.length > 0) {
                const indices = series[0].data.map((val, idx) => idx);
                indices.sort((a, b) => series[0].data[b] - series[0].data[a]);

                categories = indices.map(i => categories[i]);
                series = series.map(s => ({
                    ...s,
                    data: indices.map(i => s.data[i]),
                }));
            }
            @endif

            const options = {
                chart: { type: 'bar', height: {{ $height }} },
                plotOptions: { bar: { horizontal: true, barHeight: '60%', borderRadius: 4 } },
                xaxis: { categories: categories, labels: { formatter: (val) => Math.round(val) + '%' } },
                yaxis: { labels: { style: { fontSize: '12px' } } },
                series: series,
                colors: ['#3B82F6', '#F59E0B'],
                dataLabels: { enabled: true, formatter: (val) => Math.round(val) + '%', style: { fontSize: '11px' } },
                tooltip: { y: { formatter: (val) => Math.round(val * 10) / 10 + '%' } },
                legend: { position: 'top', fontSize: '13px' },
                annotations: {
                    xaxis: @if($referenceLine !== null)[{
                        x: {{ $referenceLine }},
                        borderColor: '#6B7280',
                        strokeDashArray: 4,
                        label: {
                            text: @js($referenceLabel),
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
