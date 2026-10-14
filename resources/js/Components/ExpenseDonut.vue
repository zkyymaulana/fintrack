<script setup lang="ts">
import { computed } from 'vue';
import type { ExpenseCategory } from '../types/finance';
import { rupiah } from '../utils/finance';

const props = defineProps<{ categories: ExpenseCategory[]; total: string }>();
const colors = ['#28785e', '#c47b43', '#597caf', '#9271aa', '#688c50', '#c56576'];
const circumference = 2 * Math.PI * 42;
const segments = computed(() => {
    const total = props.categories.reduce((sum, category) => sum + Math.max(0, Number(category.amount)), 0);
    let offset = 0;

    return props.categories.map((category, index) => {
        const length = total > 0 ? Math.max(0, Number(category.amount)) / total * circumference : 0;
        const segment = { name: category.category_name, color: colors[index % colors.length], length, offset };
        offset += length;

        return segment;
    });
});
</script>

<template>
    <div v-if="categories.length" aria-hidden="true" class="mx-auto w-full max-w-[320px]">
        <div class="relative mx-auto aspect-square max-w-[250px]">
            <svg viewBox="0 0 120 120" class="h-full w-full" focusable="false">
                <circle cx="60" cy="60" r="42" fill="none" stroke="#edf1eb" stroke-width="16" />
                <circle v-for="segment in segments" :key="segment.name" cx="60" cy="60" r="42" fill="none" :stroke="segment.color" stroke-width="16" :stroke-dasharray="`${segment.length} ${circumference - segment.length}`" :stroke-dashoffset="-segment.offset" transform="rotate(-90 60 60)" />
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center text-center"><span class="text-xs font-medium text-[#748579]">Pengeluaran</span><strong class="mt-1 break-words px-8 text-lg text-[#294538]">{{ rupiah(total) }}</strong></div>
        </div>
        <div class="mt-4 flex flex-wrap justify-center gap-x-4 gap-y-2 text-xs font-medium text-[#60766a]"><span v-for="segment in segments" :key="segment.name" class="inline-flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: segment.color }"></span>{{ segment.name }}</span></div>
    </div>
</template>
