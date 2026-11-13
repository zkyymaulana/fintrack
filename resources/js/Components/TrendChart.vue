<script setup lang="ts">
import { computed, ref } from 'vue';
import type { TrendPoint } from '../types/finance';
import { rupiah } from '../utils/finance';

const props = defineProps<{ points: TrendPoint[]; height?: number; accessibleTable?: boolean }>();
const activeMonth = ref<string | null>(null);
const pinnedMonth = ref<string | null>(null);
const peak = computed(() => Math.max(1, ...props.points.flatMap((point) => [Number(point.income), Number(point.expense)])));
const bars = computed(() => props.points.map((point) => ({
    ...point,
    label: new Intl.DateTimeFormat('id-ID', { month: 'short', year: '2-digit', timeZone: 'UTC' }).format(new Date(`${point.month}-01T00:00:00Z`)),
    incomeHeight: `${Math.max(0, Number(point.income) / peak.value * 100)}%`,
    expenseHeight: `${Math.max(0, Number(point.expense) / peak.value * 100)}%`,
})));
const activePoint = computed(() => bars.value.find((point) => point.month === (activeMonth.value ?? pinnedMonth.value)));
function toggleMonth(month: string) {
    pinnedMonth.value = pinnedMonth.value === month ? null : month;
}
</script>

<template>
    <div v-if="points.length">
        <div class="mb-4 flex flex-wrap items-center gap-5 text-xs font-semibold text-[#60766a]"><span class="flex items-center gap-2"><span class="h-3 w-3 rounded bg-[#28785e]"></span>Pemasukan</span><span class="flex items-center gap-2"><span class="h-3 w-3 rounded bg-[#db8c71]"></span>Pengeluaran</span></div>
        <div class="overflow-x-auto pb-2">
            <div class="min-w-[360px]" :style="{ height: `${height ?? 300}px` }">
                <div class="relative flex h-[calc(100%-28px)] items-end gap-2 border-b border-[#dce8df] bg-[linear-gradient(to_top,#edf0eb_1px,transparent_1px)] bg-[length:100%_25%] px-2 sm:gap-3">
                    <button v-for="point in bars" :key="point.month" type="button" class="group flex h-full min-w-0 flex-1 items-end justify-center gap-1 rounded-t-md px-1 outline-none focus-visible:ring-2 focus-visible:ring-[#28785e] sm:gap-2" :aria-label="`${point.label}: pemasukan ${rupiah(point.income)}, pengeluaran ${rupiah(point.expense)}. Klik untuk melihat detail.`" :aria-pressed="pinnedMonth === point.month" @mouseenter="activeMonth = point.month" @mouseleave="activeMonth === point.month && (activeMonth = null)" @focus="activeMonth = point.month" @blur="activeMonth === point.month && (activeMonth = null)" @click="toggleMonth(point.month)">
                        <span class="w-full max-w-8 rounded-t-md bg-[#28785e] transition-[height] duration-300 group-hover:brightness-90" :style="{ height: point.incomeHeight }"></span>
                        <span class="w-full max-w-8 rounded-t-md bg-[#db8c71] transition-[height] duration-300 group-hover:brightness-90" :style="{ height: point.expenseHeight }"></span>
                    </button>
                </div>
                <div class="flex gap-2 px-2 pt-2 text-center text-[10px] font-medium text-[#718478] sm:gap-3 sm:text-xs"><span v-for="point in bars" :key="point.month" class="min-w-0 flex-1 truncate">{{ point.label }}</span></div>
            </div>
        </div>
        <div v-if="activePoint" class="mt-3 rounded-2xl bg-[#f5f8f3] px-4 py-3" role="status" aria-live="polite">
            <p class="font-bold text-[#294538]">{{ activePoint.label }}</p>
            <div class="mt-2 flex flex-wrap gap-x-6 gap-y-1 text-sm"><span class="text-[#28785e]">Pemasukan <strong>{{ rupiah(activePoint.income) }}</strong></span><span class="text-[#aa5344]">Pengeluaran <strong>{{ rupiah(activePoint.expense) }}</strong></span></div>
            <p class="mt-2 text-xs text-[#748579]">Klik batang atau tekan Enter untuk menahan detail.</p>
        </div>
    </div>
    <p v-else class="rounded-2xl bg-[#f5f8f3] p-5 text-sm text-[#748579]">Belum ada data tren untuk periode ini.</p>
    <table v-if="accessibleTable !== false" class="sr-only">
        <caption>Data tren pemasukan dan pengeluaran</caption>
        <thead><tr><th scope="col">Bulan</th><th scope="col">Pemasukan</th><th scope="col">Pengeluaran</th></tr></thead>
        <tbody><tr v-for="point in points" :key="point.month"><th scope="row">{{ point.month }}</th><td>{{ rupiah(point.income) }}</td><td>{{ rupiah(point.expense) }}</td></tr></tbody>
    </table>
</template>
