import './bootstrap';
import Alpine from 'alpinejs';

import api, { clearAuthStorage, readToken, readUser, writeToken, writeUser } from './api';
import * as fmt from './helpers';
import { appShell, dropdown, pagedList } from './components';

import * as catalogPages from './pages/catalog';
import * as transactionPages from './pages/transactions';
import * as operationPages from './pages/operations';
import * as adminPages from './pages/admin';

window.api = api;
window.fmt = fmt;

Alpine.store('auth', {
    token: readToken(),
    user: readUser(),
    get isLoggedIn() {
        return Boolean(this.token);
    },
    is(role) {
        return this.user?.role === role;
    },
    isAny(roles) {
        return Array.isArray(roles) && roles.includes(this.user?.role);
    },
    get initials() {
        return fmt.initials(this.user?.name);
    },
    get roleLabel() {
        return fmt.roleLabel(this.user?.role);
    },
    async login(email, password) {
        const res = await api.post('/login', { email, password, device_name: 'web' });
        this.token = res.data.token;
        this.user = res.data.user;
        // Persist as bare token (not JSON-encoded) so axios Authorization stays valid.
        writeToken(this.token);
        writeUser(this.user);
    },
    async logout() {
        try {
            await api.post('/logout');
        } catch (e) {
            /* token may already be invalid */
        }
        this.token = '';
        this.user = null;
        clearAuthStorage();
        window.location.assign('/login');
    },
});

Alpine.store('toast', {
    items: [],
    push(message, type = 'success') {
        const id = Math.random().toString(36).slice(2);
        this.items.push({ id, message, type });
        setTimeout(() => {
            this.items = this.items.filter((t) => t.id !== id);
        }, 4000);
    },
    remove(id) {
        this.items = this.items.filter((t) => t.id !== id);
    },
});

Alpine.store('confirm', {
    open: false,
    title: 'Konfirmasi',
    message: '',
    confirmLabel: 'Konfirmasi',
    danger: true,
    resolve: null,
    ask({ title = 'Konfirmasi', message = 'Yakin melanjutkan?', confirmLabel = 'Konfirmasi', danger = true } = {}) {
        this.title = title;
        this.message = message;
        this.confirmLabel = confirmLabel;
        this.danger = danger;
        this.open = true;
        return new Promise((resolve) => {
            this.resolve = resolve;
        });
    },
    accept() {
        this.open = false;
        this.resolve?.(true);
    },
    cancel() {
        this.open = false;
        this.resolve?.(false);
    },
});

window.toast = (message, type = 'success') => Alpine.store('toast').push(message, type);
window.confirmAction = (opts) => Alpine.store('confirm').ask(opts);

// Allow templates to use `auth.*` instead of `$store.auth.*`
Alpine.magic('auth', () => Alpine.store('auth'));

Alpine.data('appShell', () => appShell());
Alpine.data('dropdown', () => dropdown());
Alpine.data('pagedList', (...args) => pagedList(...args));

const pageFactories = { ...catalogPages, ...transactionPages, ...operationPages, ...adminPages };
for (const [name, factory] of Object.entries(pageFactories)) {
    // Alpine.data("name", (arg) => ...) — first arg is the value from x-data="name(arg)"
    Alpine.data(name, (...args) => factory(...args));
}

window.Alpine = Alpine;
Alpine.start();
