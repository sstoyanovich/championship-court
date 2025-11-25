<script setup>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import api from "../services/api";

const router = useRouter();
const lineups = ref([]);
const loading = ref(false);
const error = ref(null);
const showCreateModal = ref(false);
const newLineupName = ref("");

const loadLineups = async () => {
  loading.value = true;
  error.value = null;
  try {
    const response = await api.getLineups();
    if (response.data.success) {
      lineups.value = response.data.lineups;
    }
  } catch (err) {
    console.error("Error loading lineups:", err);
    error.value = "Failed to load lineups. Please try again.";
  } finally {
    loading.value = false;
  }
};

const createLineup = async () => {
  if (!newLineupName.value.trim()) {
    return;
  }

  try {
    const response = await api.createLineup(newLineupName.value);
    if (response.data.success) {
      showCreateModal.value = false;
      newLineupName.value = "";
      router.push(`/lineups/${response.data.lineup.id}`);
    }
  } catch (err) {
    console.error("Error creating lineup:", err);
    error.value = "Failed to create lineup. Please try again.";
  }
};

const deleteLineup = async (lineupId) => {
  if (!confirm("Are you sure you want to delete this lineup?")) {
    return;
  }

  try {
    const response = await api.deleteLineup(lineupId);
    if (response.data.success) {
      await loadLineups();
    }
  } catch (err) {
    console.error("Error deleting lineup:", err);
    error.value = "Failed to delete lineup. Please try again.";
  }
};

const getFilledSlotsCount = (lineup) => {
  return lineup.slots?.filter((slot) => slot.user_card_id !== null).length || 0;
};

onMounted(() => {
  loadLineups();
});
</script>

<template>
  <div class="w-full p-4">
    <div class="flex justify-between items-center mb-8">
      <h1 class="text-4xl font-extrabold bg-brand-gradient bg-clip-text text-transparent">
        My Lineups
      </h1>
      <button
        class="flex items-center gap-2 px-6 py-3 bg-brand-gradient text-white border-none rounded-lg font-semibold cursor-pointer transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_4px_12px_rgba(102,126,234,0.4)]"
        @click="showCreateModal = true"
      >
        <span class="text-2xl leading-none">+</span>
        Create New Lineup
      </button>
    </div>

    <div v-if="error" class="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 mb-4">
      {{ error }}
    </div>

    <div v-if="loading" class="text-center py-16">
      <div
        class="w-[50px] h-[50px] border-4 border-gray-200 border-t-brand-primary rounded-full animate-spin mx-auto mb-4"
      ></div>
      <p>Loading lineups...</p>
    </div>

    <div v-else-if="lineups.length === 0" class="text-center py-16">
      <div class="text-6xl mb-4">🏀</div>
      <h2 class="text-2xl mb-2 text-gray-800">No lineups yet</h2>
      <p class="text-gray-600 mb-8">Create your first lineup to get started!</p>
      <button
        class="px-8 py-4 bg-brand-gradient text-white border-none rounded-lg font-semibold text-base cursor-pointer transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_4px_12px_rgba(102,126,234,0.4)]"
        @click="showCreateModal = true"
      >
        Create Your First Lineup
      </button>
    </div>

    <div v-else class="grid grid-cols-[repeat(auto-fill,minmax(300px,1fr))] gap-8">
      <div
        v-for="lineup in lineups"
        :key="lineup.id"
        class="bg-white rounded-xl p-6 shadow-[0_2px_8px_rgba(0,0,0,0.1)] cursor-pointer transition-all duration-200 hover:-translate-y-1 hover:shadow-[0_4px_16px_rgba(0,0,0,0.15)]"
        @click="router.push(`/lineups/${lineup.id}`)"
      >
        <div class="flex justify-between items-start mb-4">
          <h3 class="text-xl font-bold text-gray-800 flex-1 break-words">
            {{ lineup.name }}
          </h3>
          <button
            class="bg-gray-100 border-none w-7 h-7 rounded-full text-2xl leading-none cursor-pointer text-gray-600 transition-all duration-200 flex-shrink-0 ml-2 hover:bg-red-50 hover:text-red-700"
            @click.stop="deleteLineup(lineup.id)"
            title="Delete lineup"
          >
            ×
          </button>
        </div>
        <div class="mb-4">
          <div class="flex justify-between mb-2">
            <span class="text-gray-600 text-sm">Filled Slots</span>
            <span class="font-bold text-brand-primary">{{ getFilledSlotsCount(lineup) }}/14</span>
          </div>
          <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden">
            <div
              class="h-full bg-brand-gradient transition-all duration-300"
              :style="{
                width: `${(getFilledSlotsCount(lineup) / 14) * 100}%`,
              }"
            ></div>
          </div>
        </div>
        <div
          class="flex items-center justify-center gap-2 pt-4 border-t border-gray-100 text-brand-primary font-semibold text-sm"
        >
          <span class="text-xl">→</span>
          Edit Lineup
        </div>
      </div>
    </div>

    <!-- Create Lineup Modal -->
    <div
      v-if="showCreateModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-[1000]"
      @click="showCreateModal = false"
    >
      <div
        class="bg-white rounded-xl p-8 w-[90%] max-w-[500px] shadow-[0_8px_32px_rgba(0,0,0,0.2)]"
        @click.stop
      >
        <h2 class="mb-6 text-gray-800">Create New Lineup</h2>
        <input
          v-model="newLineupName"
          type="text"
          placeholder="Enter lineup name..."
          maxlength="50"
          class="w-full p-3 border-2 border-gray-300 rounded-lg text-base mb-6 transition-colors duration-200 focus:outline-none focus:border-brand-primary"
          @keyup.enter="createLineup"
          autofocus
        />
        <div class="flex gap-4 justify-end">
          <button
            class="px-6 py-3 border-none rounded-lg font-semibold cursor-pointer transition-all duration-200 bg-gray-200 text-gray-600 hover:bg-gray-300"
            @click="showCreateModal = false"
          >
            Cancel
          </button>
          <button
            class="px-6 py-3 border-none rounded-lg font-semibold cursor-pointer transition-all duration-200 bg-brand-gradient text-white hover:-translate-y-0.5 hover:shadow-[0_4px_12px_rgba(102,126,234,0.4)] disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none"
            @click="createLineup"
            :disabled="!newLineupName.trim()"
          >
            Create
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* No additional styles needed - all converted to Tailwind */
</style>
