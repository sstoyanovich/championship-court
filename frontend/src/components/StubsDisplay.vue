<template>
  <div
    class="flex items-center gap-2 py-2 px-4 bg-gradient-to-br from-yellow-400 to-yellow-300 rounded-full shadow-[0_2px_8px_rgba(255,215,0,0.3)]"
  >
    <div class="text-xl">💰</div>
    <div class="text-lg font-bold text-gray-800">{{ formatNumber(stubsBalance) }}</div>
    <div class="text-sm text-gray-600 font-semibold">Stubs</div>
  </div>
</template>

<script setup>
import { defineProps } from "vue";

const props = defineProps({
  stubsBalance: {
    type: Number,
    default: 0,
  },
});

const formatNumber = (num) => {
  if (!num) return "0";
  return num.toLocaleString();
};
</script>

<style scoped>
/* No additional styles needed - all converted to Tailwind */
</style>
