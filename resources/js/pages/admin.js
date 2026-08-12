import { api } from '../api';
import { pagedList } from '../components';
import { currentUser, errorMessage } from '../helpers';

export function dashboardPage() {
    return {
        loading: true,
        error: null,
        stats: {
            items: 0,
            lowStock: 0,
            needsCalibration: 0,
            pendingBorrowings: 0,
            overdueBorrowings: 0,
            unread: 0,
        },
        recentActivities: [],
        recentBorrowings: [],
        recentUsages: [],
        async load() {
            this.loading = true;
            this.error = null;
            try {
                const [items, lowStock, calib, pending, diproses, unread, audits, borrowings, usages] = await Promise.all([
                    api.get('/items', { params: { per_page: 1 } }),
                    api.get('/items', { params: { low_stock: true, per_page: 1 } }),
                    api.get('/items', { params: { needs_calibration: true, per_page: 1 } }),
                    api.get('/borrowing-requests', { params: { status: 'diajukan', per_page: 1 } }),
                    api.get('/borrowing-requests', { params: { status: 'diproses', per_page: 100 } }),
                    api.get('/notifications/unread-count'),
                    api.get('/audit-trails/recent', { params: { limit: 8 } }),
                    api.get('/borrowing-requests', { params: { per_page: 5 } }),
                    api.get('/usages', { params: { per_page: 5 } }),
                ]);

                const overdue = (diproses.data?.data ?? []).filter((b) =>
                    (b.items ?? []).some(
                        (i) => i.expected_return_date && !i.actual_return_date && new Date(i.expected_return_date) < new Date()
                    )
                ).length;

                this.stats = {
                    items: items.data?.meta?.total ?? 0,
                    lowStock: lowStock.data?.meta?.total ?? 0,
                    needsCalibration: calib.data?.meta?.total ?? 0,
                    pendingBorrowings: pending.data?.meta?.total ?? 0,
                    overdueBorrowings: overdue,
                    unread: unread.data?.data?.unread_count ?? 0,
                };
                this.recentActivities = audits.data?.data ?? [];
                this.recentBorrowings = borrowings.data?.data ?? [];
                this.recentUsages = usages.data?.data ?? [];
            } catch (e) {
                this.error = errorMessage(e);
            } finally {
                this.loading = false;
            }
        },
        itemQuantity(req) {
            return (req.items ?? []).reduce((s, i) => s + Number(i.quantity ?? 0), 0);
        },
        init() {
            this.load();
        },
    };
}

export function usersPage() {
    return {
        ...pagedList({ endpoint: '/users', perPage: 15 }),
        filters: { role: '', is_active: '', search: '' },
        formOpen: false,
        editId: null,
        saving: false,
        errors: {},
        form: { name: '', email: '', password: '', role: 'peminjam', phone: '', is_active: true },
        applyFilters() {
            this.query = { ...this.query, ...this.filters, page: 1 };
            this.load();
        },
        resetFilters() {
            this.filters = { role: '', is_active: '', search: '' };
            this.query = { page: 1 };
            this.load();
        },
        openCreate() {
            this.editId = null;
            this.errors = {};
            this.form = { name: '', email: '', password: '', role: 'peminjam', phone: '', is_active: true };
            this.formOpen = true;
        },
        openEdit(u) {
            this.editId = u.id;
            this.errors = {};
            this.form = {
                name: u.name ?? '',
                email: u.email ?? '',
                password: '',
                role: u.role ?? 'peminjam',
                phone: u.phone ?? '',
                is_active: Boolean(u.is_active),
            };
            this.formOpen = true;
        },
        async save() {
            this.saving = true;
            this.errors = {};
            try {
                const payload = {
                    name: this.form.name,
                    email: this.form.email,
                    role: this.form.role,
                    phone: this.form.phone || null,
                    is_active: this.form.is_active,
                };
                if (this.editId) {
                    if (this.form.password) payload.password = this.form.password;
                    await api.patch(`/users/${this.editId}`, payload);
                    toast('Pengguna diperbarui.');
                } else {
                    payload.password = this.form.password;
                    await api.post('/users', payload);
                    toast('Pengguna ditambahkan.');
                }
                this.formOpen = false;
                this.load();
            } catch (e) {
                const data = e.response?.data;
                if (data?.errors) this.errors = data.errors;
                toast(errorMessage(e), 'error');
            } finally {
                this.saving = false;
            }
        },
        async toggleActive(u) {
            try {
                await api.patch(`/users/${u.id}`, { is_active: !u.is_active });
                u.is_active = !u.is_active;
                toast(u.is_active ? 'Pengguna diaktifkan.' : 'Pengguna dinonaktifkan.');
            } catch (e) {
                toast(errorMessage(e), 'error');
            }
        },
        async remove(u) {
            if (currentUser()?.id === u.id) {
                toast('Tidak bisa menghapus akun sendiri.', 'info');
                return;
            }
            const ok = await confirmAction({ title: 'Hapus pengguna', message: `Hapus pengguna "${u.name}"?`, confirmLabel: 'Hapus' });
            if (!ok) return;
            try {
                await api.delete(`/users/${u.id}`);
                toast('Pengguna dihapus.');
                this.load();
            } catch (e) {
                toast(errorMessage(e), 'error');
            }
        },
        init() {
            this.load();
        },
    };
}

export function notificationsPage() {
    return {
        ...pagedList({ endpoint: '/notifications', perPage: 15 }),
        async markRead(n) {
            if (n.read_at) return;
            try {
                await api.patch(`/notifications/${n.id}/read`);
                n.read_at = new Date().toISOString();
                window.dispatchEvent(new CustomEvent('simlab:unread-refresh'));
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
            this.load();
        },
    };
}

export function loginPage() {
    return {
        email: '',
        password: '',
        error: null,
        errors: {},
        busy: false,
        quickLogin: [
            { label: 'Admin Sistem', email: 'admin.sistem@wiralab.com' },
            { label: 'Laboran', email: 'laboran@wiralab.com' },
            { label: 'Kepala Lab', email: 'kepala.lab@wiralab.com' },
            { label: 'Peminjam', email: 'peminjam@wiralab.com' },
        ],
        async submit() {
            this.busy = true;
            this.error = null;
            this.errors = {};
            try {
                await window.Alpine.store('auth').login(this.email, this.password);
                window.location.assign('/dashboard');
            } catch (e) {
                const status = e.response?.status;
                if (status === 429) {
                    this.error = 'Terlalu banyak percobaan. Coba lagi sebentar lagi.';
                } else if (status === 403) {
                    this.error = e.response?.data?.message ?? 'Akun tidak aktif.';
                } else {
                    const data = e.response?.data;
                    if (data?.errors) this.errors = data.errors;
                    this.error = data?.message ?? 'Gagal masuk. Periksa kembali email dan password.';
                }
            } finally {
                this.busy = false;
            }
        },
        async quick(email) {
            this.email = email;
            this.password = 'password';
            await this.submit();
        },
        init() {
            if (window.Alpine.store('auth').isLoggedIn) {
                window.location.assign('/dashboard');
            }
        },
    };
}
