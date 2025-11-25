<template>
  <div class="p-4">
    <h1 class="text-[2rem] font-bold text-[#1a1d29] my-0 mb-2">Dashboard</h1>
    <p class="text-gray-500 my-0 mb-8 text-lg">Manage your Championship Court resources</p>

    <div class="grid grid-cols-[repeat(auto-fit,minmax(280px,1fr))] gap-6 mb-12">
      <div
        class="bg-white rounded-xl p-6 flex items-start gap-4 shadow-[0_1px_3px_rgba(0,0,0,0.1)] transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_4px_12px_rgba(0,0,0,0.15)]"
      >
        <div
          class="text-[2.5rem] w-[60px] h-[60px] flex items-center justify-center bg-brand-gradient rounded-xl"
        >
          👤
        </div>
        <div class="flex-1">
          <h3 class="my-0 mb-2 text-sm text-gray-500 uppercase tracking-wide font-semibold">
            Players
          </h3>
          <p class="my-0 mb-3 text-[2rem] font-bold text-[#1a1d29]">{{ playerCount }}</p>
          <router-link
            to="/admin/players"
            class="text-blue-500 no-underline text-sm font-medium transition-colors duration-200 hover:text-blue-600"
            >Manage Players →</router-link
          >
        </div>
      </div>

      <div
        class="bg-white rounded-xl p-6 flex items-start gap-4 shadow-[0_1px_3px_rgba(0,0,0,0.1)] transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_4px_12px_rgba(0,0,0,0.15)]"
      >
        <div
          class="text-[2.5rem] w-[60px] h-[60px] flex items-center justify-center bg-brand-gradient rounded-xl"
        >
          🏆
        </div>
        <div class="flex-1">
          <h3 class="my-0 mb-2 text-sm text-gray-500 uppercase tracking-wide font-semibold">
            Programs
          </h3>
          <p class="my-0 mb-3 text-[2rem] font-bold text-[#1a1d29]">{{ programCount }}</p>
          <router-link
            to="/admin/programs"
            class="text-blue-500 no-underline text-sm font-medium transition-colors duration-200 hover:text-blue-600"
            >Manage Programs →</router-link
          >
        </div>
      </div>

      <div
        class="bg-white rounded-xl p-6 flex items-start gap-4 shadow-[0_1px_3px_rgba(0,0,0,0.1)] transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_4px_12px_rgba(0,0,0,0.15)]"
      >
        <div
          class="text-[2.5rem] w-[60px] h-[60px] flex items-center justify-center bg-brand-gradient rounded-xl"
        >
          📦
        </div>
        <div class="flex-1">
          <h3 class="my-0 mb-2 text-sm text-gray-500 uppercase tracking-wide font-semibold">
            Packs
          </h3>
          <p class="my-0 mb-3 text-[2rem] font-bold text-[#1a1d29]">{{ packCount }}</p>
          <router-link
            to="/admin/packs"
            class="text-blue-500 no-underline text-sm font-medium transition-colors duration-200 hover:text-blue-600"
            >Manage Packs →</router-link
          >
        </div>
      </div>

      <div
        class="bg-white rounded-xl p-6 flex items-start gap-4 shadow-[0_1px_3px_rgba(0,0,0,0.1)] transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_4px_12px_rgba(0,0,0,0.15)]"
      >
        <div
          class="text-[2.5rem] w-[60px] h-[60px] flex items-center justify-center bg-brand-gradient rounded-xl"
        >
          📚
        </div>
        <div class="flex-1">
          <h3 class="my-0 mb-2 text-sm text-gray-500 uppercase tracking-wide font-semibold">
            Collections
          </h3>
          <p class="my-0 mb-3 text-[2rem] font-bold text-[#1a1d29]">{{ collectionCount }}</p>
          <router-link
            to="/admin/collections"
            class="text-blue-500 no-underline text-sm font-medium transition-colors duration-200 hover:text-blue-600"
            >Manage Collections →</router-link
          >
        </div>
      </div>
    </div>

    <div>
      <h2 class="text-2xl font-semibold text-[#1a1d29] my-0 mb-6">Quick Actions</h2>
      <div class="grid grid-cols-[repeat(auto-fit,minmax(200px,1fr))] gap-4">
        <router-link
          to="/admin/players/create"
          class="block py-4 px-6 bg-brand-gradient text-white no-underline rounded-lg font-semibold text-center transition-all duration-200 shadow-[0_2px_4px_rgba(102,126,234,0.3)] hover:-translate-y-0.5 hover:shadow-[0_4px_12px_rgba(102,126,234,0.4)]"
        >
          + Add New Player
        </router-link>
        <router-link
          to="/admin/programs/create"
          class="block py-4 px-6 bg-brand-gradient text-white no-underline rounded-lg font-semibold text-center transition-all duration-200 shadow-[0_2px_4px_rgba(102,126,234,0.3)] hover:-translate-y-0.5 hover:shadow-[0_4px_12px_rgba(102,126,234,0.4)]"
        >
          + Add New Program
        </router-link>
        <router-link
          to="/admin/packs/create"
          class="block py-4 px-6 bg-brand-gradient text-white no-underline rounded-lg font-semibold text-center transition-all duration-200 shadow-[0_2px_4px_rgba(102,126,234,0.3)] hover:-translate-y-0.5 hover:shadow-[0_4px_12px_rgba(102,126,234,0.4)]"
        >
          + Add New Pack
        </router-link>
        <router-link
          to="/admin/collections/create"
          class="block py-4 px-6 bg-brand-gradient text-white no-underline rounded-lg font-semibold text-center transition-all duration-200 shadow-[0_2px_4px_rgba(102,126,234,0.3)] hover:-translate-y-0.5 hover:shadow-[0_4px_12px_rgba(102,126,234,0.4)]"
        >
          + Add New Collection
        </router-link>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useAdminStore } from "@/stores/admin";

const adminStore = useAdminStore();
const playerCount = ref(0);
const programCount = ref(0);
const packCount = ref(0);
const collectionCount = ref(0);

onMounted(async () => {
  // Fetch counts from each resource
  await adminStore.fetchPlayers(1, "", 1);
  await adminStore.fetchPrograms(1, "", 1);
  await adminStore.fetchPacks(1, "", 1);
  await adminStore.fetchCollections(1, "", 1);

  playerCount.value = adminStore.playersPagination?.total || 0;
  programCount.value = adminStore.programsPagination?.total || 0;
  packCount.value = adminStore.packsPagination?.total || 0;
  collectionCount.value = adminStore.collectionsPagination?.total || 0;
});
</script>

<style scoped>
/* No additional styles needed - all converted to Tailwind */
</style>
