<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Wallet as WalletIcon } from '@lucide/vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import type { Category, Wallet } from '../../types/finance';

const props = defineProps<{ wallet: Wallet | null; categories: Category[] }>();
const form = useForm({
    name: props.wallet?.name ?? '',
    wallet_type: props.wallet?.wallet_type ?? 'regular',
    balance: props.wallet?.balance ?? '0',
    auto_deduction_enabled: props.wallet?.auto_deduction_enabled ?? false,
    auto_deduction_amount: props.wallet?.auto_deduction_amount ?? '',
    auto_deduction_category_id: props.wallet?.auto_deduction_category_id ? String(props.wallet.auto_deduction_category_id) : '',
    auto_deduction_title: props.wallet?.auto_deduction_title ?? '',
    auto_deduction_note: props.wallet?.auto_deduction_note ?? '',
    auto_deduction_frequency: props.wallet?.auto_deduction_frequency ?? 'monthly',
    auto_deduction_next_run_on: props.wallet?.auto_deduction_next_run_on?.slice(0, 10) ?? new Date().toLocaleDateString('sv-SE'),
});
function submit() {
    if (props.wallet) form.put(`/wallets/${props.wallet.id}`);
    else form.post('/wallets');
}
</script>

<template>
    <Head :title="wallet ? 'Ubah dompet' : 'Tambah dompet'" />
    <AppLayout :title="wallet ? 'Ubah dompet' : 'Tambah dompet'" subtitle="Beri nama dompet dan tentukan saldo yang ingin dicatat.">
        <Link href="/wallets" class="mb-5 inline-flex min-h-11 items-center gap-2 text-sm font-bold text-[#256958] hover:underline"><ArrowLeft :size="17" aria-hidden="true" /> Kembali ke dompet</Link>
        <form class="surface-shadow max-w-2xl space-y-6 rounded-3xl border border-[#e3ebe3] bg-white p-6 sm:p-9" @submit.prevent="submit">
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#e9f4eb] text-[#2d765c]"><WalletIcon :size="23" aria-hidden="true" /></span>
            <div><label for="wallet-name" class="mb-2 block text-sm font-semibold">Nama dompet</label><input id="wallet-name" v-model="form.name" class="field" maxlength="255" placeholder="Contoh: Rekening utama" required :aria-invalid="Boolean(form.errors.name)" /><p v-if="form.errors.name" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.name }}</p></div>
            <div><label for="wallet-type" class="mb-2 block text-sm font-semibold">Jenis dompet</label><select id="wallet-type" v-model="form.wallet_type" class="field" required :aria-invalid="Boolean(form.errors.wallet_type)"><option value="regular">Reguler</option><option value="investment">Investasi</option></select><p class="mt-2 text-xs leading-relaxed text-[#7d8c80]">Pilih Investasi untuk akun seperti Saham, Bibit, atau Ajaib.</p><p v-if="form.errors.wallet_type" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.wallet_type }}</p></div>
            <div><label for="wallet-balance" class="mb-2 block text-sm font-semibold">Saldo saat ini</label><input id="wallet-balance" v-model="form.balance" class="field" type="number" inputmode="decimal" min="0" step="0.01" required :aria-invalid="Boolean(form.errors.balance)" /><p class="mt-2 text-xs leading-relaxed text-[#7d8c80]">Saat mengubah saldo, nilai ini menjadi saldo baru dompet. Perubahan manual tidak membuat transaksi.</p><p v-if="form.errors.balance" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.balance }}</p></div>
            <section class="rounded-2xl border border-[#dce8df] bg-[#f8faf6] p-4 sm:p-5" aria-labelledby="auto-deduction-heading">
                <label class="flex cursor-pointer items-start gap-3"><input v-model="form.auto_deduction_enabled" class="mt-1 h-4 w-4 accent-[#256958]" type="checkbox" /><span><strong id="auto-deduction-heading" class="block text-sm font-bold text-[#294538]">Jadwalkan pengeluaran otomatis</strong><span class="mt-1 block text-xs leading-relaxed text-[#748579]">Nonaktif secara default. Jika aktif, pengeluaran dicatat sebagai transaksi dan saldo dompet berkurang sesuai jadwal.</span></span></label>
                <div v-if="form.auto_deduction_enabled" class="mt-5 grid gap-4 border-t border-[#e4ebe5] pt-5 sm:grid-cols-2">
                    <div><label for="auto-deduction-title" class="mb-2 block text-sm font-semibold">Judul transaksi</label><input id="auto-deduction-title" v-model="form.auto_deduction_title" class="field" maxlength="255" placeholder="Contoh: Langganan internet" required /><p v-if="form.errors.auto_deduction_title" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.auto_deduction_title }}</p></div>
                    <div><label for="auto-deduction-amount" class="mb-2 block text-sm font-semibold">Jumlah per potongan (Rp)</label><input id="auto-deduction-amount" v-model="form.auto_deduction_amount" class="field" type="number" inputmode="decimal" min="0.01" step="0.01" required /><p v-if="form.errors.auto_deduction_amount" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.auto_deduction_amount }}</p></div>
                    <div><label for="auto-deduction-frequency" class="mb-2 block text-sm font-semibold">Frekuensi</label><select id="auto-deduction-frequency" v-model="form.auto_deduction_frequency" class="field" required><option value="daily">Setiap hari</option><option value="weekly">Setiap minggu</option><option value="monthly">Setiap bulan</option></select><p v-if="form.errors.auto_deduction_frequency" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.auto_deduction_frequency }}</p></div>
                    <div><label for="auto-deduction-date" class="mb-2 block text-sm font-semibold">{{ wallet?.auto_deduction_enabled ? 'Tanggal potongan berikutnya' : 'Tanggal potongan pertama' }}</label><input id="auto-deduction-date" v-model="form.auto_deduction_next_run_on" class="field" type="date" required /><p v-if="form.errors.auto_deduction_next_run_on" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.auto_deduction_next_run_on }}</p></div>
                    <div><label for="auto-deduction-category" class="mb-2 block text-sm font-semibold">Kategori transaksi</label><select id="auto-deduction-category" v-model="form.auto_deduction_category_id" class="field" required><option value="" disabled>Pilih kategori pengeluaran</option><option v-for="category in categories" :key="category.id" :value="String(category.id)">{{ category.name }}</option></select><p v-if="form.errors.auto_deduction_category_id" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.auto_deduction_category_id }}</p></div>
                    <div class="sm:col-span-2"><label for="auto-deduction-note" class="mb-2 block text-sm font-semibold">Catatan <span class="font-normal text-[#819186]">(opsional)</span></label><textarea id="auto-deduction-note" v-model="form.auto_deduction_note" class="field min-h-20" maxlength="5000" placeholder="Misalnya: tagihan internet rumah" /><p v-if="form.errors.auto_deduction_note" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.auto_deduction_note }}</p></div>
                    <p class="sm:col-span-2 text-xs leading-relaxed text-[#748579]">Jika saldo kurang, potongan dilewati dan saldo tidak menjadi negatif. Potongan berikutnya tetap mengikuti jadwal.</p>
                </div>
            </section>
            <div class="flex flex-wrap gap-3 border-t border-[#edf0eb] pt-6"><button type="submit" class="primary-button" :disabled="form.processing">{{ form.processing ? 'Menyimpan...' : 'Simpan dompet' }}</button><Link href="/wallets" class="inline-flex min-h-12 items-center rounded-xl px-5 text-sm font-semibold text-[#61766b] hover:bg-[#f2f6f1]">Batal</Link></div>
        </form>
    </AppLayout>
</template>
