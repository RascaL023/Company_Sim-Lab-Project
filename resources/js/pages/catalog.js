import { api } from '../api';
import { pagedList } from '../components';
import { currentUserId, errorMessage } from '../helpers';

const blankItemForm = {
    category_id: '',
    code: '',
    name: '',
    type: 'alat',
    unit: '',
    stock_quantity: 0,
    minimum_stock: 0,
    location: '',
    manufacturer: '',
    description: '',
    created_by: null,
};

const blankUnitForm = {
    item_id: '',
    serial_number: '',
    asset_tag: '',
    condition: 'baik',
    location_id: '',
    purchase_date: '',
    expiry_date: '',
    last_calibration_date: '',
    next_calibration_date: '',
    notes: '',
};

export function itemsPage() {
    return {
        ...pagedList({ endpoint: '/items', perPage: 15 }),
        filters: { search: '', type: '', category_id: '', low_stock: '', needs_calibration: '', expired: '' },
        categories: [],
        formOpen: false,
        editId: null,
        saving: false,
        errors: {},
        form: { ...blankItemForm },
        async loadCategories() {
            try {
                const res = await api.get('/categories', { params: { per_page: 100 } });
                this.categories = res.data?.data ?? [];
            } catch (e) {
                /* ignore */
            }
        },
        applyFilters() {
            this.query = { ...this.query, ...this.filters, page: 1 };
            this.load();
        },
        resetFilters() {
            this.filters = { search: '', type: '', category_id: '', low_stock: '', needs_calibration: '', expired: '' };
            this.query = { page: 1 };
            this.load();
        },
        openCreate() {
            this.editId = null;
            this.errors = {};
            this.form = { ...blankItemForm, created_by: currentUserId() };
            this.formOpen = true;
        },
        openEdit(item) {
            this.editId = item.id;
            this.errors = {};
            this.form = {
                category_id: item.category?.id ?? '',
                code: item.code ?? '',
                name: item.name ?? '',
                type: item.type ?? 'alat',
                unit: item.unit ?? '',
                stock_quantity: item.stock_quantity ?? 0,
                minimum_stock: item.minimum_stock ?? 0,
                location: item.location ?? '',
                manufacturer: item.manufacturer ?? '',
                description: item.description ?? '',
                created_by: currentUserId(),
            };
            this.formOpen = true;
        },
        async save() {
            this.saving = true;
            this.errors = {};
            try {
                const payload = {
                    category_id: this.form.category_id,
                    code: this.form.code,
                    name: this.form.name,
                    type: this.form.type,
                    unit: this.form.unit,
                    stock_quantity: this.form.stock_quantity,
                    minimum_stock: this.form.minimum_stock,
                    location: this.form.location || null,
                    manufacturer: this.form.manufacturer || null,
                    description: this.form.description || null,
                    created_by: this.form.created_by,
                };
                if (this.editId) {
                    await api.put(`/items/${this.editId}`, payload);
                    toast('Item berhasil diperbarui.');
                } else {
                    await api.post('/items', payload);
                    toast('Item berhasil ditambahkan.');
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
        async remove(item) {
            const ok = await confirmAction({
                title: 'Hapus item',
                message: `Hapus "${item.name}" dari katalog?`,
                confirmLabel: 'Hapus',
            });
            if (!ok) return;
            try {
                await api.delete(`/items/${item.id}`);
                toast('Item dihapus dari katalog.');
                this.load();
            } catch (e) {
                toast(errorMessage(e), 'error');
            }
        },
        init() {
            const params = new URLSearchParams(window.location.search);
            if (params.get('category_id')) {
                this.filters.category_id = params.get('category_id');
                this.query = { ...this.query, category_id: this.filters.category_id, page: 1 };
            }
            this.load();
            this.loadCategories();
        },
    };
}

export function itemDetailPage(opts = {}) {
    const tabEndpoints = {
        units: '/units',
        movements: '/stock-movements',
        usages: '/usages',
        calibrations: '/calibrations',
        maintenances: '/maintenances',
        audit: '/audit-trails',
    };
    return {
        id: opts.id ?? null,
        item: null,
        loading: true,
        error: null,
        tab: 'units',
        tabItems: [],
        tabMeta: null,
        tabLoading: false,
        locations: [],
        unitFormOpen: false,
        unitSaving: false,
        unitErrors: {},
        unitForm: { ...blankUnitForm },
        async loadItem() {
            try {
                const res = await api.get(`/items/${this.id}`);
                this.item = res.data?.data ?? null;
            } catch (e) {
                this.error = errorMessage(e);
            } finally {
                this.loading = false;
            }
        },
        async loadLocations() {
            try {
                const res = await api.get('/locations', { params: { per_page: 100 } });
                this.locations = res.data?.data ?? [];
            } catch (e) {
                /* ignore */
            }
        },
        openUnitCreate() {
            this.unitErrors = {};
            this.unitForm = { ...blankUnitForm, item_id: this.id, condition: 'baik' };
            this.unitFormOpen = true;
        },
        async saveUnit() {
            this.unitSaving = true;
            this.unitErrors = {};
            try {
                await api.post('/item-units', {
                    item_id: this.unitForm.item_id,
                    serial_number: this.unitForm.serial_number,
                    asset_tag: this.unitForm.asset_tag || null,
                    condition: this.unitForm.condition,
                    location_id: this.unitForm.location_id || null,
                    purchase_date: this.unitForm.purchase_date || null,
                    expiry_date: this.unitForm.expiry_date || null,
                    last_calibration_date: this.unitForm.last_calibration_date || null,
                    next_calibration_date: this.unitForm.next_calibration_date || null,
                    notes: this.unitForm.notes || null,
                });
                toast('Unit item ditambahkan.');
                this.unitFormOpen = false;
                await this.loadTab(this.tabMeta?.current_page ?? 1);
            } catch (e) {
                const data = e.response?.data;
                if (data?.errors) this.unitErrors = data.errors;
                toast(errorMessage(e), 'error');
            } finally {
                this.unitSaving = false;
            }
        },
        async loadTab(page = 1) {
            this.tabLoading = true;
            try {
                const res = await api.get(`/items/${this.id}${tabEndpoints[this.tab]}`, { params: { per_page: 10, page } });
                this.tabItems = res.data?.data ?? [];
                this.tabMeta = res.data?.meta ?? null;
            } catch (e) {
                this.tabItems = [];
                this.tabMeta = null;
            } finally {
                this.tabLoading = false;
            }
        },
        setTab(tab) {
            this.tab = tab;
            this.loadTab(1);
        },
        tabPageNumbers() {
            return fmt.pageNumbers(this.tabMeta);
        },
        setTabPage(p) {
            this.loadTab(p);
        },
        async init() {
            await this.loadItem();
            this.tab = this.item?.is_bahan ? 'movements' : 'units';
            this.loadLocations();
            this.loadTab(1);
        },
    };
}

export function categoriesPage() {
    return {
        ...pagedList({ endpoint: '/categories', perPage: 15 }),
        filters: { search: '', type: '' },
        formOpen: false,
        editId: null,
        saving: false,
        errors: {},
        form: { name: '', type: 'alat', description: '' },
        applyFilters() {
            this.query = { ...this.query, ...this.filters, page: 1 };
            this.load();
        },
        resetFilters() {
            this.filters = { search: '', type: '' };
            this.query = { page: 1 };
            this.load();
        },
        openCreate() {
            this.editId = null;
            this.errors = {};
            this.form = { name: '', type: 'alat', description: '' };
            this.formOpen = true;
        },
        openEdit(cat) {
            this.editId = cat.id;
            this.errors = {};
            this.form = { name: cat.name, type: cat.type, description: cat.description ?? '' };
            this.formOpen = true;
        },
        async save() {
            this.saving = true;
            this.errors = {};
            try {
                const payload = {
                    name: this.form.name,
                    type: this.form.type,
                    description: this.form.description || null,
                };
                if (this.editId) {
                    await api.put(`/categories/${this.editId}`, payload);
                    toast('Kategori diperbarui.');
                } else {
                    await api.post('/categories', payload);
                    toast('Kategori ditambahkan.');
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
        async remove(cat) {
            const ok = await confirmAction({ title: 'Hapus kategori', message: `Hapus kategori "${cat.name}"?`, confirmLabel: 'Hapus' });
            if (!ok) return;
            try {
                await api.delete(`/categories/${cat.id}`);
                toast('Kategori dihapus.');
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

export function locationsPage() {
    return {
        ...pagedList({ endpoint: '/locations', perPage: 15 }),
        search: '',
        formOpen: false,
        editId: null,
        saving: false,
        errors: {},
        form: { code: '', name: '', description: '' },
        doSearch() {
            this.query = { ...this.query, search: this.search || undefined, page: 1 };
            this.load();
        },
        clearSearch() {
            this.search = '';
            this.query = { page: 1 };
            this.load();
        },
        openCreate() {
            this.editId = null;
            this.errors = {};
            this.form = { code: '', name: '', description: '' };
            this.formOpen = true;
        },
        openEdit(loc) {
            this.editId = loc.id;
            this.errors = {};
            this.form = { code: loc.code, name: loc.name, description: loc.description ?? '' };
            this.formOpen = true;
        },
        async save() {
            this.saving = true;
            this.errors = {};
            try {
                const payload = {
                    code: this.form.code,
                    name: this.form.name,
                    description: this.form.description || null,
                };
                if (this.editId) {
                    await api.put(`/locations/${this.editId}`, payload);
                    toast('Lokasi diperbarui.');
                } else {
                    await api.post('/locations', payload);
                    toast('Lokasi ditambahkan.');
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
        async remove(loc) {
            const ok = await confirmAction({ title: 'Hapus lokasi', message: `Hapus lokasi "${loc.name}"?`, confirmLabel: 'Hapus' });
            if (!ok) return;
            try {
                await api.delete(`/locations/${loc.id}`);
                toast('Lokasi dihapus.');
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

export function itemUnitsPage() {
    return {
        ...pagedList({ endpoint: '/item-units', perPage: 15 }),
        filters: { condition: '', location_id: '', needs_calibration: '' },
        locations: [],
        itemOptions: [],
        formOpen: false,
        editId: null,
        saving: false,
        errors: {},
        form: { ...blankUnitForm },
        async loadLocations() {
            try {
                const res = await api.get('/locations', { params: { per_page: 100 } });
                this.locations = res.data?.data ?? [];
            } catch (e) {
                /* ignore */
            }
        },
        async loadItemOptions() {
            try {
                const res = await api.get('/items', { params: { per_page: 100, type: 'alat', sort_by: 'name', sort_order: 'asc' } });
                this.itemOptions = res.data?.data ?? [];
            } catch (e) {
                /* ignore */
            }
        },
        applyFilters() {
            this.query = { ...this.query, ...this.filters, page: 1 };
            this.load();
        },
        resetFilters() {
            this.filters = { condition: '', location_id: '', needs_calibration: '' };
            this.query = { page: 1 };
            this.load();
        },
        openCreate() {
            this.editId = null;
            this.errors = {};
            this.form = { ...blankUnitForm };
            this.formOpen = true;
        },
        openEdit(unit) {
            this.editId = unit.id;
            this.errors = {};
            this.form = {
                item_id: unit.item?.id ?? '',
                serial_number: unit.serial_number ?? '',
                asset_tag: unit.asset_tag ?? '',
                condition: unit.condition ?? 'baik',
                location_id: unit.location_id ?? '',
                purchase_date: unit.purchase_date ?? '',
                expiry_date: unit.expiry_date ?? '',
                last_calibration_date: unit.last_calibration_date ?? '',
                next_calibration_date: unit.next_calibration_date ?? '',
                notes: unit.notes ?? '',
            };
            this.formOpen = true;
        },
        async save() {
            this.saving = true;
            this.errors = {};
            try {
                if (this.editId) {
                    await api.patch(`/item-units/${this.editId}`, {
                        condition: this.form.condition,
                        location_id: this.form.location_id || null,
                        notes: this.form.notes || null,
                    });
                    toast('Unit item diperbarui.');
                } else {
                    const payload = {
                        item_id: this.form.item_id,
                        serial_number: this.form.serial_number,
                        asset_tag: this.form.asset_tag || null,
                        condition: this.form.condition,
                        location_id: this.form.location_id || null,
                        purchase_date: this.form.purchase_date || null,
                        expiry_date: this.form.expiry_date || null,
                        last_calibration_date: this.form.last_calibration_date || null,
                        next_calibration_date: this.form.next_calibration_date || null,
                        notes: this.form.notes || null,
                    };
                    await api.post('/item-units', payload);
                    toast('Unit item ditambahkan.');
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
        async remove(unit) {
            const ok = await confirmAction({ title: 'Hapus unit', message: `Hapus unit "${unit.serial_number ?? unit.asset_tag ?? unit.id}"?`, confirmLabel: 'Hapus' });
            if (!ok) return;
            try {
                await api.delete(`/item-units/${unit.id}`);
                toast('Unit dihapus.');
                this.load();
            } catch (e) {
                toast(errorMessage(e), 'error');
            }
        },
        init() {
            const params = new URLSearchParams(window.location.search);
            if (params.get('location_id')) {
                this.filters.location_id = params.get('location_id');
                this.query = { ...this.query, location_id: this.filters.location_id, page: 1 };
            }
            this.load();
            this.loadLocations();
            this.loadItemOptions();
        },
    };
}
