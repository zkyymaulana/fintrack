<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowDownLeft, ArrowLeftRight, ArrowUpRight, ChevronLeft, ChevronRight, Pencil, Plus, ReceiptText, Search, Trash2 } from '@lucide/vue';
import { reactive, ref } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import type { Category, Option, Transaction } from '../../types/finance';
import { rupiah, tanggal, typeLabel } from '../../utils/finance';

const props = defineProps<{
    transactions: { data: Transaction[]; current_page: number; last_page: number; total: number; prev_page_url: string | null; next_page_url: string | null };
    filters: { search?: string; type?: string; month?: string; wallet_id?: number; category_id?: number };
    wallets: Option[];
    categories: Category[];
}>();
const fields = reactive({ search: props.filters.search ?? '', type: props.filters.type ?? '', month: props.filters.month ?? '', wallet_id: String(props.filters.wallet_id ?? ''), category_id: String(props.filters.category_id ?? '') });
const pending = ref<number | null>(null);
const icon = { income: ArrowDownLeft, expense: ArrowUpRight, transfer: ArrowLeftRight };
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
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3"><p class="text-sm text-[#728478]">{{ transactions.total }} transaksi ditemukan</p><Link href="/transactions/create" class="primary-button"><Plus :size="18" aria-hidden="true" /> Tambah transaksi</Link></div>
        <form class="surface-shadow rounded-3xl border border-[#e3ebe3] bg-white p-5 sm:p-6" @submit.prevent="filter">
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                <div class="sm:col-span-2 xl:col-span-1"><label for="search" class="mb-2 block text-xs font-bold text-[#61766b]">Cari transaksi</label><input id="search" v-model="fields.search" class="field" type="search" placeholder="Judul atau catatan" /></div>
                <div><label for="type" class="mb-2 block text-xs font-bold text-[#61766b]">Jenis</label><select id="type" v-model="fields.type" class="field"><option value="">Semua jenis</option><option value="income">Pemasukan</option><option value="expense">Pengeluaran</option><option value="transfer">Transfer</option></select></div>
                <div><label for="month" class="mb-2 block text-xs font-bold text-[#61766b]">Bulan</label><input id="month" v-model="fields.month" class="field" type="month" /></div>
                <div><label for="wallet" class="mb-2 block text-xs font-bold text-[#61766b]">Dompet</label><select id="wallet" v-model="fields.wallet_id" class="field"><option value="">Semua dompet</option><option v-for="wallet in wallets" :key="wallet.id" :value="String(wallet.id)">{{ wallet.name }}</option></select></div>
                <div><label for="category" class="mb-2 block text-xs font-bold text-[#61766b]">Kategori</label><select id="category" v-model="fields.category_id" class="field"><option value="">Semua kategori</option><option v-for="category in categories" :key="category.id" :value="String(category.id)">{{ category.name }} · {{ typeLabel(category.type) }}</option></select></div>
            </div>
            <div class="mt-4 flex flex-wrap gap-3"><button class="primary-button" type="submit"><Search :size="17" aria-hidden="true" /> Terapkan filter</button><button class="inline-flex min-h-12 items-center rounded-xl px-4 text-sm font-bold text-[#61766b] hover:bg-[#f3f6f2]" type="button" @click="clear">Reset</button></div>
        </form>

        <section class="mt-6 overflow-hidden rounded-3xl border border-[#e3ebe3] bg-white" aria-label="Daftar transaksi">
            <div v-if="transactions.data.length" class="divide-y divide-[#edf0eb]">
                <article v-for="transaction in transactions.data" :key="transaction.id" class="flex flex-wrap items-center gap-3 p-5 sm:flex-nowrap sm:gap-5 sm:px-7">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl" :class="transaction.type === 'income' ? 'bg-[#e9f5ee] text-[#247358]' : transaction.type === 'expense' ? 'bg-[#fff0e9] text-[#bf6350]' : 'bg-[#edf2fc] text-[#5974af]'"><component :is="icon[transaction.type]" :size="20" aria-hidden="true" /></span>
                    <div class="min-w-0 flex-1"><h2 class="truncate font-bold text-[#294538]">{{ transaction.title }}</h2><p class="mt-1 truncate text-xs text-[#829186]">{{ tanggal(transaction.date) }} · {{ transaction.category_name ?? 'Tanpa kategori' }} · {{ transaction.wallet_name ?? 'Dompet tidak tersedia' }}<template v-if="transaction.to_wallet_name"> → {{ transaction.to_wallet_name }}</template></p><p v-if="transaction.note" class="mt-1 truncate text-xs text-[#829186]">{{ transaction.note }}</p></div>
                    <div class="w-full pl-14 sm:w-auto sm:pl-0 sm:text-right"><p class="font-bold" :class="transaction.type === 'income' ? 'text-[#247358]' : transaction.type === 'expense' ? 'text-[#aa5344]' : 'text-[#5974af]'">{{ transaction.type === 'income' ? '+' : transaction.type === 'expense' ? '−' : '' }}{{ rupiah(transaction.amount) }}</p><p class="mt-1 text-xs text-[#829186]">{{ typeLabel(transaction.type) }}<template v-if="Number(transaction.admin_fee)"> · Admin {{ rupiah(transaction.admin_fee) }}</template></p></div>
                    <div class="ml-auto flex gap-1 sm:ml-2"><Link :href="`/transactions/${transaction.id}/edit`" class="flex h-11 w-11 items-center justify-center rounded-xl text-[#256958] hover:bg-[#edf5ee]" :aria-label="`Ubah ${transaction.title}`"><Pencil :size="17" aria-hidden="true" /></Link><button class="flex h-11 w-11 items-center justify-center rounded-xl text-[#ad5147] hover:bg-[#fff0ed] disabled:opacity-50" type="button" :disabled="pending === transaction.id" :aria-label="`Hapus ${transaction.title}`" @click="remove(transaction)"><Trash2 :size="17" aria-hidden="true" /></button></div>
                </article>
            </div>
            <div v-else class="px-6 py-14 text-center"><ReceiptText :size="34" class="mx-auto text-[#8caf97]" aria-hidden="true" /><h2 class="mt-4 font-bold text-[#294538]">Belum ada transaksi yang cocok</h2><p class="mt-2 text-sm text-[#7a8b80]">Ubah filter atau catat transaksi baru.</p></div>
        </section>
        <nav v-if="transactions.last_page > 1" class="mt-5 flex items-center justify-between gap-3" aria-label="Halaman transaksi"><Link v-if="transactions.prev_page_url" :href="transactions.prev_page_url" class="inline-flex min-h-11 items-center gap-1 rounded-xl bg-white px-4 text-sm font-bold text-[#256958]"><ChevronLeft :size="17" aria-hidden="true" /> Sebelumnya</Link><span v-else></span><span class="text-sm text-[#728478]">{{ transactions.current_page }} / {{ transactions.last_page }}</span><Link v-if="transactions.next_page_url" :href="transactions.next_page_url" class="inline-flex min-h-11 items-center gap-1 rounded-xl bg-white px-4 text-sm font-bold text-[#256958]">Berikutnya <ChevronRight :size="17" aria-hidden="true" /></Link><span v-else></span></nav>
    </AppLayout>
</template>
