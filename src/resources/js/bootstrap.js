window.axios = (await import('axios')).default;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';