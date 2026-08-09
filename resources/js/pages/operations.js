import { api } from '../api';
import { pagedList } from '../components';
import { errorMessage, downloadBlob } from '../helpers';

export function stockMovementsPage() {
    return {
        ...pagedList({ endpoint: '/stock-movements', perPage: 15 }),
        filters: { type: '', item_id: '' },
        itemOptions: [],
        units: [],
        formOpen: false,
        saving: false,
        errors: {},
        form: { item_id: '', item_unit_id: '', type: 'in_purchase', quantity: '', notes: '' },
        applyFilters() {
            this.query = { ...this.query, ...this.filters, page: 1 };
            this.load();
        },
        resetFilters() {
            this.filters = { type: '', item_id: '' };
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
            this.form = { item_id: '', item_unit_id: '', type: 'in_purchase', quantity: '', notes: '' };
            this.units = [];
            this.formOpen = true;
        },
        async save() {
            this.saving = true;
            this.errors = {};
            try {
                await api.post('/stock-movements', {
                    item_id: this.form.item_id,
                    item_unit_id: this.form.item_unit_id || null,
                    type: this.form.type,
                    quantity: this.form.quantity,
                    notes: this.form.notes || null,
                });
                toast('Mutasi stok dicatat.');
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
        init() {
            this.load();
            this.loadItems();
        },
    };
}

export function calibrationsPage() {
    return {
        ...pagedList({ endpoint: '/calibrations', perPage: 15 }),
        filters: { needs_calibration: '' },
        units: [],
        formOpen: false,
        editId: null,
        saving: false,
        errors: {},
        form: {
            item_unit_id: '',
            calibration_date: '',
            next_calibration_date: '',
            calibrated_by: '',
            certificate_number: '',
            result: 'lulus',
            notes: '',
        },
        applyFilters() {
            this.query = { ...this.query, ...this.filters, page: 1 };
            this.load();
        },
        resetFilters() {
            this.filters = { needs_calibration: '' };
            this.query = { page: 1 };
            this.load();
        },
        async loadUnits() {
            try {
                const res = await api.get('/item-units', { params: { per_page: 100 } });
                this.units = res.data?.data ?? [];
            } catch (e) {
                /* ignore */
            }
        },
        openCreate() {
            this.editId = null;
            this.errors = {};
            this.form = {
                item_unit_id: '',
                calibration_date: '',
                next_calibration_date: '',
                calibrated_by: '',
                certificate_number: '',
                result: 'lulus',
                notes: '',
            };
            this.formOpen = true;
        },
        openEdit(c) {
            this.editId = c.id;
            this.errors = {};
            this.form = {
                item_unit_id: c.item_unit?.id ?? '',
                calibration_date: c.calibration_date ?? '',
                next_calibration_date: c.next_calibration_date ?? '',
                calibrated_by: c.calibrated_by ?? '',
                certificate_number: c.certificate_number ?? '',
                result: c.result ?? 'lulus',
                notes: c.notes ?? '',
            };
            this.formOpen = true;
        },
        async save() {
            this.saving = true;
            this.errors = {};
            try {
                const payload = {
                    item_unit_id: this.form.item_unit_id,
                    calibration_date: this.form.calibration_date,
                    next_calibration_date: this.form.next_calibration_date,
                    calibrated_by: this.form.calibrated_by,
                    certificate_number: this.form.certificate_number || null,
                    result: this.form.result,
                    notes: this.form.notes || null,
                };
                if (this.editId) {
                    await api.put(`/calibrations/${this.editId}`, payload);
                    toast('Data kalibrasi diperbarui.');
                } else {
                    await api.post('/calibrations', payload);
                    toast('Kalibrasi dicatat.');
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
        init() {
            this.load();
            this.loadUnits();
        },
    };
}

export function maintenancesPage() {
    return {
        ...pagedList({ endpoint: '/maintenances', perPage: 15 }),
        filters: { status: '' },
        units: [],
        formOpen: false,
        editId: null,
        saving: false,
        errors: {},
        form: {
            item_unit_id: '',
            maintenance_date: '',
            description: '',
            performed_by: '',
            cost: '',
            status: 'proses',
            notes: '',
        },
        applyFilters() {
            this.query = { ...this.query, ...this.filters, page: 1 };
            this.load();
        },
        resetFilters() {
            this.filters = { status: '' };
            this.query = { page: 1 };
            this.load();
        },
        async loadUnits() {
            try {
                const res = await api.get('/item-units', { params: { per_page: 100 } });
                this.units = res.data?.data ?? [];
            } catch (e) {
                /* ignore */
            }
        },
        openCreate() {
            this.editId = null;
            this.errors = {};
            this.form = {
                item_unit_id: '',
                maintenance_date: '',
                description: '',
                performed_by: '',
                cost: '',
                status: 'proses',
                notes: '',
            };
            this.formOpen = true;
        },
        openEdit(m) {
            this.editId = m.id;
            this.errors = {};
            this.form = {
                item_unit_id: m.item_unit?.id ?? '',
                maintenance_date: m.maintenance_date ?? '',
                description: m.description ?? '',
                performed_by: m.performed_by ?? '',
                cost: m.cost ?? '',
                status: m.status ?? 'proses',
                notes: m.notes ?? '',
            };
            this.formOpen = true;
        },
        async save() {
            this.saving = true;
            this.errors = {};
            try {
                const payload = {
                    item_unit_id: this.form.item_unit_id,
                    maintenance_date: this.form.maintenance_date,
                    description: this.form.description,
                    performed_by: this.form.performed_by,
                    cost: this.form.cost || null,
                    status: this.form.status,
                    notes: this.form.notes || null,
                };
                if (this.editId) {
                    await api.put(`/maintenances/${this.editId}`, payload);
                    toast('Data maintenance diperbarui.');
                } else {
                    await api.post('/maintenances', payload);
                    toast('Maintenance dicatat.');
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
        init() {
            this.load();
            this.loadUnits();
        },
    };
}

export function auditTrailsPage() {
    return {
        ...pagedList({ endpoint: '/audit-trails', perPage: 20 }),
        filters: { action: '' },
        applyFilters() {
            this.query = { ...this.query, ...this.filters, page: 1 };
            this.load();
        },
        resetFilters() {
            this.filters = { action: '' };
            this.query = { page: 1 };
            this.load();
        },
        valuesPreview(values) {
            try {
                const obj = typeof values === 'string' ? JSON.parse(values) : values;
                if (!obj) return null;
                const keys = Object.keys(obj).slice(0, 3);
                return keys.map((k) => `${k}: ${obj[k]}`).join(', ');
            } catch (e) {
                return null;
            }
        },
        init() {
            this.load();
        },
    };
}

export function attachmentsPage() {
    const attachableOptions = [
        { value: 'App\\Models\\Item', label: 'Item (katalog)' },
        { value: 'App\\Models\\ItemUnit', label: 'Item Unit' },
        { value: 'App\\Models\\BorrowingRequest', label: 'Borrowing Request' },
        { value: 'App\\Models\\BorrowingItem', label: 'Borrowing Item' },
        { value: 'App\\Models\\Usage', label: 'Pemakaian' },
    ];
    return {
        ...pagedList({ endpoint: '/attachments', perPage: 15 }),
        attachableOptions,
        uploadOpen: false,
        saving: false,
        errors: {},
        form: { attachable_type: '', attachable_id: '', type: '', description: '', file: null },
        onFile(e) {
            this.form.file = e.target.files?.[0] ?? null;
        },
        openUpload() {
            this.errors = {};
            this.form = { attachable_type: '', attachable_id: '', type: '', description: '', file: null };
            this.uploadOpen = true;
        },
        async upload() {
            if (!this.form.file) {
                toast('Pilih file terlebih dahulu.', 'info');
                return;
            }
            this.saving = true;
            this.errors = {};
            try {
                const fd = new FormData();
                fd.append('attachable_type', this.form.attachable_type);
                fd.append('attachable_id', String(this.form.attachable_id));
                if (this.form.type) fd.append('type', this.form.type);
                if (this.form.description) fd.append('description', this.form.description);
                fd.append('file', this.form.file);
                await api.post('/attachments', fd);
                toast('Lampiran berhasil diunggah.');
                this.uploadOpen = false;
                this.load();
            } catch (e) {
                const data = e.response?.data;
                if (data?.errors) this.errors = data.errors;
                toast(errorMessage(e), 'error');
            } finally {
                this.saving = false;
            }
        },
        async download(a) {
            try {
                const res = await api.get(`/attachments/${a.id}/download`, { responseType: 'blob' });
                downloadBlob(res.data, a.original_filename ?? `lampiran-${a.id}`);
            } catch (e) {
                toast(errorMessage(e), 'error');
            }
        },
        async remove(a) {
            const ok = await confirmAction({ title: 'Hapus lampiran', message: `Hapus "${a.original_filename}"?`, confirmLabel: 'Hapus' });
            if (!ok) return;
            try {
                await api.delete(`/attachments/${a.id}`);
                toast('Lampiran dihapus.');
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

export function reportsPage() {
    return {
        from: '',
        to: '',
        busy: null,
        async download(kind, format) {
            this.busy = `${kind}:${format}`;
            try {
                const params = { format };
                if (kind === 'borrowings') {
                    if (this.from) params.from = this.from;
                    if (this.to) params.to = this.to;
                }
                const res = await api.get(`/reports/${kind}`, { params, responseType: 'blob' });
                const ext = format === 'pdf' ? 'pdf' : 'xlsx';
                downloadBlob(res.data, `laporan-${kind}.${ext}`);
                toast('Laporan berhasil diunduh.');
            } catch (e) {
                let msg = 'Gagal mengunduh laporan.';
                try {
                    if (e.response?.data instanceof Blob) {
                        const text = await e.response.data.text();
                        const parsed = JSON.parse(text);
                        if (parsed?.message) msg = parsed.message;
                    }
                } catch (err) {
                    /* keep default */
                }
                toast(msg, 'error');
            } finally {
                this.busy = null;
            }
        },
    };
}
