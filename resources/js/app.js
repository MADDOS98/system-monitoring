import './bootstrap';
/* 
import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start(); */

const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
document.cookie = `tz=${tz}; path=/; max-age=31536000`;
