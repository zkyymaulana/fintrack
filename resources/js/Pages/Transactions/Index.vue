<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowDownLeft, ArrowLeftRight, ArrowUpRight, LoaderCircle, Pencil, Plus, ReceiptText, Search, Trash2 } from '@lucide/vue';
import axios from 'axios';
import { onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import type { Category, Option, Transaction } from '../../types/finance';
import { rupiah, tanggal, typeLabel } from '../../utils/finance';

const props = defineProps<{
    transactions: { data: Transaction[]; current_page: number; last_page: number; total: number };
    filters: { search?: string; type?: string; month?: string; wallet_id?: number; category_id?: number };
    wallets: Option[];
    categories: Category[];
}>();
const fields = reactive({ search: props.filters.search ?? '', type: props.filters.type ?? '', month: props.filters.month ?? '', wallet_id: String(props.filters.wallet_id ?? ''), category_id: String(props.filters.category_id ?? '') });
const pending = ref<number | null>(null);
const transactions = ref([...props.transactions.data]);
const currentPage = ref(props.transactions.current_page);
const lastPage = ref(props.transactions.last_page);
const total = ref(props.transactions.total);
const loadingMore = ref(false);
const loadError = ref('');
const loadMoreTarget = ref<HTMLElement | null>(null);
let observer: IntersectionObserver | null = null;
const icon = { income: ArrowDownLeft, expense: ArrowUpRight, transfer: ArrowLeftRight };
watch(() => props.transactions, (page) => {
    transactions.value = [...page.data];
    currentPage.value = page.current_page;
    lastPage.value = page.last_page;
    total.value = page.total;
    loadError.value = '';
});
async function loadMore(retry = false) {
    if (loadingMore.value || currentPage.value >= lastPage.value || (loadError.value && !retry)) return;
    loadingMore.value = true;
    loadError.value = '';
    const params = Object.fromEntries(Object.entries(fields).filter(([, value]) => value !== ''));
    try {
        const { data } = await axios.get('/transactions', {
            params: { ...params, page: currentPage.value + 1 },
            headers: { Accept: 'application/json' },
        });
        transactions.value.push(...data.data);
        currentPage.value = data.current_page;
        lastPage.value = data.last_page;
        total.value = data.total;
    } catch {
        loadError.value = 'Transaksi berikutnya gagal dimuat.';
    } finally {
        loadingMore.value = false;
    }
}
onMounted(() => {
    observer = new IntersectionObserver((entries) => {
        if (entries.some((entry) => entry.isIntersecting)) void loadMore();
    }, { rootMargin: '320px' });
    if (loadMoreTarget.value) observer.observe(loadMoreTarget.value);
});
watch(loadMoreTarget, (target, previous) => {
    if (previous) observer?.unobserve(previous);
    if (target) observer?.observe(target);
});
onBeforeUnmount(() => observer?.disconnect());
function filter() {
    router.get('/transactions', Object.fromEntries(Object.entries(fields).filter(([, value]) => value !== '')), { preserveState: true, replace: true });
}
function clear() {
    Object.assign(fields, { search: '', type: '', month: '', wallet_id: '', category_id: '' });
    router.get('/transactions', {}, { preserveState: true, replace: true });
}
function remove(transaction: Transaction) {
    if (!window.confirm(`Hapus transaksi “${transaction.title}”? Saldo dompet akan dikembalikan.`)) return;
    pending.value = transaction.id;
    router.delete(`/transactions/${transaction.id}`, { onFinish: () => pending.value = null });
}
</script>

<template>
    <Head title="Transaksi" />
    <AppLayout title="Transaksi" subtitle="Telusuri dan kelola semua pemasukan, pengeluaran, serta transfermu.">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3"><p class="text-sm text-[#728478]">{{ total }} transaksi ditemukan</p><Link href="/transactions/create" class="primary-button"><Plus :size="18" aria-hidden="true" /> Tambah transaksi</Link></div>
        <form class="surface-shadow rounded-2xl border border-[#e3ebe3] bg-white p-4 sm:p-5" @submit.prevent="filter">
            <div class="grid gap-2.5 sm:grid-cols-2 xl:grid-cols-5">
                <div class="sm:col-span-2 xl:col-span-1"><label for="search" class="mb-2 block text-xs font-bold text-[#61766b]">Cari transaksi</label><input id="search" v-model="fields.search" class="field" type="search" placeholder="Judul atau catatan" /></div>
                <div><label for="type" class="mb-2 block text-xs font-bold text-[#61766b]">Jenis</label><select id="type" v-model="fields.type" class="field"><option value="">Semua jenis</option><option value="income">Pemasukan</option><option value="expense">Pengeluaran</option><option value="transfer">Transfer</option></select></div>
                <div><label for="month" class="mb-2 block text-xs font-bold text-[#61766b]">Bulan</label><input id="month" v-model="fields.month" class="field" type="month" /></div>
                <div><label for="wallet" class="mb-2 block text-xs font-bold text-[#61766b]">Dompet</label><select id="wallet" v-model="fields.wallet_id" class="field"><option value="">Semua dompet</option><option v-for="wallet in wallets" :key="wallet.id" :value="String(wallet.id)">{{ wallet.name }}{{ wallet.wallet_type === 'investment' ? ' · Investasi' : '' }}</option></select></div>
                <div><label for="category" class="mb-2 block text-xs font-bold text-[#61766b]">Kategori</label><select id="category" v-model="fields.category_id" class="field"><option value="">Semua kategori</option><option v-for="category in categories" :key="category.id" :value="String(category.id)">{{ category.name }} · {{ typeLabel(category.type) }}</option></select></div>
            </div>
            <div class="mt-4 flex flex-wrap gap-3"><button class="primary-button" type="submit"><Search :size="17" aria-hidden="true" /> Terapkan filter</button><button class="inline-flex min-h-12 items-center rounded-xl px-4 text-sm font-bold text-[#61766b] hover:bg-[#f3f6f2]" type="button" @click="clear">Reset</button></div>
        </form>

        <section class="mt-4 overflow-hidden rounded-2xl border border-[#e3ebe3] bg-white" aria-label="Daftar transaksi">
            <div v-if="transactions.length" class="divide-y divide-[#edf0eb]">
                <article v-for="transaction in transactions" :key="transaction.id" class="flex flex-wrap items-center gap-3 p-4 sm:flex-nowrap sm:gap-4 sm:px-5">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl" :class="transaction.type === 'income' ? 'bg-[#e9f5ee] text-[#247358]' : transaction.type === 'expense' ? 'bg-[#fff0e9] text-[#bf6350]' : 'bg-[#edf2fc] text-[#5974af]'"><component :is="icon[transaction.type]" :size="19" aria-hidden="true" /></span>
                    <div class="min-w-0 flex-1"><h2 class="truncate font-bold text-[#294538]">{{ transaction.title }}</h2><p class="mt-1 truncate text-xs text-[#829186]">{{ tanggal(transaction.date) }} · {{ transaction.category_name ?? 'Tanpa kategori' }} · {{ transaction.wallet_name ?? 'Dompet tidak tersedia' }}<template v-if="transaction.to_wallet_name"> → {{ transaction.to_wallet_name }}</template></p><p v-if="transaction.note" class="mt-1 truncate text-xs text-[#829186]">{{ transaction.note }}</p></div>
                    <div class="w-full pl-14 sm:w-auto sm:pl-0 sm:text-right"><p class="font-bold" :class="transaction.type === 'income' ? 'text-[#247358]' : transaction.type === 'expense' ? 'text-[#aa5344]' : 'text-[#5974af]'">{{ transaction.type === 'income' ? '+' : transaction.type === 'expense' ? '−' : '' }}{{ rupiah(transaction.amount) }}</p><p class="mt-1 text-xs text-[#829186]">{{ typeLabel(transaction.type) }}<template v-if="Number(transaction.admin_fee)"> · Admin {{ rupiah(transaction.admin_fee) }}</template></p></div>
                    <div class="ml-auto flex gap-1 sm:ml-2"><Link :href="`/transactions/${transaction.id}/edit`" class="flex h-10 w-10 items-center justify-center rounded-xl text-[#256958] hover:bg-[#edf5ee]" :aria-label="`Ubah ${transaction.title}`"><Pencil :size="16" aria-hidden="true" /></Link><button class="flex h-10 w-10 items-center justify-center rounded-xl text-[#ad5147] hover:bg-[#fff0ed] disabled:opacity-50" type="button" :disabled="pending === transaction.id" :aria-label="`Hapus ${transaction.title}`" @click="remove(transaction)"><Trash2 :size="16" aria-hidden="true" /></button></div>
                </article>
            </div>
            <div v-else class="px-6 py-14 text-center"><ReceiptText :size="34" class="mx-auto text-[#8caf97]" aria-hidden="true" /><h2 class="mt-4 font-bold text-[#294538]">Belum ada transaksi yang cocok</h2><p class="mt-2 text-sm text-[#7a8b80]">Ubah filter atau catat transaksi baru.</p></div>
        </section>
        <div v-if="transactions.length && currentPage < lastPage" ref="loadMoreTarget" class="mt-5 flex min-h-12 items-center justify-center" aria-live="polite">
            <LoaderCircle v-if="loadingMore" :size="20" class="animate-spin text-[#256958]" aria-label="Memuat transaksi" />
            <button v-else-if="loadError" type="button" class="text-sm font-semibold text-[#ad5147] underline" @click="loadMore(true)">{{ loadError }} Coba lagi</button>
            <span v-else class="text-xs text-[#829186]">Gulir untuk memuat transaksi lainnya</span>
        </div>
    </AppLayout>
</template>
