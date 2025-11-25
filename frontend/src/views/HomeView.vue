<script setup>
import { useRouter } from "vue-router";

const router = useRouter();
</script>

<template>
  <div class="flex justify-center items-center min-h-[80vh] p-8">
    <div class="text-center max-w-[600px]">
      <h1 class="text-[4rem] font-extrabold bg-brand-gradient bg-clip-text text-transparent mb-4">
        Championship Court
      </h1>
      <p class="text-2xl text-gray-600 mb-12">NBA Card Collection Game</p>
      <div class="flex gap-4 justify-center flex-wrap">
        <button
          class="py-4 px-8 text-lg font-semibold border-none rounded-xl cursor-pointer transition-all duration-300 uppercase tracking-wide bg-brand-gradient text-white shadow-[0_4px_15px_rgba(102,126,234,0.4)] hover:-translate-y-0.5 hover:shadow-[0_6px_20px_rgba(102,126,234,0.6)]"
          @click="router.push('/packs')"
        >
          Pack Store
        </button>
        <button
          class="py-4 px-8 text-lg font-semibold border-none rounded-xl cursor-pointer transition-all duration-300 uppercase tracking-wide bg-gradient-to-br from-pink-400 to-rose-500 text-white shadow-[0_4px_15px_rgba(245,87,108,0.4)] hover:-translate-y-0.5 hover:shadow-[0_6px_20px_rgba(245,87,108,0.6)]"
          @click="router.push('/collection')"
        >
          My Collection
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* No additional styles needed - all converted to Tailwind */
</style>
