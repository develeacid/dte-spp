import './bootstrap';

import focus from '@alpinejs/focus';

document.addEventListener('alpine:init', () => {
    window.Alpine.plugin(focus);
});

import ApexCharts from 'apexcharts';
window.ApexCharts = ApexCharts;
