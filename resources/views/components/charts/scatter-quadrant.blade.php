@props([
    'series' => [],
    'height' => 400,
    'xLabel' => 'Avance financiero (%)',
    'yLabel' => 'Avance físico (%)',
    'xThreshold' => 75,
    'yThreshold' => 75,
])

<div wire:ignore
     x-data="{
        chart: null,
        init() {
            const options = {
                chart: {
                    type: 'scatter',
                    height: {{ $height }},
                    toolbar: { show: false },
                    zoom: { enabled: false },
                },
                xaxis: {
                    min: 0,
                    max: 120,
                    tickAmount: 6,
                    title: { text: @js($xLabel), style: { fontSize: '12px' } },
                    labels: { formatter: (val) => Math.round(val) + '%' },
                },
                yaxis: {
                    min: 0,
                    max: 120,
                    tickAmount: 6,
                    title: { text: @js($yLabel), style: { fontSize: '12px' } },
                    labels: { formatter: (val) => Math.round(val) + '%' },
                },
                series: @js($series),
                colors: ['#3B82F6'],
                markers: { size: 8 },
                legend: { position: 'top', fontSize: '13px' },
                tooltip: {
                    custom: function({ seriesIndex, dataPointIndex, w }) {
                        const point = w.config.series[seriesIndex].data[dataPointIndex];
                        return '<div class=\"px-3 py-2 text-sm\">' +
                            '<b>' + @js($xLabel) + ':</b> ' + point[0] + '%<br>' +
                            '<b>' + @js($yLabel) + ':</b> ' + point[1] + '%' +
                            '</div>';
                    },
                },
                annotations: {
                    xaxis: [{
                        x: {{ $xThreshold }},
                        borderColor: '#9CA3AF',
                        strokeDashArray: 4,
                        label: { text: '' },
                    }],
                    yaxis: [{
                        y: {{ $yThreshold }},
                        borderColor: '#9CA3AF',
                        strokeDashArray: 4,
                        label: { text: '' },
                    }],
                    points: [
                        {
                            x: {{ $xThreshold / 2 }},
                            y: {{ $yThreshold + ($yThreshold > 0 ? (120 - $yThreshold) / 2 : 30) }},
                            marker: { size: 0 },
                            label: {
                                text: 'Eficiente',
                                borderColor: 'transparent',
                                style: { background: 'transparent', color: '#16A34A', fontSize: '12px', fontWeight: 600, padding: { left: 4, right: 4, top: 2, bottom: 2 } },
                            },
                        },
                        {
                            x: {{ $xThreshold + (120 - $xThreshold) / 2 }},
                            y: {{ $yThreshold + (120 - $yThreshold) / 2 }},
                            marker: { size: 0 },
                            label: {
                                text: 'Sobre-entrega',
                                borderColor: 'transparent',
                                style: { background: 'transparent', color: '#3B82F6', fontSize: '12px', fontWeight: 600, padding: { left: 4, right: 4, top: 2, bottom: 2 } },
                            },
                        },
                        {
                            x: {{ $xThreshold / 2 }},
                            y: {{ $yThreshold / 2 }},
                            marker: { size: 0 },
                            label: {
                                text: 'Sub-ejercicio',
                                borderColor: 'transparent',
                                style: { background: 'transparent', color: '#CA8A04', fontSize: '12px', fontWeight: 600, padding: { left: 4, right: 4, top: 2, bottom: 2 } },
                            },
                        },
                        {
                            x: {{ $xThreshold + (120 - $xThreshold) / 2 }},
                            y: {{ $yThreshold / 2 }},
                            marker: { size: 0 },
                            label: {
                                text: 'Gasta sin entregar',
                                borderColor: 'transparent',
                                style: { background: 'transparent', color: '#DC2626', fontSize: '12px', fontWeight: 600, padding: { left: 4, right: 4, top: 2, bottom: 2 } },
                            },
                        },
                    ],
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
