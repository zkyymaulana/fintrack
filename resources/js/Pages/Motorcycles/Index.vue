<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Bike, Gauge, Plus, Wrench } from '@lucide/vue';
import AppLayout from '../../Layouts/AppLayout.vue';
import type { MotorcycleSummary } from '../../types/motorcycle';

defineProps<{ motorcycles: MotorcycleSummary[] }>();
</script>

<template>
    <Head title="Motor" />
    <AppLayout title="Motor" subtitle="Catat kilometer, jadwal perawatan, sparepart, dan riwayat servis setiap motor.">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm font-semibold text-[#61766b]">{{ motorcycles.length }} motor tercatat</p>
            <Link href="/motorcycles/create" class="primary-button"><Plus :size="17" aria-hidden="true" /> Tambah motor</Link>
        </div>

        <section v-if="motorcycles.length" class="grid gap-4 md:grid-cols-2 xl:grid-cols-3" aria-label="Daftar motor">
            <article v-for="motorcycle in motorcycles" :key="motorcycle.id" class="surface-shadow overflow-hidden rounded-3xl border border-[#e3ebe3] bg-white">
                <div class="flex items-start gap-4 p-5">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-[#e9f4eb] text-[#256958]"><Bike :size="23" aria-hidden="true" /></span>
                    <div class="min-w-0 flex-1"><h2 class="truncate text-lg font-bold text-[#294538]">{{ motorcycle.name }}</h2><p class="mt-1 truncate text-sm text-[#748579]">{{ [motorcycle.brand, motorcycle.model].filter(Boolean).join(' ') || 'Merek dan model belum diisi' }}</p><p v-if="motorcycle.license_plate" class="mt-1 text-xs font-semibold text-[#819186]">{{ motorcycle.license_plate }}</p></div>
                </div>
                <div class="mx-5 flex items-center gap-3 rounded-2xl bg-[#f7faf6] p-4"><Gauge :size="20" class="shrink-0 text-[#4d8268]" aria-hidden="true" /><div><p class="text-xs text-[#839187]">Odometer saat ini</p><p class="mt-0.5 text-lg font-bold text-[#294538]">{{ motorcycle.current_odometer_km.toLocaleString('id-ID') }} km</p></div></div>
                <div class="px-5 pb-4 pt-3"><p v-if="motorcycle.next_due_km === null" class="flex items-center gap-2 text-sm text-[#748579]"><Wrench :size="16" aria-hidden="true" /> Belum ada jadwal perawatan</p><p v-else-if="(motorcycle.km_until_next_service ?? 0) <= 0" class="text-sm font-semibold text-[#a45340]">Servis terdekat lewat {{ Math.abs(motorcycle.km_until_next_service ?? 0).toLocaleString('id-ID') }} km</p><p v-else class="text-sm text-[#60766a]">{{ (motorcycle.km_until_next_service ?? 0).toLocaleString('id-ID') }} km menuju servis terdekat</p><p class="mt-1 text-xs text-[#8a988e]">{{ motorcycle.active_maintenance_count }} item perawatan aktif</p></div>
                <div class="border-t border-[#edf0eb] px-5 py-3"><Link :href="`/motorcycles/${motorcycle.id}`" class="inline-flex min-h-10 items-center text-sm font-bold text-[#256958] hover:underline">Buka catatan motor</Link></div>
            </article>
        </section>

        <section v-else class="rounded-3xl border border-dashed border-[#cbdacf] bg-white px-6 py-14 text-center"><Bike :size="38" class="mx-auto text-[#8caf97]" aria-hidden="true" /><h2 class="mt-4 text-lg font-bold text-[#294538]">Belum ada motor</h2><p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-[#748579]">Tambahkan motor untuk mulai mencatat odometer dan mengatur pengingat servis berdasarkan kilometer.</p><Link href="/motorcycles/create" class="primary-button mt-5"><Plus :size="17" aria-hidden="true" /> Tambah motor</Link></section>
    </AppLayout>
</template>
