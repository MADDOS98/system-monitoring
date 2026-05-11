import './bootstrap';
import Chart from 'chart.js/auto';
window.Chart = Chart;
/* 
import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start(); */

const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
document.cookie = `tz=${tz}; path=/; max-age=31536000`;
