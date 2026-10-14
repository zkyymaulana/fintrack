<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowDownLeft, ArrowUpRight, CalendarRange, FileDown, TrendingUp } from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import AppLayout from '../Layouts/AppLayout.vue';
import { rupiah, tanggal, typeLabel } from '../utils/finance';

type Transaction = { date: string; title: string; type: 'income' | 'expense' | 'transfer'; total: string };
const props = defineProps<{
    start: string;
    end: string;
    summary: { income: string; expense: string; net: string };
    count: number;
    recentTransactions: Transaction[];
}>();
const page = usePage();
const errors = computed(() => page.props.errors as Record<string, string> | undefined);
const fields = reactive({ from: props.start, to: props.end });
const downloading = ref<'pdf' | 'excel' | null>(null);
const downloadError = ref('');
const periodLabel = computed(() => props.start === props.end ? tanggal(props.start) : `${tanggal(props.start)} – ${tanggal(props.end)}`);

function filter() {
    downloadError.value = '';
    router.get('/reports', { from: fields.from, to: fields.to }, { preserveState: true, replace: true });
}

async function download(format: 'pdf' | 'excel') {
    downloadError.value = '';
    downloading.value = format;
    try {
        const params = new URLSearchParams({ from: props.start, to: props.end });
        const response = await fetch(`/reports/download/${format}?${params}`, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        if (!response.ok) {
            const body = await response.json().catch(() => null) as { message?: string; errors?: Record<string, string[]> } | null;
            throw new Error(body?.errors ? Object.values(body.errors)[0]?.[0] : body?.message ?? 'Unduhan gagal. Coba lagi.');
        }
        const blob = await response.blob();
        const url = URL.createObjectURL(blob);
        const anchor = document.createElement('a');
        anchor.href = url;
        anchor.download = `Laporan_FinTrack_${props.start}_${props.end}.${format === 'pdf' ? 'pdf' : 'xlsx'}`;
        document.body.appendChild(anchor);
        anchor.click();
        anchor.remove();
        window.setTimeout(() => URL.revokeObjectURL(url), 60_000);
    } catch (error) {
        downloadError.value = error instanceof Error ? error.message : 'Unduhan gagal. Coba lagi.';
    } finally {
        downloading.value = null;
    }
}
</script>

<template>
    <Head title="Laporan" />
    <AppLayout title="Laporan" subtitle="Pilih periode, tinjau ringkasan, lalu unduh catatan keuanganmu.">
        <form class="surface-shadow rounded-3xl border border-[#e3ebe3] bg-white p-5 sm:p-6" @submit.prevent="filter">
            <h2 class="text-base font-bold text-[#294538]">Periode laporan</h2>
            <div class="mt-4 flex flex-wrap items-end gap-3">
                <div><label for="report-from" class="mb-2 block text-xs font-bold text-[#61766b]">Dari tanggal</label><input id="report-from" v-model="fields.from" class="field" type="date" required /><p v-if="errors?.from" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ errors.from }}</p></div>
                <div><label for="report-to" class="mb-2 block text-xs font-bold text-[#61766b]">Sampai tanggal</label><input id="report-to" v-model="fields.to" class="field" type="date" :min="fields.from" required /><p v-if="errors?.to" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ errors.to }}</p></div>
                <button class="primary-button" type="submit"><CalendarRange :size="17" aria-hidden="true" /> Tampilkan</button>
            </div>
            <p class="mt-3 text-xs text-[#839187]">Rentang tanggal maksimal 12 bulan kalender.</p>
        </form>

        <div class="mt-6 flex flex-wrap items-center justify-between gap-3"><p class="text-sm font-semibold text-[#61766b]">Periode {{ periodLabel }} · {{ count }} transaksi</p><Link href="/analytics" class="inline-flex min-h-11 items-center text-sm font-bold text-[#256958] hover:underline">Lihat analitik</Link></div>
        <section class="mt-3 grid gap-4 sm:grid-cols-3" aria-label="Ringkasan laporan">
            <div class="rounded-3xl border border-[#e3ebe3] bg-white p-6"><ArrowDownLeft :size="22" class="text-[#247358]" aria-hidden="true" /><p class="mt-4 text-sm text-[#728478]">Pemasukan</p><p class="mt-1 break-words text-2xl font-bold text-[#245d48]">{{ rupiah(summary.income) }}</p></div>
            <div class="rounded-3xl border border-[#e3ebe3] bg-white p-6"><ArrowUpRight :size="22" class="text-[#bf6350]" aria-hidden="true" /><p class="mt-4 text-sm text-[#728478]">Pengeluaran termasuk admin</p><p class="mt-1 break-words text-2xl font-bold text-[#aa5344]">{{ rupiah(summary.expense) }}</p></div>
            <div class="rounded-3xl bg-[#194b40] p-6 text-white"><TrendingUp :size="22" class="text-[#d3efda]" aria-hidden="true" /><p class="mt-4 text-sm text-[#c7e4d2]">Arus kas bersih</p><p class="mt-1 break-words text-2xl font-bold">{{ rupiah(summary.net) }}</p></div>
        </section>

        <section class="mt-7 rounded-3xl border border-[#e3ebe3] bg-white p-5 sm:p-7">
            <div class="flex flex-wrap items-start justify-between gap-5"><div><h2 class="text-lg font-bold text-[#294538]">Unduh laporan</h2><p class="mt-1 text-sm leading-relaxed text-[#7a8b80]">PDF untuk dibaca atau dicetak. Excel untuk mengolah rincian transaksi. Transfer ditampilkan tetapi tidak dihitung sebagai pemasukan atau pengeluaran.</p></div><FileDown :size="25" class="text-[#34826f]" aria-hidden="true" /></div>
            <div class="mt-5 flex flex-wrap gap-3"><button type="button" class="primary-button" :disabled="downloading !== null" @click="download('pdf')">{{ downloading === 'pdf' ? 'Menyiapkan PDF…' : 'Unduh PDF' }}</button><button type="button" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-[#bfd8c6] bg-white px-5 text-sm font-bold text-[#256958] disabled:opacity-50" :disabled="downloading !== null" @click="download('excel')">{{ downloading === 'excel' ? 'Menyiapkan Excel…' : 'Unduh Excel' }}</button></div>
            <p v-if="downloadError" role="alert" class="mt-4 rounded-xl bg-[#fff0e9] px-4 py-3 text-sm text-[#a9443c]">{{ downloadError }}</p>
        </section>

        <section class="mt-7 rounded-3xl border border-[#e3ebe3] bg-white p-5 sm:p-7"><h2 class="text-lg font-bold text-[#294538]">Transaksi terbaru pada periode ini</h2><div v-if="recentTransactions.length" class="mt-4 divide-y divide-[#edf0eb]"><div v-for="transaction in recentTransactions" :key="`${transaction.date}-${transaction.title}`" class="flex flex-wrap justify-between gap-2 py-3 text-sm"><div><p class="font-semibold text-[#294538]">{{ transaction.title }}</p><p class="text-[#839187]">{{ tanggal(transaction.date) }} · {{ typeLabel(transaction.type) }}</p></div><p class="font-bold text-[#294538]">{{ rupiah(transaction.total) }}</p></div></div><p v-else class="mt-4 text-sm text-[#7a8b80]">Belum ada transaksi pada periode ini. Laporan kosong tetap dapat diunduh.</p></section>
    </AppLayout>
</template>
