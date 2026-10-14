<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';

const unavailable = ref(false);
let stopNetworkError: (() => void) | undefined;
let stopSuccess: (() => void) | undefined;

function updateConnection() {
    unavailable.value = !navigator.onLine;
}

onMounted(() => {
    updateConnection();
    window.addEventListener('offline', updateConnection);
    window.addEventListener('online', updateConnection);
    stopNetworkError = router.on('networkError', () => { unavailable.value = true; });
    stopSuccess = router.on('success', () => { unavailable.value = false; });
});

onUnmounted(() => {
    window.removeEventListener('offline', updateConnection);
    window.removeEventListener('online', updateConnection);
    stopNetworkError?.();
    stopSuccess?.();
});
</script>

<template>
    <div v-if="unavailable" role="alert" class="rounded-2xl border border-[#e6c6a9] bg-[#fff4e8] px-5 py-4 text-sm font-semibold text-[#794a2f]">Koneksi terputus atau server tidak merespons. Periksa jaringanmu, lalu coba lagi.</div>
</template>
