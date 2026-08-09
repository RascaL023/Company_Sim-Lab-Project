import { api } from './api';
import { pageNumbers } from './helpers';

export function pagedList(opts = {}) {
    return {
        items: [],
        meta: null,
        links: null,
        loading: true,
        error: null,
        query: { page: 1, ...(opts.query ?? {}) },
        perPage: opts.perPage ?? 15,
        async load() {
            this.loading = true;
            this.error = null;
            try {
                const res = await api.get(opts.endpoint, { params: { ...this.query, per_page: this.perPage } });
                this.items = res.data?.data ?? [];
                this.meta = res.data?.meta ?? null;
                this.links = res.data?.links ?? null;
            } catch (e) {
                this.error = e.response?.data?.message ?? 'Gagal memuat data.';
                this.items = [];
                this.meta = null;
            } finally {
                this.loading = false;
            }
        },
        setPage(page) {
            this.query.page = page;
            this.load();
        },
        pageNumbers() {
            return pageNumbers(this.meta);
        },
        async init() {
            await this.load();
        },
    };
}

export function dropdown() {
    return {
        open: false,
        toggle() {
            this.open = !this.open;
        },
        close() {
            this.open = false;
        },
    };
}

export function appShell() {
    return {
        sidebarOpen: false,
        unread: 0,
        notifs: [],
        async loadUnread() {
            try {
                const res = await api.get('/notifications/unread-count');
                this.unread = res.data?.data?.unread_count ?? 0;
            } catch (e) {
                /* background poll — ignore */
            }
        },
        async toggleNotif() {
            if (!this.notifs.length) {
                try {
                    const res = await api.get('/notifications', { params: { per_page: 6 } });
                    this.notifs = res.data?.data ?? [];
                } catch (e) {
                    /* ignore */
                }
            }
        },
        async markRead(n) {
            if (n.read_at) return;
            try {
                await api.patch(`/notifications/${n.id}/read`);
                n.read_at = new Date().toISOString();
                this.unread = Math.max(0, this.unread - 1);
            } catch (e) {
                /* ignore */
            }
        },
        notifTarget(n) {
            const p = n?.payload ?? {};
            if (p.borrowing_request_id) return `/borrowings/${p.borrowing_request_id}`;
            if (p.usage_id) return `/usages`;
            return '/notifications';
        },
        init() {
            if (!window.localStorage.getItem('simlab.token')) {
                window.location.assign('/login');
                return;
            }
            this.loadUnread();
            setInterval(() => this.loadUnread(), 60000);
        },
    };
}
