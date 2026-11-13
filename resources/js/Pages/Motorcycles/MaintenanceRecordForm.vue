<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Wrench } from '@lucide/vue';
import AppLayout from '../../Layouts/AppLayout.vue';

type Bike = { id: number; name: string; current_odometer_km: number };
type Item = { id: number; name: string; kind: 'part' | 'service' };
type Option = { id: number; name: string };

const props = defineProps<{
    motorcycle: Bike;
    maintenanceItems: Item[];
    selectedItemId: number | null;
    wallets: Option[];
    categories: Option[];
}>();

const today = (() => {
    const date = new Date();
    const localDate = new Date(date.getTime() - date.getTimezoneOffset() * 60_000);

    return localDate.toISOString().slice(0, 10);
})();

const form = useForm({
    maintenance_item_id: props.selectedItemId ?? '',
    title: '',
    odometer_km: props.motorcycle.current_odometer_km,
    performed_on: today,
    cost: '',
    wallet_id: '',
    category_id: '',
    note: '',
});

function submit() {
    form.post(`/motorcycles/${props.motorcycle.id}/maintenance-records`);
}
</script>

<template>
    <Head title="Catat servis motor" />
    <AppLayout title="Catat servis" :subtitle="`Simpan pekerjaan dan sparepart untuk ${motorcycle.name}.`">
        <Link :href="`/motorcycles/${motorcycle.id}`" class="mb-5 inline-flex min-h-11 items-center gap-2 text-sm font-bold text-[#256958] hover:underline"><ArrowLeft :size="17" aria-hidden="true" /> Kembali ke motor</Link>
        <form class="surface-shadow max-w-2xl space-y-5 rounded-3xl border border-[#e3ebe3] bg-white p-6 sm:p-9" @submit.prevent="submit">
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#e9f4eb] text-[#256958]"><Wrench :size="22" aria-hidden="true" /></span>

            <div v-if="maintenanceItems.length"><label for="record-item" class="mb-2 block text-sm font-semibold">Jadwal sparepart atau perawatan <span class="font-normal text-[#819186]">(opsional)</span></label><select id="record-item" v-model="form.maintenance_item_id" class="field"><option value="">Servis atau sparepart lain</option><option v-for="item in maintenanceItems" :key="item.id" :value="item.id">{{ item.name }} · {{ item.kind === 'part' ? 'Sparepart' : 'Perawatan' }}</option></select><p v-if="form.errors.maintenance_item_id" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.maintenance_item_id }}</p></div>

            <div v-if="!form.maintenance_item_id"><label for="record-title" class="mb-2 block text-sm font-semibold">Nama servis atau sparepart</label><input id="record-title" v-model="form.title" class="field" maxlength="255" placeholder="Contoh: Ganti kampas rem" required /><p v-if="form.errors.title" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.title }}</p></div>

            <div class="grid gap-4 sm:grid-cols-2"><div><label for="record-date" class="mb-2 block text-sm font-semibold">Tanggal servis</label><input id="record-date" v-model="form.performed_on" class="field" type="date" :max="today" required /><p v-if="form.errors.performed_on" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.performed_on }}</p></div><div><label for="record-odometer" class="mb-2 block text-sm font-semibold">Odometer saat servis</label><div class="relative"><input id="record-odometer" v-model.number="form.odometer_km" class="field pr-14" type="number" min="0" max="2000000" step="1" required /><span class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-sm text-[#819186]">km</span></div><p v-if="form.errors.odometer_km" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.odometer_km }}</p></div></div>

            <fieldset class="space-y-4 rounded-2xl border border-[#e7ede6] p-4"><legend class="px-1 text-sm font-bold text-[#294538]">Biaya dan dompet <span class="font-normal text-[#819186]">(opsional)</span></legend><p class="text-xs leading-relaxed text-[#748579]">Hubungkan biaya ke transaksi pengeluaran agar saldo dompet ikut diperbarui.</p>
                <div class="grid gap-4 sm:grid-cols-2"><div><label for="record-cost" class="mb-2 block text-sm font-semibold">Biaya servis</label><input id="record-cost" v-model="form.cost" class="field" type="number" min="0" max="9999999999999.99" step="0.01" placeholder="0" /><p v-if="form.errors.cost" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.cost }}</p></div><div><label for="record-wallet" class="mb-2 block text-sm font-semibold">Potong dari dompet</label><select id="record-wallet" v-model="form.wallet_id" class="field"><option value="">Tidak dihubungkan</option><option v-for="wallet in wallets" :key="wallet.id" :value="wallet.id">{{ wallet.name }}</option></select><p v-if="form.errors.wallet_id" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.wallet_id }}</p></div></div>
                <div v-if="form.wallet_id"><label for="record-category" class="mb-2 block text-sm font-semibold">Kategori pengeluaran</label><select id="record-category" v-model="form.category_id" class="field" required><option value="">Pilih kategori</option><option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option></select><p v-if="form.errors.category_id" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.category_id }}</p></div>
            </fieldset>

            <div><label for="record-note" class="mb-2 block text-sm font-semibold">Catatan <span class="font-normal text-[#819186]">(opsional)</span></label><textarea id="record-note" v-model="form.note" class="field min-h-24" maxlength="5000" placeholder="Detail pekerjaan, merek sparepart, atau bengkel" /><p v-if="form.errors.note" role="alert" class="mt-2 text-sm text-[#b5433b]">{{ form.errors.note }}</p></div>

            <div class="flex flex-wrap gap-3 border-t border-[#edf0eb] pt-5"><button type="submit" class="primary-button" :disabled="form.processing">{{ form.processing ? 'Menyimpan...' : 'Simpan catatan servis' }}</button><Link :href="`/motorcycles/${motorcycle.id}`" class="inline-flex min-h-12 items-center rounded-xl px-5 text-sm font-semibold text-[#61766b] hover:bg-[#f2f6f1]">Batal</Link></div>
        </form>
    </AppLayout>
</template>
