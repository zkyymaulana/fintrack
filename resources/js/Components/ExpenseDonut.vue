<script setup lang="ts">
import { computed, ref } from 'vue';
import type { ExpenseCategory } from '../types/finance';
import { rupiah } from '../utils/finance';

const props = defineProps<{ categories: ExpenseCategory[]; total: string }>();
const emit = defineEmits<{ select: [categoryId: number] }>();
const activeId = ref<number | null>(null);
const colors = ['#28785e', '#c47b43', '#597caf', '#9271aa', '#688c50', '#c56576'];
const circumference = 2 * Math.PI * 42;
const segments = computed(() => {
    const total = props.categories.reduce((sum, category) => sum + Math.max(0, Number(category.amount)), 0);
    let offset = 0;
    return props.categories.map((category, index) => {
        const length = total > 0 ? Math.max(0, Number(category.amount)) / total * circumference : 0;
        const segment = { ...category, color: colors[index % colors.length], length, offset };
        offset += length;
        return segment;
    });
});
const activeSegment = computed(() => segments.value.find((segment) => segment.category_id === activeId.value));
function choose(categoryId: number) {
    activeId.value = categoryId;
    emit('select', categoryId);
}
</script>

<template>
    <div v-if="categories.length" class="mx-auto w-full max-w-[320px]">
        <div class="relative mx-auto aspect-square max-w-[250px]">
            <svg viewBox="0 0 120 120" class="h-full w-full" role="group" aria-label="Pilih kategori pengeluaran pada diagram">
                <circle cx="60" cy="60" r="42" fill="none" stroke="#edf1eb" stroke-width="16" />
                <circle v-for="segment in segments" :key="segment.category_id" cx="60" cy="60" r="42" fill="none" :stroke="segment.color" :stroke-width="activeId === segment.category_id ? 20 : 16" :stroke-dasharray="`${segment.length} ${circumference - segment.length}`" :stroke-dashoffset="-segment.offset" transform="rotate(-90 60 60)" class="cursor-pointer outline-none transition-[stroke-width] focus-visible:stroke-[20]" role="button" tabindex="0" :aria-label="`${segment.category_name}: ${rupiah(segment.amount)}, ${segment.count} transaksi. Pilih untuk melihat detail.`" :aria-pressed="activeId === segment.category_id" @mouseenter="activeId = segment.category_id" @mouseleave="activeId === segment.category_id && (activeId = null)" @focus="activeId = segment.category_id" @blur="activeId === segment.category_id && (activeId = null)" @click="choose(segment.category_id)" @keydown.enter.prevent="choose(segment.category_id)" @keydown.space.prevent="choose(segment.category_id)" />
            </svg>
            <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center px-8 text-center"><span class="text-xs font-medium text-[#748579]">{{ activeSegment?.category_name ?? 'Pengeluaran' }}</span><strong class="mt-1 break-words text-lg text-[#294538]">{{ rupiah(activeSegment?.amount ?? total) }}</strong><span v-if="activeSegment" class="mt-0.5 text-xs text-[#748579]">{{ activeSegment.count }} transaksi · {{ activeSegment.percentage }}%</span></div>
        </div>
        <div class="mt-4 flex flex-wrap justify-center gap-x-4 gap-y-2 text-xs font-medium text-[#60766a]"><button v-for="segment in segments" :key="segment.category_id" type="button" class="inline-flex min-h-8 items-center gap-1.5 rounded-md px-1 outline-none focus-visible:ring-2 focus-visible:ring-[#28785e] hover:text-[#194b40]" @mouseenter="activeId = segment.category_id" @mouseleave="activeId === segment.category_id && (activeId = null)" @focus="activeId = segment.category_id" @blur="activeId === segment.category_id && (activeId = null)" @click="choose(segment.category_id)"><span class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: segment.color }"></span>{{ segment.category_name }}</button></div>
    </div>
</template>
