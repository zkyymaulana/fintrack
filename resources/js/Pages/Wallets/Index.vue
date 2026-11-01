<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Eye, EyeOff, Pencil, Plus, Trash2, Wallet as WalletIcon } from '@lucide/vue';
import { computed, ref } from 'vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import type { Wallet } from '../../types/finance';
import { rupiah } from '../../utils/finance';

defineProps<{ wallets: Wallet[]; totalBalance: string }>();
const page = usePage();
const error = computed(() => (page.props.errors as Record<string, string> | undefined)?.wallet);
const pending = ref<number | null>(null);
const visibleBalances = ref(new Set<number>());
const showTotalBalance = ref(false);
function toggleBalance(id: number) {
    const next = new Set(visibleBalances.value);
    if (next.has(id)) next.delete(id);
    else next.add(id);
    visibleBalances.value = next;
}
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
        <section class="flex flex-wrap items-center justify-between gap-4 rounded-3xl bg-[#194b40] p-5 text-white sm:p-6">
            <div class="min-w-0"><p class="text-sm font-medium text-[#c7e4d2]">Total saldo dompet aktif</p><div class="mt-1 flex items-center gap-2"><p class="break-words text-2xl font-bold sm:text-3xl">{{ showTotalBalance ? rupiah(totalBalance) : '••••••••' }}</p><button type="button" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-[#d7eee0] hover:bg-white/10" :aria-label="showTotalBalance ? 'Sembunyikan saldo total' : 'Tampilkan saldo total'" @click="showTotalBalance = !showTotalBalance"><EyeOff v-if="showTotalBalance" :size="18" aria-hidden="true" /><Eye v-else :size="18" aria-hidden="true" /></button></div><p class="mt-1 text-sm text-[#c7e4d2]">{{ wallets.length }} dompet</p></div>
            <Link href="/wallets/create" class="inline-flex min-h-12 items-center gap-2 rounded-xl bg-white px-5 text-sm font-bold text-[#205c4b] hover:bg-[#e8f5e8]"><Plus :size="18" aria-hidden="true" /> Tambah dompet</Link>
        </section>
        <section v-if="wallets.length" class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3" aria-label="Daftar dompet">
            <article v-for="wallet in wallets" :key="wallet.id" class="surface-shadow min-w-0 rounded-2xl border border-[#e3ebe3] bg-white p-4">
                <div class="flex min-w-0 items-center gap-3"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#e9f4eb] text-[#2d765c]"><WalletIcon :size="19" aria-hidden="true" /></span><div class="min-w-0 flex-1"><h2 class="truncate font-bold text-[#294538]">{{ wallet.name }}</h2><span class="mt-1 inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold" :class="wallet.wallet_type === 'investment' ? 'bg-[#edf2fc] text-[#5974af]' : 'bg-[#edf5ee] text-[#256958]'">{{ wallet.wallet_type === 'investment' ? 'Investasi' : 'Reguler' }}</span></div><Link :href="`/wallets/${wallet.id}/edit`" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-[#256958] hover:bg-[#edf5ee]" :aria-label="`Ubah dompet ${wallet.name}`"><Pencil :size="16" aria-hidden="true" /></Link><button type="button" :disabled="pending === wallet.id" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-[#ad5147] hover:bg-[#fff0ed] disabled:opacity-50" :aria-label="`Hapus dompet ${wallet.name}`" @click="remove(wallet.id)"><Trash2 :size="16" aria-hidden="true" /></button></div>
                <div class="mt-4 flex items-center justify-between gap-3 border-t border-[#edf0eb] pt-3"><div class="min-w-0"><p class="text-xs text-[#84958a]">Saldo saat ini</p><p class="mt-1 truncate text-lg font-bold text-[#245d48]">{{ visibleBalances.has(wallet.id) ? rupiah(wallet.balance) : '••••••••' }}</p></div><button type="button" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-[#61766b] hover:bg-[#f3f6f2]" :aria-label="visibleBalances.has(wallet.id) ? `Sembunyikan saldo ${wallet.name}` : `Tampilkan saldo ${wallet.name}`" @click="toggleBalance(wallet.id)"><EyeOff v-if="visibleBalances.has(wallet.id)" :size="18" aria-hidden="true" /><Eye v-else :size="18" aria-hidden="true" /></button></div>
            </article>
        </section>
        <section v-else class="mt-6 rounded-3xl border border-dashed border-[#cbdacf] bg-white p-10 text-center"><WalletIcon :size="34" class="mx-auto text-[#8caf97]" aria-hidden="true" /><h2 class="mt-4 text-lg font-bold">Belum ada dompet</h2><p class="mt-2 text-sm text-[#748579]">Tambahkan dompet untuk mulai mencatat transaksi.</p></section>
    </AppLayout>
</template>
