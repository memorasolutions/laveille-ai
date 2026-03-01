// Author: MEMORA solutions, https://memora.solutions ; info@memora.ca
import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
