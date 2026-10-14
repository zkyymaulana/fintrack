<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Wallet as WalletIcon } from '@lucide/vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import type { Wallet } from '../../types/finance';

const props = defineProps<{ wallet: Wallet | null }>();
const form = useForm({ name: props.wallet?.name ?? '', balance: props.wallet?.balance ?? '0' });
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
            <div><label for="wallet-balance" class="mb-2 block text-sm font-semibold">Saldo saat ini</label><input id="wallet-balance" v-model="form.balance" class="field" type="number" inputmode="decimal" min="0" step="0.01" required :aria-invalid="Boolean(form.errors.balance)" /><p class="mt-2 text-xs leading-relaxed text-[#7d8c80]">Saat mengubah saldo, nilai ini menjadi saldo baru dompet. Perubahan manual tidak membuat transaksi.</p><p v-if="form.errors.balance" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.balance }}</p></div>
            <div class="flex flex-wrap gap-3 border-t border-[#edf0eb] pt-6"><button type="submit" class="primary-button" :disabled="form.processing">{{ form.processing ? 'Menyimpan...' : 'Simpan dompet' }}</button><Link href="/wallets" class="inline-flex min-h-12 items-center rounded-xl px-5 text-sm font-semibold text-[#61766b] hover:bg-[#f2f6f1]">Batal</Link></div>
        </form>
    </AppLayout>
</template>
