import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Realtime via HTTP polling — vezi resources/js/poller.js.
import './poller.js';
