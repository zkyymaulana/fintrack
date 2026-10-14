<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2, Wallet as WalletIcon } from '@lucide/vue';
import { computed, ref } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import type { Wallet } from '../../types/finance';
import { rupiah } from '../../utils/finance';

defineProps<{ wallets: Wallet[]; totalBalance: string }>();
const page = usePage();
const error = computed(() => (page.props.errors as Record<string, string> | undefined)?.wallet);
const pending = ref<number | null>(null);
function remove(id: number) {
    if (!window.confirm('Hapus dompet ini? Tindakan ini tidak dapat dibatalkan.')) return;
    pending.value = id;
    router.delete(`/wallets/${id}`, { onFinish: () => pending.value = null });
}
</script>

<template>
    <Head title="Dompet" />
    <AppLayout title="Dompet" subtitle="Kelola tempat penyimpanan uang dan lihat saldo terkininya.">
        <div v-if="error" role="alert" class="mb-5 rounded-2xl border border-[#f0c7be] bg-[#fff1ed] px-5 py-4 text-sm text-[#a74439]">{{ error }}</div>
        <section class="flex flex-wrap items-center justify-between gap-5 rounded-[28px] bg-[#194b40] p-7 text-white sm:p-9">
            <div><p class="text-sm font-medium text-[#c7e4d2]">Total saldo dompet aktif</p><p class="mt-2 break-words text-3xl font-bold sm:text-4xl">{{ rupiah(totalBalance) }}</p><p class="mt-3 text-sm text-[#c7e4d2]">{{ wallets.length }} dompet</p></div>
            <Link href="/wallets/create" class="inline-flex min-h-12 items-center gap-2 rounded-xl bg-white px-5 text-sm font-bold text-[#205c4b] hover:bg-[#e8f5e8]"><Plus :size="18" aria-hidden="true" /> Tambah dompet</Link>
        </section>
        <section v-if="wallets.length" class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3" aria-label="Daftar dompet">
            <article v-for="wallet in wallets" :key="wallet.id" class="surface-shadow min-w-0 rounded-3xl border border-[#e3ebe3] bg-white p-6">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#e9f4eb] text-[#2d765c]"><WalletIcon :size="23" aria-hidden="true" /></span>
                <h2 class="mt-5 truncate text-lg font-bold text-[#294538]">{{ wallet.name }}</h2>
                <p class="mt-1 text-xs font-semibold uppercase tracking-wider text-[#84958a]">Saldo saat ini</p>
                <p class="mt-2 break-words text-2xl font-bold text-[#245d48]">{{ rupiah(wallet.balance) }}</p>
                <div class="mt-6 flex gap-2 border-t border-[#edf0eb] pt-4">
                    <Link :href="`/wallets/${wallet.id}/edit`" class="inline-flex min-h-11 flex-1 items-center justify-center gap-2 rounded-xl bg-[#edf5ee] text-sm font-bold text-[#256958] hover:bg-[#dfece0]"><Pencil :size="16" aria-hidden="true" /> Ubah</Link>
                    <button type="button" :disabled="pending === wallet.id" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl px-4 text-sm font-bold text-[#ad5147] hover:bg-[#fff0ed] disabled:opacity-50" :aria-label="`Hapus dompet ${wallet.name}`" @click="remove(wallet.id)"><Trash2 :size="16" aria-hidden="true" /> Hapus</button>
                </div>
            </article>
        </section>
        <section v-else class="mt-6 rounded-3xl border border-dashed border-[#cbdacf] bg-white p-10 text-center"><WalletIcon :size="34" class="mx-auto text-[#8caf97]" aria-hidden="true" /><h2 class="mt-4 text-lg font-bold">Belum ada dompet</h2><p class="mt-2 text-sm text-[#748579]">Tambahkan dompet untuk mulai mencatat transaksi.</p></section>
    </AppLayout>
</template>
