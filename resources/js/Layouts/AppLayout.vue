<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ChartNoAxesCombined,
    FileDown,
    LayoutDashboard,
    LogOut,
    ReceiptText,
    Target,
    UserRound,
    Wallet,
} from '@lucide/vue';
import { computed } from 'vue';
import NetworkStatus from '../Components/NetworkStatus.vue';

defineProps<{ title: string; subtitle?: string }>();

type AuthUser = { id: number; name: string; email: string; avatar_url: string | null };
const page = usePage();
const user = computed(() => (page.props.auth as { user: AuthUser | null } | undefined)?.user ?? null);
const initials = computed(() => user.value?.name.trim().slice(0, 1).toUpperCase() || 'F');

const navigation = [
    { label: 'Beranda', icon: LayoutDashboard, href: '/dashboard' },
    { label: 'Transaksi', icon: ReceiptText, href: '/transactions' },
    { label: 'Dompet', icon: Wallet, href: '/wallets' },
    { label: 'Anggaran', icon: Target, href: '/budgets' },
    { label: 'Analitik', icon: ChartNoAxesCombined, href: '/analytics' },
    { label: 'Laporan', icon: FileDown, href: '/reports' },
    { label: 'Profil', icon: UserRound, href: '/profile' },
];
const isActive = (href: string | null) => Boolean(href && (page.url.split('?')[0] === href || page.url.startsWith(`${href}/`)));
const success = computed(() => (page.props.flash as { success?: string } | undefined)?.success);
</script>

<template>
    <div class="min-h-screen bg-[#f5f7f4] text-[#172c29] lg:flex">
        <a href="#main-content" class="sr-only fixed left-3 top-3 z-50 rounded-xl bg-[#194b40] px-4 py-3 font-bold text-white focus:not-sr-only">Langsung ke konten</a>
        <aside class="hidden w-64 shrink-0 border-r border-[#e1e9e3] bg-white px-5 py-7 lg:flex lg:min-h-screen lg:flex-col xl:w-72">
            <div class="flex items-center gap-3 px-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#256958] text-white">
                    <ChartNoAxesCombined :size="21" :stroke-width="2.3" aria-hidden="true" />
                </span>
                <span class="text-xl font-bold tracking-tight">fintrack<span class="text-[#4a9679]">.</span></span>
            </div>

            <div class="mt-12 px-3 text-[11px] font-bold uppercase tracking-[0.18em] text-[#a2afa5]">Menu utama</div>
            <nav class="mt-4 space-y-1" aria-label="Navigasi utama">
                <template v-for="item in navigation" :key="item.label">
                    <Link v-if="item.href" :href="item.href" :aria-current="isActive(item.href) ? 'page' : undefined" class="flex min-h-12 items-center gap-3 rounded-xl px-4 text-sm transition-colors" :class="isActive(item.href) ? 'bg-[#e9f4eb] font-bold text-[#1e6951]' : 'font-medium text-[#62766a] hover:bg-[#f2f6f1] hover:text-[#1e6951]'">
                        <component :is="item.icon" :size="19" aria-hidden="true" />
                        {{ item.label }}
                    </Link>
                    <span v-else class="flex min-h-12 items-center gap-3 rounded-xl px-4 text-sm font-medium text-[#a0aca3]" aria-disabled="true">
                        <component :is="item.icon" :size="19" aria-hidden="true" />
                        {{ item.label }}
                        <span class="ml-auto text-[10px] font-semibold">Segera</span>
                    </span>
                </template>
            </nav>

            <div class="mt-auto rounded-2xl bg-[#f1f6ee] p-4">
                <p class="text-sm font-bold text-[#315b49]">Keuanganmu, lebih rapi</p>
                <p class="mt-1 text-xs leading-relaxed text-[#728879]">Pantau saldo dan catat transaksi dalam satu tempat.</p>
            </div>
        </aside>

        <div class="min-w-0 flex-1">
            <header class="sticky top-0 z-20 border-b border-[#e1e9e3] bg-[#f5f7f4]/95 px-5 backdrop-blur-sm sm:px-8 lg:px-10">
                <div class="mx-auto flex h-[74px] max-w-7xl items-center justify-between gap-4">
                    <div class="flex min-w-0 items-center gap-3 lg:hidden">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-[#256958] text-white">
                            <ChartNoAxesCombined :size="20" aria-hidden="true" />
                        </span>
                        <span class="hidden truncate text-xl font-bold tracking-tight min-[380px]:block">fintrack<span class="text-[#4a9679]">.</span></span>
                    </div>
                    <div class="hidden min-w-0 lg:block">
                        <p class="text-xs font-medium text-[#84958a]">Ruang keuangan pribadi</p>
                        <p class="truncate text-sm font-bold text-[#2a4e3b]">{{ title }}</p>
                    </div>

                    <div class="ml-auto flex items-center gap-3">
                        <div class="hidden min-w-0 text-right sm:block">
                            <p class="truncate text-sm font-bold text-[#284b3c]">{{ user?.name }}</p>
                            <p class="truncate text-xs text-[#819186]">{{ user?.email }}</p>
                        </div>
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-[#d9eadc] text-sm font-bold text-[#26694f]" :aria-label="`Akun ${user?.name ?? ''}`"><img v-if="user?.avatar_url" :src="user.avatar_url" alt="" class="h-full w-full object-cover" />{{ user?.avatar_url ? '' : initials }}</span>
                        <Link href="/logout" method="post" as="button" class="flex h-11 w-11 items-center justify-center rounded-xl border border-[#dfe8df] bg-white text-[#5c7263] transition-colors hover:text-[#256958]" title="Keluar" aria-label="Keluar dari akun">
                            <LogOut :size="18" aria-hidden="true" />
                        </Link>
                    </div>
                </div>
            </header>

            <main id="main-content" tabindex="-1" class="mx-auto max-w-7xl px-5 pb-32 pt-8 sm:px-8 lg:px-10 lg:pb-12 lg:pt-11">
                <NetworkStatus class="mb-6" />
                <div v-if="success" role="status" class="mb-6 rounded-2xl border border-[#bedfc7] bg-[#edf8ed] px-5 py-3 text-sm font-semibold text-[#256958]">{{ success }}</div>
                <div class="mb-8">
                    <p class="mb-2 text-xs font-bold uppercase tracking-[0.18em] text-[#5b9679]">FinTrack web</p>
                    <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">{{ title }}</h1>
                    <p v-if="subtitle" class="mt-2 max-w-2xl text-sm leading-relaxed text-[#728478] sm:text-base">{{ subtitle }}</p>
                </div>
                <slot />
            </main>
        </div>

        <nav class="mobile-nav fixed inset-x-0 bottom-0 z-30 grid grid-cols-5 border-t border-[#dfe8df] bg-white/95 px-2 pt-2 backdrop-blur-md lg:hidden" aria-label="Navigasi ponsel">
            <template v-for="item in [...navigation.slice(0, 4), navigation[6]]" :key="item.label">
                <Link v-if="item.href" :href="item.href" :aria-current="isActive(item.href) ? 'page' : undefined" class="flex min-h-14 flex-col items-center justify-center gap-1 rounded-xl text-[11px]" :class="isActive(item.href) ? 'bg-[#e9f4eb] font-bold text-[#1e6951]' : 'font-medium text-[#6c8072]'">
                    <component :is="item.icon" :size="20" aria-hidden="true" />
                    {{ item.label }}
                </Link>
                <span v-else class="flex min-h-14 flex-col items-center justify-center gap-1 text-[11px] font-medium text-[#a0aca3]" aria-disabled="true">
                    <component :is="item.icon" :size="20" aria-hidden="true" />
                    {{ item.label }}
                </span>
            </template>
        </nav>
    </div>
</template>
