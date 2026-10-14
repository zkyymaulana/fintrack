<script setup lang="ts">
import { ChartNoAxesCombined, ShieldCheck, Sparkles } from '@lucide/vue';
import NetworkStatus from '../Components/NetworkStatus.vue';

defineProps<{
    eyebrow: string;
    title: string;
    description: string;
}>();
</script>

<template>
    <div class="min-h-screen bg-[#f5f7f4] lg:grid lg:grid-cols-[minmax(0,1.05fr)_minmax(420px,0.95fr)]">
        <aside class="auth-glow relative hidden min-h-screen overflow-hidden p-12 text-white lg:flex lg:flex-col xl:p-16">
            <div class="absolute -right-36 top-20 h-96 w-96 rounded-full border border-white/10"></div>
            <div class="absolute -right-14 top-44 h-72 w-72 rounded-full border border-white/10"></div>

            <div class="relative flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-[#d5efda] text-[#155548]">
                    <ChartNoAxesCombined :size="23" :stroke-width="2.3" aria-hidden="true" />
                </span>
                <span class="text-2xl font-bold tracking-tight">fintrack<span class="text-[#a9dbb4]">.</span></span>
            </div>

            <div class="relative my-auto max-w-xl py-16">
                <div class="mb-7 inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-medium text-[#d7f1e1]">
                    <Sparkles :size="15" aria-hidden="true" />
                    Keuangan lebih tertata, dari mana saja
                </div>
                <h2 class="max-w-lg text-5xl font-bold leading-[1.12] tracking-tight xl:text-6xl">
                    Kenali arah uangmu dengan lebih tenang.
                </h2>
                <p class="mt-6 max-w-md text-lg leading-relaxed text-[#c4dbd3]">
                    Satu tempat untuk mencatat, merencanakan, dan memahami keuangan harianmu.
                </p>

                <div class="surface-shadow mt-12 max-w-md rounded-[28px] border border-white/15 bg-white/10 p-6 backdrop-blur-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-[#d7f1e1]">Gambaran keuangan</span>
                        <span class="rounded-full bg-[#b8e5c5]/20 px-3 py-1 text-xs font-semibold text-[#d7f1e1]">Segera hadir</span>
                    </div>
                    <div class="mt-8 flex h-32 items-end gap-3" aria-hidden="true">
                        <span v-for="height in [32, 46, 38, 62, 55, 76, 66, 91, 78, 100]" :key="height" class="flex-1 rounded-t-md bg-[#a9dbb4]/80" :style="{ height: `${height}%` }"></span>
                    </div>
                    <div class="mt-5 h-px bg-white/20"></div>
                    <p class="mt-4 text-sm text-[#d7f1e1]">Ruang untuk keputusan yang lebih jelas.</p>
                </div>
            </div>

            <p class="relative flex items-center gap-2 text-sm text-[#c4dbd3]">
                <ShieldCheck :size="17" aria-hidden="true" />
                Data akunmu tetap berada di ruang pribadimu.
            </p>
        </aside>

        <main class="flex min-h-screen flex-col px-5 py-6 sm:px-9 lg:justify-center lg:px-12 xl:px-20">
            <div class="mb-10 flex items-center gap-3 lg:hidden">
                <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#256958] text-white">
                    <ChartNoAxesCombined :size="21" :stroke-width="2.3" aria-hidden="true" />
                </span>
                <span class="text-xl font-bold tracking-tight">fintrack<span class="text-[#4a9679]">.</span></span>
            </div>

            <div class="mx-auto w-full max-w-[440px] py-6 lg:py-10">
                <p class="mb-3 text-xs font-bold uppercase tracking-[0.22em] text-[#3e826c]">{{ eyebrow }}</p>
                <h1 class="text-4xl font-bold leading-tight tracking-tight text-[#172c29] sm:text-[42px]">{{ title }}</h1>
                <p class="mt-3 text-base leading-relaxed text-[#6b7d73]">{{ description }}</p>
                <NetworkStatus class="mt-5" />
                <slot />
            </div>

            <p class="mt-auto pt-10 text-center text-xs text-[#8b9b91] lg:mt-0">© {{ new Date().getFullYear() }} FinTrack</p>
        </main>
    </div>
</template>
