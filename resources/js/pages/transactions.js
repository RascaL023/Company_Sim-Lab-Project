import { api } from '../api';
import { pagedList } from '../components';
import { currentUserId, errorMessage } from '../helpers';

function borrowingActions() {
    return {
        busy: false,
        rejectOpen: false,
        rejectTarget: null,
        rejectionReason: '',
        checkoutOpen: false,
        checkoutTarget: null,
        expectedReturnDate: '',
        returnOpen: false,
        returnTarget: null,
        returnForm: { condition_after: 'baik', is_damaged: false, damage_notes: '', check_notes: '' },
        async approve(req) {
            const ok = await confirmAction({
                title: 'Setujui peminjaman',
                message: `Setujui permintaan ${req.request_number}?`,
                confirmLabel: 'Setujui',
                danger: false,
            });
            if (!ok) return;
            this.busy = true;
            try {
                await api.patch(`/borrowing-requests/${req.id}/approve`);
                toast('Peminjaman disetujui.');
                await this.load();
            } catch (e) {
                toast(errorMessage(e), 'error');
            } finally {
                this.busy = false;
            }
        },
        openReject(req) {
            this.rejectTarget = req;
            this.rejectionReason = '';
            this.rejectOpen = true;
        },
        async reject() {
            if (!this.rejectionReason.trim()) return;
            this.busy = true;
            try {
                await api.patch(`/borrowing-requests/${this.rejectTarget.id}/reject`, { rejection_reason: this.rejectionReason });
                toast('Peminjaman ditolak.');
                this.rejectOpen = false;
                await this.load();
            } catch (e) {
                toast(errorMessage(e), 'error');
            } finally {
                this.busy = false;
            }
        },
        async cancel(req) {
            const ok = await confirmAction({
                title: 'Batalkan peminjaman',
                message: `Batalkan permintaan ${req.request_number}?`,
                confirmLabel: 'Batalkan',
            });
            if (!ok) return;
            try {
                await api.patch(`/borrowing-requests/${req.id}/cancel`);
                toast('Peminjaman dibatalkan.');
                await this.load();
            } catch (e) {
                toast(errorMessage(e), 'error');
            }
        },
        openCheckout(bi) {
            this.checkoutTarget = bi;
            this.expectedReturnDate = '';
            this.checkoutOpen = true;
        },
        async doCheckout() {
            this.busy = true;
            try {
                await api.patch(`/borrowing-items/${this.checkoutTarget.id}/checkout`, { expected_return_date: this.expectedReturnDate });
                toast('Item berhasil di-checkout.');
                this.checkoutOpen = false;
                await this.load();
            } catch (e) {
                toast(errorMessage(e), 'error');
            } finally {
                this.busy = false;
            }
        },
        openReturn(bi) {
            this.returnTarget = bi;
            this.returnForm = { condition_after: 'baik', is_damaged: false, damage_notes: '', check_notes: '' };
            this.returnOpen = true;
        },
        async doReturn() {
            this.busy = true;
            try {
                await api.patch(`/borrowing-items/${this.returnTarget.id}/return`, {
                    condition_after: this.returnForm.condition_after,
                    is_damaged: this.returnForm.is_damaged,
                    check_notes: this.returnForm.check_notes || null,
                    damage_notes: this.returnForm.is_damaged ? this.returnForm.damage_notes : null,
                });
                toast('Item berhasil dikembalikan.');
                this.returnOpen = false;
                await this.load();
            } catch (e) {
                toast(errorMessage(e), 'error');
            } finally {
                this.busy = false;
            }
        },
    };
}

export function borrowingsPage() {
    return {
        ...pagedList({ endpoint: '/borrowing-requests', perPage: 15 }),
        ...borrowingActions(),
        filters: { status: '' },
        drawerOpen: false,
        expanded: null,
        applyFilters() {
            this.query = { ...this.query, ...this.filters, page: 1 };
            this.load();
        },
        resetFilters() {
            this.filters = { status: '' };
            this.query = { page: 1 };
            this.load();
        },
        openDrawer(req) {
            this.expanded = req;
            this.drawerOpen = true;
        },
        itemQuantity(req) {
            return (req.items ?? []).reduce((s, i) => s + Number(i.quantity ?? 0), 0);
        },
        isCancellable(req) {
            return ['diajukan', 'disetujui'].includes(req.status);
        },
        init() {
            this.load();
        },
    };
}

export function borrowingCreatePage() {
    return {
        items: [],
        itemsLoading: true,
        purpose: '',
        selected: [],
        submitting: false,
        errors: {},
        async loadItems() {
            try {
                const res = await api.get('/items', { params: { per_page: 100, sort_by: 'name', sort_order: 'asc' } });
                this.items = res.data?.data ?? [];
            } catch (e) {
                toast(errorMessage(e), 'error');
            } finally {
                this.itemsLoading = false;
            }
        },
        addItem(itemId) {
            if (!itemId) return;
            const id = Number(itemId);
            if (this.selected.some((l) => l.item_id === id)) {
                toast('Item sudah ada di daftar.', 'info');
                return;
            }
            const item = this.items.find((i) => i.id === id);
            if (!item) return;
            this.selected.push({ item_id: id, item, quantity: 1 });
        },
        removeLine(index) {
            this.selected.splice(index, 1);
        },
        totalQuantity() {
            return this.selected.reduce((s, l) => s + Number(l.quantity || 0), 0);
        },
        async submit() {
            if (!this.selected.length) {
                toast('Tambahkan minimal satu item.', 'info');
                return;
            }
            this.submitting = true;
            this.errors = {};
            try {
                await api.post('/borrowing-requests', {
                    requested_by: currentUserId(),
                    purpose: this.purpose,
                    items: this.selected.map((l) => ({ item_id: l.item_id, quantity: l.quantity })),
                });
                toast('Pengajuan peminjaman terkirim.');
                window.location.assign('/borrowings');
            } catch (e) {
                const data = e.response?.data;
                if (data?.errors) this.errors = data.errors;
                toast(errorMessage(e), 'error');
            } finally {
                this.submitting = false;
            }
        },
        init() {
            this.loadItems();
        },
    };
}

export function borrowingDetailPage(opts = {}) {
    return {
        id: opts.id ?? null,
        request: null,
        loading: true,
        error: null,
        ...borrowingActions(),
        async load() {
            this.loading = true;
            this.error = null;
            try {
                const res = await api.get(`/borrowing-requests/${this.id}`);
                this.request = res.data?.data ?? null;
                await this.loadItems();
            } catch (e) {
                this.error = errorMessage(e);
            } finally {
                this.loading = false;
            }
        },
        async loadItems() {
            try {
                const res = await api.get('/borrowing-requests', { params: { status: this.request?.status, per_page: 200 } });
                const found = (res.data?.data ?? []).find((r) => r.id === this.id);
                if (found) this.request = { ...this.request, ...found };
            } catch (e) {
                /* keep header-only view */
            }
        },
        itemQuantity() {
            return (this.request?.items ?? []).reduce((s, i) => s + Number(i.quantity ?? 0), 0);
        },
        isCancellable() {
            return this.request && ['diajukan', 'disetujui'].includes(this.request.status);
        },
        init() {
            this.load();
        },
    };
}

export function usagesPage() {
    return {
        ...pagedList({ endpoint: '/usages', perPage: 15 }),
        filters: { status: '', item_id: '' },
        itemOptions: [],
        formOpen: false,
        saving: false,
        errors: {},
        form: { item_id: '', item_unit_id: '', quantity_used: '', purpose: '' },
        units: [],
        rejectOpen: false,
        rejectTarget: null,
        rejectionReason: '',
        applyFilters() {
            this.query = { ...this.query, ...this.filters, page: 1 };
            this.load();
        },
        resetFilters() {
            this.filters = { status: '', item_id: '' };
            this.query = { page: 1 };
            this.load();
        },
        async loadItems() {
            try {
                const res = await api.get('/items', { params: { per_page: 100, sort_by: 'name', sort_order: 'asc' } });
                this.itemOptions = res.data?.data ?? [];
            } catch (e) {
                /* ignore */
            }
        },
        async onItemChange() {
            this.form.item_unit_id = '';
            this.units = [];
            if (!this.form.item_id) return;
            try {
                const res = await api.get(`/items/${this.form.item_id}/units`, { params: { per_page: 100 } });
                this.units = res.data?.data ?? [];
            } catch (e) {
                /* ignore */
            }
        },
        openCreate() {
            this.errors = {};
            this.form = { item_id: '', item_unit_id: '', quantity_used: '', purpose: '' };
            this.units = [];
            this.formOpen = true;
        },
        async save() {
            this.saving = true;
            this.errors = {};
            try {
                await api.post('/usages', {
                    item_id: this.form.item_id,
                    item_unit_id: this.form.item_unit_id || null,
                    quantity_used: this.form.quantity_used,
                    purpose: this.form.purpose,
                });
                toast('Pemakaian dicatat.');
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
        async verify(u) {
            const ok = await confirmAction({ title: 'Verifikasi pemakaian', message: 'Verifikasi catatan pemakaian ini?', confirmLabel: 'Verifikasi', danger: false });
            if (!ok) return;
            try {
                await api.patch(`/usages/${u.id}/verify`);
                toast('Pemakaian diverifikasi.');
                this.load();
            } catch (e) {
                toast(errorMessage(e), 'error');
            }
        },
        openReject(u) {
            this.rejectTarget = u;
            this.rejectionReason = '';
            this.rejectOpen = true;
        },
        async reject() {
            if (!this.rejectionReason.trim()) return;
            try {
                await api.patch(`/usages/${this.rejectTarget.id}/reject`, { rejection_reason: this.rejectionReason });
                toast('Pemakaian ditolak.');
                this.rejectOpen = false;
                this.load();
            } catch (e) {
                toast(errorMessage(e), 'error');
            }
        },
        init() {
            this.load();
            this.loadItems();
        },
    };
}

export function stockOpnamePage() {
    return {
        items: [],
        loading: true,
        notes: '',
        lines: [],
        submitting: false,
        result: null,
        errors: {},
        async loadItems() {
            try {
                const res = await api.get('/items', { params: { per_page: 100, sort_by: 'name', sort_order: 'asc' } });
                this.items = res.data?.data ?? [];
            } catch (e) {
                toast(errorMessage(e), 'error');
            } finally {
                this.loading = false;
            }
        },
        addItem(itemId) {
            if (!itemId) return;
            const id = Number(itemId);
            if (this.lines.some((l) => l.item_id === id)) {
                toast('Item sudah ada di sesi opname.', 'info');
                return;
            }
            const item = this.items.find((i) => i.id === id);
            if (!item) return;
            this.lines.push({ item_id: id, item, counted_quantity: Number(item.stock_quantity ?? 0) });
        },
        removeLine(index) {
            this.lines.splice(index, 1);
        },
        diff(line) {
            return Number(line.counted_quantity || 0) - Number(line.item.stock_quantity || 0);
        },
        async submit() {
            if (!this.lines.length) {
                toast('Tambahkan minimal satu item.', 'info');
                return;
            }
            this.submitting = true;
            this.errors = {};
            try {
                const res = await api.post('/stock-opname', {
                    notes: this.notes || null,
                    items: this.lines.map((l) => ({ item_id: l.item_id, counted_quantity: l.counted_quantity })),
                });
                this.result = res.data?.data ?? null;
                this.lines = [];
                this.notes = '';
                toast('Stock opname selesai dijalankan.');
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } catch (e) {
                const data = e.response?.data;
                if (data?.errors) this.errors = data.errors;
                toast(errorMessage(e), 'error');
            } finally {
                this.submitting = false;
            }
        },
        init() {
            this.loadItems();
        },
    };
}

export function disposalsPage() {
    return {
        targetType: 'alat',
        items: [],
        units: [],
        loading: true,
        form: { item_unit_id: '', item_id: '', reason: 'rusak_total', notes: '' },
        submitting: false,
        errors: {},
        async loadOptions() {
            try {
                const [itemsRes, unitsRes] = await Promise.all([
                    api.get('/items', { params: { per_page: 100, sort_by: 'name', sort_order: 'asc' } }),
                    api.get('/item-units', { params: { per_page: 100 } }),
                ]);
                this.items = (itemsRes.data?.data ?? []).filter((i) => i.type === 'bahan');
                this.units = unitsRes.data?.data ?? [];
            } catch (e) {
                toast(errorMessage(e), 'error');
            } finally {
                this.loading = false;
            }
        },
        async submit() {
            this.submitting = true;
            this.errors = {};
            try {
                const payload = { reason: this.form.reason, notes: this.form.notes || null };
                if (this.targetType === 'alat') {
                    payload.item_unit_id = this.form.item_unit_id;
                } else {
                    payload.item_id = this.form.item_id;
                }
                await api.post('/asset-disposals', payload);
                toast('Usulan disposal berhasil diajukan.');
                this.form = { item_unit_id: '', item_id: '', reason: 'rusak_total', notes: '' };
            } catch (e) {
                const data = e.response?.data;
                if (data?.errors) this.errors = data.errors;
                toast(errorMessage(e), 'error');
            } finally {
                this.submitting = false;
            }
        },
        init() {
            this.loadOptions();
        },
    };
}
