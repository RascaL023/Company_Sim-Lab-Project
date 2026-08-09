import axios from 'axios';

export const TOKEN_KEY = 'simlab.token';
export const USER_KEY = 'simlab.user';

export const api = axios.create({
    baseURL: '/api',
    headers: { Accept: 'application/json' },
});

api.interceptors.request.use((config) => {
    const token = window.localStorage.getItem(TOKEN_KEY);
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

api.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error.response?.status;
        if (status === 401 && !window.location.pathname.startsWith('/login')) {
            window.localStorage.removeItem(TOKEN_KEY);
            window.localStorage.removeItem(USER_KEY);
            window.location.assign('/login');
        }
        return Promise.reject(error);
    }
);

export default api;
