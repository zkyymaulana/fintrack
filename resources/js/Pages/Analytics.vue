<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowDownLeft, ArrowUpRight, CalendarRange, ChartNoAxesCombined, TrendingUp } from '@lucide/vue';
import { computed, reactive } from 'vue';
import ExpenseDonut from '../Components/ExpenseDonut.vue';
import TrendChart from '../Components/TrendChart.vue';
import AppLayout from '../Layouts/AppLayout.vue';
import type { ExpenseCategory, TrendPoint } from '../types/finance';
import { rupiah, tanggal } from '../utils/finance';

const props = defineProps<{
    mode: 'month' | 'range';
    filters: { month?: string; from?: string; to?: string };
    start: string;
    end: string;
    summary: { income: string; expense: string; net: string };
    trend: TrendPoint[];
    distribution: ExpenseCategory[];
}>();
const page = usePage();
const errors = computed(() => page.props.errors as Record<string, string> | undefined);
const fields = reactive({ mode: props.mode, month: props.filters.month ?? new Date().toLocaleDateString('sv-SE').slice(0, 7), from: props.filters.from ?? props.start, to: props.filters.to ?? props.end });
const periodLabel = computed(() => props.start === props.end ? tanggal(props.start) : `${tanggal(props.start)} – ${tanggal(props.end)}`);
function filter() {
    router.get('/analytics', fields.mode === 'month' ? { mode: 'month', month: fields.month } : { mode: 'range', from: fields.from, to: fields.to }, { preserveState: true, replace: true });
}
</script>

<template>
    <Head title="Analitik" />
    <AppLayout title="Analitik" subtitle="Lihat arus uang dan kategori pengeluaran pada periode yang kamu pilih.">
        <form class="surface-shadow rounded-3xl border border-[#e3ebe3] bg-white p-5 sm:p-6" @submit.prevent="filter">
            <fieldset><legend class="mb-3 text-sm font-bold text-[#294538]">Pilih periode</legend><div class="flex flex-wrap gap-2"><label v-for="option in [{ value: 'month', label: 'Bulanan' }, { value: 'range', label: 'Rentang tanggal' }]" :key="option.value" class="inline-flex min-h-11 cursor-pointer items-center rounded-xl border px-4 text-sm font-bold focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-[#256958]" :class="fields.mode === option.value ? 'border-[#34826f] bg-[#e9f4eb] text-[#1e6951]' : 'border-[#dbe4de] text-[#61766b]'"><input v-model="fields.mode" class="sr-only" name="mode" type="radio" :value="option.value" />{{ option.label }}</label></div></fieldset>
            <div class="mt-5 flex flex-wrap items-end gap-3"><div v-if="fields.mode === 'month'"><label for="analytic-month" class="mb-2 block text-xs font-bold text-[#61766b]">Bulan</label><input id="analytic-month" v-model="fields.month" class="field min-w-48" type="month" required /><p v-if="errors?.month" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ errors.month }}</p></div><template v-else><div><label for="analytic-from" class="mb-2 block text-xs font-bold text-[#61766b]">Dari tanggal</label><input id="analytic-from" v-model="fields.from" class="field" type="date" required /><p v-if="errors?.from" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ errors.from }}</p></div><div><label for="analytic-to" class="mb-2 block text-xs font-bold text-[#61766b]">Sampai tanggal</label><input id="analytic-to" v-model="fields.to" class="field" type="date" :min="fields.from" required /><p v-if="errors?.to" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ errors.to }}</p></div></template><button class="primary-button" type="submit"><CalendarRange :size="17" aria-hidden="true" /> Tampilkan</button></div>
            <p class="mt-3 text-xs text-[#839187]">Rentang tanggal maksimal 12 bulan kalender.</p>
        </form>

        <div class="mt-6 flex flex-wrap items-center justify-between gap-3"><p class="text-sm font-semibold text-[#61766b]">Periode {{ periodLabel }}</p><Link href="/transactions" class="inline-flex min-h-11 items-center text-sm font-bold text-[#256958] hover:underline">Lihat transaksi</Link></div>
        <section class="mt-3 grid gap-4 sm:grid-cols-3" aria-label="Ringkasan periode">
            <div class="rounded-3xl border border-[#e3ebe3] bg-white p-6"><span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#e9f5ee] text-[#247358]"><ArrowDownLeft :size="22" aria-hidden="true" /></span><p class="mt-4 text-sm text-[#728478]">Pemasukan</p><p class="mt-1 break-words text-2xl font-bold text-[#245d48]">{{ rupiah(summary.income) }}</p></div>
            <div class="rounded-3xl border border-[#e3ebe3] bg-white p-6"><span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#fff0e9] text-[#bf6350]"><ArrowUpRight :size="22" aria-hidden="true" /></span><p class="mt-4 text-sm text-[#728478]">Pengeluaran, termasuk admin</p><p class="mt-1 break-words text-2xl font-bold text-[#aa5344]">{{ rupiah(summary.expense) }}</p></div>
            <div class="rounded-3xl bg-[#194b40] p-6 text-white"><span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/10 text-[#d3efda]"><TrendingUp :size="22" aria-hidden="true" /></span><p class="mt-4 text-sm text-[#c7e4d2]">Arus kas bersih</p><p class="mt-1 break-words text-2xl font-bold">{{ rupiah(summary.net) }}</p></div>
        </section>

        <section class="mt-7 rounded-3xl border border-[#e3ebe3] bg-white p-5 sm:p-7"><div class="mb-4"><h2 class="text-lg font-bold text-[#294538]">Tren pemasukan dan pengeluaran</h2><p class="mt-1 text-sm text-[#7a8b80]">{{ mode === 'month' ? 'Enam bulan hingga periode pilihan' : 'Bulan dalam rentang pilihan' }}. Transfer antar dompet tidak dihitung.</p></div><TrendChart :points="trend" :accessible-table="false" /><div class="mt-4 overflow-x-auto"><table class="w-full min-w-[480px] text-left text-sm"><caption class="mb-3 text-left font-bold text-[#294538]">Rincian tren per bulan</caption><thead><tr class="border-b border-[#e4ebe5] text-xs uppercase tracking-wider text-[#7b8b80]"><th class="py-3 pr-3" scope="col">Bulan</th><th class="py-3 pr-3 text-right" scope="col">Pemasukan</th><th class="py-3 text-right" scope="col">Pengeluaran</th></tr></thead><tbody><tr v-for="point in trend" :key="point.month" class="border-b border-[#edf0eb] last:border-0"><th class="py-3 pr-3 font-semibold" scope="row">{{ point.month }}</th><td class="py-3 pr-3 text-right text-[#28785e]">{{ rupiah(point.income) }}</td><td class="py-3 text-right text-[#aa5344]">{{ rupiah(point.expense) }}</td></tr></tbody></table></div></section>

        <section class="mt-7 rounded-3xl border border-[#e3ebe3] bg-white p-5 sm:p-7"><div><h2 class="text-lg font-bold text-[#294538]">Pengeluaran per kategori</h2><p class="mt-1 text-sm text-[#7a8b80]">Kategori dengan pengeluaran terbesar tampil lebih dulu.</p></div><template v-if="distribution.length"><div class="mt-4 grid items-center gap-5 lg:grid-cols-[minmax(240px,1fr)_minmax(0,1.4fr)]"><ExpenseDonut :categories="distribution" :total="summary.expense" /><div class="min-w-0 overflow-x-auto"><table class="w-full min-w-[360px] text-left text-sm"><caption class="mb-3 text-left font-bold text-[#294538]">Rincian pengeluaran per kategori</caption><thead><tr class="border-b border-[#e4ebe5] text-xs uppercase tracking-wider text-[#7b8b80]"><th class="py-3 pr-3" scope="col">Kategori</th><th class="py-3 pr-3 text-right" scope="col">Jumlah</th><th class="py-3 text-right" scope="col">Bagian</th></tr></thead><tbody><tr v-for="item in distribution" :key="item.category_id" class="border-b border-[#edf0eb] last:border-0"><th class="py-3 pr-3 font-semibold" scope="row">{{ item.category_name }}<span class="block text-xs font-normal text-[#839187]">{{ item.count }} transaksi</span></th><td class="py-3 pr-3 text-right">{{ rupiah(item.amount) }}</td><td class="py-3 text-right font-semibold text-[#256958]">{{ item.percentage }}%</td></tr></tbody></table></div></div></template><div v-else class="flex flex-col items-center px-6 py-12 text-center"><ChartNoAxesCombined :size="34" class="mx-auto text-[#8caf97]" aria-hidden="true" /><p class="mt-4 font-bold text-[#294538]">Belum ada pengeluaran pada periode ini</p><p class="mt-2 text-sm text-[#7a8b80]">Pilih periode lain atau catat transaksi untuk melihat distribusi.</p></div></section>
    </AppLayout>
</template>
