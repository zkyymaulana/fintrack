<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowDownLeft, ArrowLeftRight, ArrowRight, ArrowUpRight, Plus, ReceiptText, Target, Wallet } from '@lucide/vue';
import TrendChart from '../Components/TrendChart.vue';
import AppLayout from '../Layouts/AppLayout.vue';
import type { Budget, Transaction, TrendPoint } from '../types/finance';
import { rupiah, tanggal, typeLabel } from '../utils/finance';

defineProps<{
    totalBalance: string;
    monthlyIncome: string;
    monthlyExpense: string;
    walletCount: number;
    transactionCount: number;
    recentTransactions: Transaction[];
    budgetOverview: { totalLimit: string; totalSpent: string; budgets: Budget[] };
    trend: TrendPoint[];
}>();

const icon = { income: ArrowDownLeft, expense: ArrowUpRight, transfer: ArrowLeftRight };
</script>

<template>
    <Head title="Beranda" />
    <AppLayout title="Beranda" subtitle="Ringkasan uangmu hari ini, langsung dari transaksi dan dompet akunmu.">
        <section class="relative overflow-hidden rounded-[28px] bg-[#194b40] p-7 text-white sm:p-10">
            <div class="absolute -right-14 -top-20 h-72 w-72 rounded-full border border-white/10"></div>
            <div class="relative flex flex-col justify-between gap-7 sm:flex-row sm:items-end">
                <div>
                    <p class="text-sm font-semibold text-[#c5e8d3]">Total saldo {{ walletCount }} dompet</p>
                    <p class="mt-3 break-words text-4xl font-bold tracking-tight sm:text-5xl">{{ rupiah(totalBalance) }}</p>
                    <p class="mt-4 max-w-lg text-sm leading-relaxed text-[#c4dfd2]">Semua saldo dompet aktif milikmu dalam satu pandangan.</p>
                </div>
                <Link href="/transactions/create" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl bg-white px-5 text-sm font-bold text-[#205c4b] transition hover:bg-[#e8f5e8]">
                    <Plus :size="18" aria-hidden="true" /> Catat transaksi
                </Link>
            </div>
        </section>

        <section class="mt-5 grid gap-4 sm:grid-cols-2" aria-label="Ringkasan bulan ini">
            <div class="surface-shadow rounded-3xl border border-[#e3ebe3] bg-white p-6">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#e9f5ee] text-[#247358]"><ArrowDownLeft :size="22" aria-hidden="true" /></span>
                <p class="mt-4 text-sm text-[#6d8074]">Pemasukan bulan ini</p>
                <p class="mt-1 break-words text-2xl font-bold text-[#245d48]">{{ rupiah(monthlyIncome) }}</p>
            </div>
            <div class="surface-shadow rounded-3xl border border-[#e3ebe3] bg-white p-6">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#fff0e9] text-[#bf6350]"><ArrowUpRight :size="22" aria-hidden="true" /></span>
                <p class="mt-4 text-sm text-[#6d8074]">Pengeluaran bulan ini, termasuk biaya admin</p>
                <p class="mt-1 break-words text-2xl font-bold text-[#aa5344]">{{ rupiah(monthlyExpense) }}</p>
            </div>
        </section>

        <section class="mt-8 grid gap-5 xl:grid-cols-[minmax(0,1.45fr)_minmax(300px,1fr)]" aria-label="Tren dan anggaran">
            <div class="min-w-0 rounded-3xl border border-[#e3ebe3] bg-white p-5 sm:p-7"><div class="flex flex-wrap items-start justify-between gap-3"><div><h2 class="text-lg font-bold text-[#294538]">Arus kas enam bulan</h2><p class="mt-1 text-sm text-[#7a8b80]">Pemasukan dan pengeluaran per bulan</p></div><Link href="/analytics" class="inline-flex min-h-11 items-center gap-1 text-sm font-bold text-[#256958] hover:underline">Analitik <ArrowRight :size="16" aria-hidden="true" /></Link></div><TrendChart :points="trend" :height="255" /></div>
            <div class="rounded-3xl border border-[#e3ebe3] bg-white p-5 sm:p-7"><div class="flex items-start justify-between gap-3"><div><h2 class="text-lg font-bold text-[#294538]">Anggaran bulan ini</h2><p class="mt-1 text-sm text-[#7a8b80]">{{ rupiah(budgetOverview.totalSpent) }} dari {{ rupiah(budgetOverview.totalLimit) }}</p></div><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#e9f4eb] text-[#28785e]"><Target :size="19" aria-hidden="true" /></span></div><div v-if="budgetOverview.budgets.length" class="mt-5 space-y-5"><div v-for="budget in budgetOverview.budgets" :key="budget.category_id"><div class="flex justify-between gap-3 text-sm"><span class="truncate font-semibold text-[#294538]">{{ budget.category_name }}</span><span class="shrink-0 text-[#728478]">{{ budget.percentage }}%</span></div><div class="mt-2 h-2 overflow-hidden rounded-full bg-[#edf1eb]"><div class="h-full rounded-full" :class="budget.status === 'over' ? 'bg-[#d66c5d]' : budget.status === 'warning' ? 'bg-[#e7a44f]' : 'bg-[#34826f]'" :style="{ width: `${Math.min(budget.percentage, 100)}%` }"></div></div><p class="mt-1 text-xs text-[#839187]">{{ rupiah(budget.spent) }} dari {{ rupiah(budget.limit_amount) }}</p></div></div><p v-else class="mt-6 rounded-2xl bg-[#f5f8f3] p-5 text-sm leading-relaxed text-[#748579]">Belum ada anggaran. Tetapkan batas untuk mengawasi pengeluaranmu.</p><Link href="/budgets" class="mt-5 inline-flex min-h-11 items-center gap-2 text-sm font-bold text-[#256958] hover:underline">Lihat anggaran <ArrowRight :size="16" aria-hidden="true" /></Link></div>
        </section>

        <section class="mt-8 overflow-hidden rounded-3xl border border-[#e3ebe3] bg-white">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#edf0eb] px-6 py-5 sm:px-7">
                <div><h2 class="text-lg font-bold text-[#294538]">Transaksi terbaru</h2><p class="mt-1 text-sm text-[#7a8b80]">{{ transactionCount }} transaksi tercatat</p></div>
                <Link href="/transactions" class="inline-flex min-h-11 items-center gap-2 text-sm font-bold text-[#256958] hover:underline">Lihat semua <ArrowRight :size="16" aria-hidden="true" /></Link>
            </div>
            <div v-if="recentTransactions.length" class="divide-y divide-[#edf0eb]">
                <Link v-for="transaction in recentTransactions" :key="transaction.id" :href="`/transactions/${transaction.id}/edit`" class="flex min-h-20 items-center gap-4 px-6 py-4 transition hover:bg-[#f8faf7] sm:px-7">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl" :class="transaction.type === 'income' ? 'bg-[#e9f5ee] text-[#247358]' : transaction.type === 'expense' ? 'bg-[#fff0e9] text-[#bf6350]' : 'bg-[#edf2fc] text-[#5974af]'"><component :is="icon[transaction.type]" :size="20" aria-hidden="true" /></span>
                    <span class="min-w-0 flex-1"><span class="block truncate font-semibold text-[#294538]">{{ transaction.title }}</span><span class="mt-1 block truncate text-xs text-[#839187]">{{ tanggal(transaction.date) }} · {{ typeLabel(transaction.type) }} · {{ transaction.wallet_name ?? 'Dompet tidak tersedia' }}</span></span>
                    <span class="shrink-0 text-right text-sm font-bold sm:text-base" :class="transaction.type === 'income' ? 'text-[#247358]' : transaction.type === 'expense' ? 'text-[#aa5344]' : 'text-[#5974af]'">{{ transaction.type === 'income' ? '+' : transaction.type === 'expense' ? '−' : '' }}{{ rupiah(transaction.amount) }}</span>
                </Link>
            </div>
            <div v-else class="flex flex-col items-center px-6 py-14 text-center"><ReceiptText :size="34" class="text-[#8caf97]" aria-hidden="true" /><p class="mt-4 font-bold text-[#294538]">Belum ada transaksi</p><p class="mt-2 text-sm text-[#7a8b80]">Buat dompet, lalu catat pemasukan atau pengeluaran pertamamu.</p><Link :href="walletCount ? '/transactions/create' : '/wallets/create'" class="mt-5 inline-flex min-h-11 items-center gap-2 font-bold text-[#256958] hover:underline"><Wallet :size="17" aria-hidden="true" /> {{ walletCount ? 'Catat transaksi' : 'Buat dompet' }}</Link></div>
        </section>
    </AppLayout>
</template>
