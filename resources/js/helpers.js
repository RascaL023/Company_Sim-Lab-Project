const BADGE_COLORS = {
    amber: 'bg-amber-50 text-amber-700 ring-amber-600/20',
    blue: 'bg-sky-50 text-sky-700 ring-sky-600/20',
    rose: 'bg-rose-50 text-rose-700 ring-rose-600/20',
    violet: 'bg-violet-50 text-violet-700 ring-violet-600/20',
    emerald: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    orange: 'bg-orange-50 text-orange-700 ring-orange-600/20',
    gray: 'bg-zinc-100 text-zinc-600 ring-zinc-500/20',
    sky: 'bg-sky-50 text-sky-700 ring-sky-600/20',
};

const STATUS_MAP = {
    // borrowing
    diajukan: 'amber',
    disetujui: 'blue',
    ditolak: 'rose',
    diproses: 'violet',
    selesai: 'emerald',
    batal: 'gray',
    // usage
    dicatat: 'amber',
    diverifikasi: 'emerald',
    // conditions
    baik: 'emerald',
    rusak_ringan: 'amber',
    rusak_berat: 'orange',
    hilang: 'rose',
    dihapus: 'gray',
    // calibration
    lulus: 'emerald',
    tidak_lulus: 'rose',
    // maintenance
    proses: 'blue',
    tertunda: 'amber',
    // movement
    in_purchase: 'emerald',
    in_return: 'emerald',
    in_adjustment: 'emerald',
    out_usage: 'rose',
    out_borrow: 'rose',
    out_disposal: 'rose',
    out_adjustment: 'rose',
    transfer_in: 'sky',
    transfer_out: 'sky',
    // disposal
    diusulkan: 'amber',
    // item type
    alat: 'violet',
    bahan: 'sky',
};

const STATUS_LABEL = {
    diajukan: 'Diajukan',
    disetujui: 'Disetujui',
    ditolak: 'Ditolak',
    diproses: 'Diproses',
    selesai: 'Selesai',
    batal: 'Batal',
    dicatat: 'Dicatat',
    diverifikasi: 'Diverifikasi',
    diusulkan: 'Diusulkan',
    lulus: 'Lulus',
    tidak_lulus: 'Tidak Lulus',
    proses: 'Proses',
    tertunda: 'Tertunda',
    baik: 'Baik',
    rusak_ringan: 'Rusak Ringan',
    rusak_berat: 'Rusak Berat',
    hilang: 'Hilang',
    dihapus: 'Dihapus',
    in_purchase: 'Pembelian',
    in_return: 'Retur Masuk',
    in_adjustment: 'Penyesuaian Masuk',
    out_usage: 'Pemakaian',
    out_borrow: 'Peminjaman',
    out_disposal: 'Disposal',
    out_adjustment: 'Penyesuaian Keluar',
    transfer_in: 'Transfer Masuk',
    transfer_out: 'Transfer Keluar',
};

export function badgeClass(value) {
    const color = STATUS_MAP[value] ?? 'gray';
    return BADGE_COLORS[color];
}

export function statusLabel(value) {
    return STATUS_LABEL[value] ?? value ?? '—';
}

export function roleLabel(role) {
    const map = {
        admin_sistem: 'Admin Sistem',
        laboran: 'Laboran',
        kepala_lab: 'Kepala Lab',
        peminjam: 'Peminjam',
    };
    return map[role] ?? role ?? '—';
}

export function typeLabel(type) {
    return type === 'alat' ? 'Alat' : type === 'bahan' ? 'Bahan' : type ?? '—';
}

export function movementLabel(type) {
    return STATUS_LABEL[type] ?? type ?? '—';
}

export function isIncoming(type) {
    return ['in_purchase', 'in_return', 'in_adjustment', 'transfer_in'].includes(type);
}

export function initials(name) {
    const parts = String(name ?? '?')
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2);
    return parts.map((p) => p[0]).join('').toUpperCase();
}

export function fmtDate(value) {
    if (!value) return '—';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
}

export function fmtDateTime(value) {
    if (!value) return '—';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return value;
    const date = d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
    const time = d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
    return `${date}, ${time}`;
}

export function fmtNum(value) {
    if (value === null || value === undefined || value === '') return '0';
    return new Intl.NumberFormat('id-ID').format(Number(value));
}

export function fmtRupiah(value) {
    if (value === null || value === undefined || value === '') return '—';
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(Number(value));
}

export function ago(value) {
    if (!value) return '';
    const d = new Date(value);
    if (Number.isNaN(d.getTime())) return '';
    const sec = Math.floor((Date.now() - d.getTime()) / 1000);
    if (sec < 60) return 'baru saja';
    const min = Math.floor(sec / 60);
    if (min < 60) return `${min} menit lalu`;
    const h = Math.floor(min / 60);
    if (h < 24) return `${h} jam lalu`;
    const days = Math.floor(h / 24);
    if (days < 7) return `${days} hari lalu`;
    return fmtDate(value);
}

export function currentUser() {
    try {
        return JSON.parse(window.localStorage.getItem('simlab.user')) ?? null;
    } catch (e) {
        return null;
    }
}

export function currentUserId() {
    return currentUser()?.id ?? null;
}

export function errorMessage(err, fallback = 'Terjadi kesalahan. Coba lagi.') {
    const data = err?.response?.data;
    if (!data) return fallback;
    if (typeof data.message === 'string') return data.message;
    if (data.errors) {
        const first = Object.values(data.errors)[0];
        if (Array.isArray(first) && first.length) return first[0];
        if (typeof first === 'string') return first;
    }
    return fallback;
}

export function pageNumbers(meta) {
    if (!meta || !meta.last_page) return [];
    const total = Number(meta.last_page) || 1;
    const current = Number(meta.current_page) || 1;
    const set = new Set([1, total, current - 1, current, current + 1]);
    const list = [...set].filter((p) => p >= 1 && p <= total).sort((a, b) => a - b);
    const out = [];
    let prev = 0;
    for (const p of list) {
        if (p - prev > 1) out.push('…');
        out.push(p);
        prev = p;
    }
    return out;
}

export function modelName(value) {
    return String(value ?? '').split('\\').pop() ?? '—';
}

export function downloadBlob(blob, filename) {
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    a.remove();
    URL.revokeObjectURL(url);
}
