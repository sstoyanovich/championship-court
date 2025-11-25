<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="show"
        class="fixed top-0 left-0 w-full h-full bg-black/75 flex items-center justify-center z-[1000] p-4"
        @click="closeModal"
      >
        <div
          class="bg-white rounded-3xl max-w-[600px] w-full max-h-[90vh] overflow-hidden flex flex-col shadow-[0_25px_50px_rgba(0,0,0,0.5)] max-sm:max-h-[95vh]"
          @click.stop
        >
          <div
            class="flex justify-between items-center py-6 px-8 border-b-2 border-slate-200 max-sm:py-4 max-sm:px-6"
          >
            <h2 class="text-2xl font-bold text-gray-900 m-0 max-sm:text-xl">Player Statistics</h2>
            <button
              class="w-9 h-9 rounded-lg border-none bg-gray-50 text-gray-600 cursor-pointer flex items-center justify-center transition-all duration-200 hover:bg-gray-200 hover:text-gray-800"
              @click="closeModal"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                class="w-5 h-5"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
            </button>
          </div>

          <div class="flex-1 overflow-y-auto p-8 max-sm:p-6">
            <div
              v-if="loading"
              class="flex flex-col items-center justify-center py-12 text-gray-500"
            >
              <div class="spinner"></div>
              <p>Loading stats...</p>
            </div>

            <div v-else-if="error" class="text-center py-12 text-red-600">
              <p>{{ error }}</p>
              <button
                @click="loadStats"
                class="mt-4 py-3 px-6 bg-brand-primary text-white border-none rounded-lg font-semibold cursor-pointer transition-all duration-200 hover:bg-brand-secondary hover:-translate-y-px"
              >
                Retry
              </button>
            </div>

            <div v-else-if="stats" class="flex flex-col gap-6">
              <StatsCard
                :player-name="stats.player_name"
                :games-played="stats.games_played"
                :total-minutes="stats.total_stats.minutes"
                :total-points="stats.total_stats.points"
                :total-rebounds="stats.total_stats.rebounds"
                :total-assists="stats.total_stats.assists"
                :total-steals="stats.total_stats.steals"
                :total-blocks="stats.total_stats.blocks"
                :total-turnovers="stats.total_stats.turnovers"
                :total-fgm="stats.total_stats.fgm"
                :total-fga="stats.total_stats.fga"
                :total3pm="stats.total_stats['3pm']"
                :total3pa="stats.total_stats['3pa']"
              />

              <div
                v-if="stats.games_played === 0"
                class="bg-gradient-to-br from-orange-50 to-orange-100 border-l-4 border-orange-500 rounded-xl p-6 text-center"
              >
                <p class="text-orange-900 m-0 mb-4 text-base">
                  This player hasn't played any games yet. Upload game stats to start tracking
                  performance!
                </p>
                <router-link
                  to="/stats/upload"
                  class="inline-block py-3 px-6 bg-brand-gradient text-white no-underline rounded-lg font-semibold transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_10px_25px_rgba(102,126,234,0.4)]"
                >
                  Upload Game Stats
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, watch } from "vue";
import StatsCard from "./StatsCard.vue";
import api from "@/services/api";

const props = defineProps({
  show: {
    type: Boolean,
    required: true,
  },
  userCardId: {
    type: Number,
    default: null,
  },
});

const emit = defineEmits(["close"]);

const loading = ref(false);
const error = ref(null);
const stats = ref(null);

watch(
  () => props.show,
  (newVal) => {
    if (newVal && props.userCardId) {
      loadStats();
    }
  }
);

const loadStats = async () => {
  if (!props.userCardId) return;

  loading.value = true;
  error.value = null;
  stats.value = null;

  try {
    const response = await api.getCardStats(props.userCardId);
    if (response.data.success) {
      stats.value = response.data.data;
    } else {
      error.value = "Failed to load stats";
    }
  } catch (err) {
    console.error("Error loading stats:", err);
    error.value = err.response?.data?.message || "Failed to load stats. Please try again.";
  } finally {
    loading.value = false;
  }
};

const closeModal = () => {
  emit("close");
};
</script>

<style scoped>
.spinner {
  width: 48px;
  height: 48px;
  border: 4px solid #e2e8f0;
  border-top-color: #667eea;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin-bottom: 1rem;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

/* Modal transition animations */
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-active > div,
.modal-leave-active > div {
  transition: transform 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-from > div,
.modal-leave-to > div {
  transform: scale(0.9);
}
</style>
