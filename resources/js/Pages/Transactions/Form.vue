<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ArrowLeftRight, Wallet } from '@lucide/vue';
import { computed, watch } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import type { Category, FinanceType, Option, Transaction } from '../../types/finance';

const props = defineProps<{ transaction: Transaction | null; wallets: Option[]; categories: Category[] }>();
const form = useForm({
    type: (props.transaction?.type ?? 'expense') as FinanceType,
    title: props.transaction?.title ?? '',
    amount: props.transaction?.amount ?? '',
    admin_fee: props.transaction?.admin_fee ?? '0',
    date: props.transaction?.date?.slice(0, 10) ?? new Date().toLocaleDateString('sv-SE'),
    category_id: String(props.transaction?.category_id ?? ''),
    wallet_id: String(props.transaction?.wallet_id ?? ''),
    to_wallet_id: String(props.transaction?.to_wallet_id ?? ''),
    note: props.transaction?.note ?? '',
});
const matchingCategories = computed(() => props.categories.filter((category) => category.type === (form.type === 'income' ? 'income' : 'expense')));
watch(() => form.type, () => { form.category_id = ''; form.to_wallet_id = ''; });
function submit() {
    if (props.transaction) form.put(`/transactions/${props.transaction.id}`);
    else form.post('/transactions');
}
</script>

<template>
    <Head :title="transaction ? 'Ubah transaksi' : 'Tambah transaksi'" />
    <AppLayout :title="transaction ? 'Ubah transaksi' : 'Tambah transaksi'" subtitle="Catat pergerakan uangmu dengan jelas dan tepat.">
        <Link href="/transactions" class="mb-5 inline-flex min-h-11 items-center gap-2 text-sm font-bold text-[#256958] hover:underline"><ArrowLeft :size="17" aria-hidden="true" /> Kembali ke transaksi</Link>
        <div v-if="!wallets.length || !matchingCategories.length" class="mb-5 rounded-2xl border border-[#f0d7aa] bg-[#fff9e9] px-5 py-4 text-sm text-[#795b25]">{{ !wallets.length ? 'Buat dompet terlebih dahulu sebelum mencatat transaksi.' : 'Kategori untuk jenis transaksi ini belum tersedia. Hubungi pengelola aplikasi.' }} <Link v-if="!wallets.length" href="/wallets/create" class="font-bold underline">Buat dompet</Link></div>
        <form class="surface-shadow max-w-3xl space-y-6 rounded-3xl border border-[#e3ebe3] bg-white p-6 sm:p-9" @submit.prevent="submit">
            <fieldset><legend class="mb-3 text-sm font-bold text-[#294538]">Jenis transaksi</legend><div class="grid gap-2 sm:grid-cols-3"><label v-for="option in [{ value: 'expense', label: 'Pengeluaran' }, { value: 'income', label: 'Pemasukan' }, { value: 'transfer', label: 'Transfer' }]" :key="option.value" class="flex min-h-12 cursor-pointer items-center justify-center rounded-xl border px-4 text-sm font-bold transition focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-[#256958]" :class="form.type === option.value ? 'border-[#34826f] bg-[#e9f4eb] text-[#1e6951]' : 'border-[#dbe4de] text-[#61766b] hover:bg-[#f7faf6]'"><input v-model="form.type" class="sr-only" type="radio" name="type" :value="option.value" />{{ option.label }}</label></div><p v-if="form.errors.type" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.type }}</p></fieldset>
            <div class="grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2"><label for="title" class="mb-2 block text-sm font-semibold">Judul</label><input id="title" v-model="form.title" class="field" maxlength="255" placeholder="Contoh: Belanja bulanan" required :aria-invalid="Boolean(form.errors.title)" /><p v-if="form.errors.title" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.title }}</p></div>
                <div><label for="amount" class="mb-2 block text-sm font-semibold">Jumlah (Rp)</label><input id="amount" v-model="form.amount" class="field" type="number" inputmode="decimal" min="0.01" step="0.01" required :aria-invalid="Boolean(form.errors.amount)" /><p v-if="form.errors.amount" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.amount }}</p></div>
                <div><label for="admin-fee" class="mb-2 block text-sm font-semibold">Biaya admin (Rp)</label><input id="admin-fee" v-model="form.admin_fee" class="field" type="number" inputmode="decimal" min="0" step="0.01" required :aria-invalid="Boolean(form.errors.admin_fee)" /><p v-if="form.errors.admin_fee" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.admin_fee }}</p></div>
                <div><label for="date" class="mb-2 block text-sm font-semibold">Tanggal</label><input id="date" v-model="form.date" class="field" type="date" required :aria-invalid="Boolean(form.errors.date)" /><p v-if="form.errors.date" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.date }}</p></div>
                <div><label for="category" class="mb-2 block text-sm font-semibold">Kategori</label><select id="category" v-model="form.category_id" class="field" required :aria-invalid="Boolean(form.errors.category_id)"><option value="" disabled>Pilih kategori</option><option v-for="category in matchingCategories" :key="category.id" :value="String(category.id)">{{ category.name }}</option></select><p v-if="form.errors.category_id" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.category_id }}</p></div>
                <div><label for="wallet" class="mb-2 block text-sm font-semibold">{{ form.type === 'transfer' ? 'Dompet asal' : 'Dompet' }}</label><select id="wallet" v-model="form.wallet_id" class="field" required :aria-invalid="Boolean(form.errors.wallet_id)"><option value="" disabled>Pilih dompet</option><option v-for="wallet in wallets" :key="wallet.id" :value="String(wallet.id)">{{ wallet.name }}</option></select><p v-if="form.errors.wallet_id" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.wallet_id }}</p></div>
                <div v-if="form.type === 'transfer'"><label for="to-wallet" class="mb-2 block text-sm font-semibold">Dompet tujuan</label><select id="to-wallet" v-model="form.to_wallet_id" class="field" required :aria-invalid="Boolean(form.errors.to_wallet_id)"><option value="" disabled>Pilih tujuan</option><option v-for="wallet in wallets.filter((item) => String(item.id) !== form.wallet_id)" :key="wallet.id" :value="String(wallet.id)">{{ wallet.name }}</option></select><p v-if="form.errors.to_wallet_id" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.to_wallet_id }}</p></div>
                <div class="sm:col-span-2"><label for="note" class="mb-2 block text-sm font-semibold">Catatan <span class="font-normal text-[#819186]">(opsional)</span></label><textarea id="note" v-model="form.note" class="field min-h-28" maxlength="5000" placeholder="Tambahkan detail jika perlu" :aria-invalid="Boolean(form.errors.note)"></textarea><p v-if="form.errors.note" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.note }}</p></div>
            </div>
            <div class="flex gap-3 rounded-2xl bg-[#f1f6ee] p-4 text-sm leading-relaxed text-[#567163]"><ArrowLeftRight v-if="form.type === 'transfer'" :size="19" class="shrink-0" aria-hidden="true" /><Wallet v-else :size="19" class="shrink-0" aria-hidden="true" /><p>{{ form.type === 'income' ? 'Jumlah akan ditambahkan ke saldo dompet.' : form.type === 'expense' ? 'Jumlah dan biaya admin akan dikurangi dari saldo dompet.' : 'Jumlah dan biaya admin dikurangi dari dompet asal; jumlahnya masuk ke dompet tujuan.' }}</p></div>
            <div class="flex flex-wrap gap-3 border-t border-[#edf0eb] pt-6"><button type="submit" class="primary-button" :disabled="form.processing || !wallets.length || !matchingCategories.length">{{ form.processing ? 'Menyimpan...' : 'Simpan transaksi' }}</button><Link href="/transactions" class="inline-flex min-h-12 items-center rounded-xl px-5 text-sm font-semibold text-[#61766b] hover:bg-[#f2f6f1]">Batal</Link></div>
        </form>
    </AppLayout>
</template>
