import axios from 'axios';

export const TOKEN_KEY = 'simlab.token';
export const USER_KEY = 'simlab.user';

/**
 * Alpine $persist JSON-encodes values. Older sessions may store `"token"` with quotes.
 * Always return a bare Sanctum token string.
 */
export function readToken() {
    const raw = window.localStorage.getItem(TOKEN_KEY);
    if (!raw) return '';
    try {
        const parsed = JSON.parse(raw);
        if (typeof parsed === 'string') return parsed;
    } catch (e) {
        /* plain string */
    }
    return raw.replace(/^"|"$/g, '');
}

export function writeToken(token) {
    if (!token) {
        window.localStorage.removeItem(TOKEN_KEY);
        return;
    }
    window.localStorage.setItem(TOKEN_KEY, token);
}

export function readUser() {
    const raw = window.localStorage.getItem(USER_KEY);
    if (!raw) return null;
    try {
        return JSON.parse(raw);
    } catch (e) {
        return null;
    }
}

export function writeUser(user) {
    if (!user) {
        window.localStorage.removeItem(USER_KEY);
        return;
    }
    window.localStorage.setItem(USER_KEY, JSON.stringify(user));
}

export function clearAuthStorage() {
    window.localStorage.removeItem(TOKEN_KEY);
    window.localStorage.removeItem(USER_KEY);
}

export const api = axios.create({
    baseURL: '/api',
    headers: { Accept: 'application/json' },
});

api.interceptors.request.use((config) => {
    const token = readToken();
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

api.interceptors.response.use(
    (response) => response,
    (error) => {
        const status = error.response?.status;
        const onLogin = window.location.pathname === '/' || window.location.pathname.startsWith('/login');
        if (status === 401 && !onLogin) {
            clearAuthStorage();
            window.location.assign('/login');
        }
        return Promise.reject(error);
    }
);

export default api;
