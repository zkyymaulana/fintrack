<script setup lang="ts">
import { computed } from 'vue';
import type { TrendPoint } from '../types/finance';
import { rupiah } from '../utils/finance';

const props = defineProps<{ points: TrendPoint[]; height?: number; accessibleTable?: boolean }>();
const peak = computed(() => Math.max(1, ...props.points.flatMap((point) => [Number(point.income), Number(point.expense)])));
const bars = computed(() => props.points.map((point) => ({
    ...point,
    label: new Intl.DateTimeFormat('id-ID', { month: 'short', year: '2-digit', timeZone: 'UTC' }).format(new Date(`${point.month}-01T00:00:00Z`)),
    incomeHeight: `${Math.max(0, Number(point.income) / peak.value * 100)}%`,
    expenseHeight: `${Math.max(0, Number(point.expense) / peak.value * 100)}%`,
})));
</script>

<template>
    <div v-if="points.length" aria-hidden="true">
        <div class="mb-4 flex flex-wrap items-center gap-5 text-xs font-semibold text-[#60766a]"><span class="flex items-center gap-2"><span class="h-3 w-3 rounded bg-[#28785e]"></span>Pemasukan</span><span class="flex items-center gap-2"><span class="h-3 w-3 rounded bg-[#db8c71]"></span>Pengeluaran</span></div>
        <div class="overflow-x-auto pb-2">
            <div class="min-w-[360px]" :style="{ height: `${height ?? 300}px` }">
                <div class="relative flex h-[calc(100%-28px)] items-end gap-2 border-b border-[#dce8df] bg-[linear-gradient(to_top,#edf0eb_1px,transparent_1px)] bg-[length:100%_25%] px-2 sm:gap-3">
                    <div v-for="point in bars" :key="point.month" class="flex h-full min-w-0 flex-1 items-end justify-center gap-1 sm:gap-2">
                        <div class="w-full max-w-8 rounded-t-md bg-[#28785e] transition-[height] duration-300" :style="{ height: point.incomeHeight }" :title="`${point.label}: pemasukan ${rupiah(point.income)}`"></div>
                        <div class="w-full max-w-8 rounded-t-md bg-[#db8c71] transition-[height] duration-300" :style="{ height: point.expenseHeight }" :title="`${point.label}: pengeluaran ${rupiah(point.expense)}`"></div>
                    </div>
                </div>
                <div class="flex gap-2 px-2 pt-2 text-center text-[10px] font-medium text-[#718478] sm:gap-3 sm:text-xs"><span v-for="point in bars" :key="point.month" class="min-w-0 flex-1 truncate">{{ point.label }}</span></div>
            </div>
        </div>
    </div>
    <p v-else class="rounded-2xl bg-[#f5f8f3] p-5 text-sm text-[#748579]">Belum ada data tren untuk periode ini.</p>
    <table v-if="accessibleTable !== false" class="sr-only">
        <caption>Data tren pemasukan dan pengeluaran</caption>
        <thead><tr><th scope="col">Bulan</th><th scope="col">Pemasukan</th><th scope="col">Pengeluaran</th></tr></thead>
        <tbody><tr v-for="point in points" :key="point.month"><th scope="row">{{ point.month }}</th><td>{{ rupiah(point.income) }}</td><td>{{ rupiah(point.expense) }}</td></tr></tbody>
    </table>
</template>
