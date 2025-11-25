<script setup>
import { ref, onMounted, computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import api from "../services/api";
import PlayerCard from "../components/PlayerCard.vue";
import StatsModal from "../components/StatsModal.vue";

const route = useRoute();
const router = useRouter();

const lineup = ref(null);
const loading = ref(false);
const error = ref(null);
const editingName = ref(false);
const newName = ref("");

// Player selection modal
const showPlayerModal = ref(false);
const selectedPosition = ref(null);
const availableCards = ref([]);
const loadingCards = ref(false);

// Filter options
const filterTeam = ref("");
const filterTier = ref("");
const filterPosition = ref("");
const filterCollection = ref("");
const searchQuery = ref("");

// Available filter options
const teams = ref([]);
const tiers = ref([]);
const filterPositions = ref(["PG", "SG", "SF", "PF", "C"]);
const collections = ref([]);

// Stats modal state
const showStatsModal = ref(false);
const selectedUserCardId = ref(null);

const lineupPositions = [
  { key: "PG", label: "Point Guard" },
  { key: "SG", label: "Shooting Guard" },
  { key: "SF", label: "Small Forward" },
  { key: "PF", label: "Power Forward" },
  { key: "C", label: "Center" },
  { key: "6", label: "6th Man" },
  { key: "7", label: "7th Man" },
  { key: "8", label: "8th Man" },
  { key: "9", label: "9th Man" },
  { key: "10", label: "10th Man" },
  { key: "11", label: "11th Man" },
  { key: "12", label: "12th Man" },
  { key: "13", label: "13th Man" },
  { key: "14", label: "14th Man" },
];

const starterPositions = computed(() => lineupPositions.slice(0, 5));
const benchPositions = computed(() => lineupPositions.slice(5));

// Filtered and grouped cards
const filteredCards = computed(() => {
  let filtered = availableCards.value;

  // Exclude players already in the lineup
  if (lineup.value && lineup.value.slots) {
    const playersInLineup = new Set();
    lineup.value.slots.forEach((slot) => {
      if (slot.user_card && slot.user_card.player) {
        // Use nba_id for NBA players, or player id for custom players
        const playerId = slot.user_card.player.nba_id || slot.user_card.player.id;
        playersInLineup.add(playerId);
      }
    });

    filtered = filtered.filter((card) => {
      const playerId = card.player.nba_id || card.player.id;
      return !playersInLineup.has(playerId);
    });
  }

  // Apply team filter
  if (filterTeam.value) {
    filtered = filtered.filter((card) => card.player.team === filterTeam.value);
  }

  // Apply tier filter
  if (filterTier.value) {
    filtered = filtered.filter((card) => card.player.card_tier === filterTier.value);
  }

  // Apply position filter
  if (filterPosition.value) {
    filtered = filtered.filter((card) => card.player.position === filterPosition.value);
  }

  // Apply collection filter
  if (filterCollection.value) {
    filtered = filtered.filter(
      (card) => card.player.collection && card.player.collection.name === filterCollection.value
    );
  }

  // Apply search query
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(
      (card) =>
        card.player.name.toLowerCase().includes(query) ||
        card.player.team.toLowerCase().includes(query)
    );
  }

  return filtered;
});

const getSlotForPosition = (position) => {
  return lineup.value?.slots?.find((slot) => slot.position === position);
};

const loadLineup = async () => {
  loading.value = true;
  error.value = null;
  try {
    const response = await api.getLineup(route.params.id);
    if (response.data.success) {
      lineup.value = response.data.lineup;
      newName.value = lineup.value.name;
    }
  } catch (err) {
    console.error("Error loading lineup:", err);
    error.value = "Failed to load lineup. Please try again.";
  } finally {
    loading.value = false;
  }
};

const updateLineupName = async () => {
  if (!newName.value.trim() || newName.value === lineup.value.name) {
    editingName.value = false;
    newName.value = lineup.value.name;
    return;
  }

  try {
    const response = await api.updateLineup(lineup.value.id, newName.value);
    if (response.data.success) {
      lineup.value.name = newName.value;
      editingName.value = false;
    }
  } catch (err) {
    console.error("Error updating lineup name:", err);
    error.value = "Failed to update lineup name.";
  }
};

const openPlayerSelection = async (position) => {
  selectedPosition.value = position;
  showPlayerModal.value = true;
  loadingCards.value = true;

  // Reset filters
  filterTeam.value = "";
  filterTier.value = "";
  filterPosition.value = "";
  filterCollection.value = "";
  searchQuery.value = "";

  try {
    const response = await api.getCollection();
    if (response.data.success) {
      // Sort by overall rating
      const allCards = response.data.cards.sort(
        (a, b) => b.player.overall_rating - a.player.overall_rating
      );

      // Get list of NBA IDs already in the lineup
      const usedNbaIds = lineup.value.slots
        .filter((slot) => slot.user_card_id !== null && slot.user_card)
        .map((slot) => slot.user_card.player.nba_id)
        .filter((id) => id !== null && id !== ""); // Filter out null/empty IDs

      // Filter out cards whose NBA player is already in lineup
      // If a card has no nba_id, allow it (custom/program players)
      availableCards.value = allCards.filter((card) => {
        const nbaId = card.player.nba_id;
        if (!nbaId) return true; // Allow cards without nba_id
        return !usedNbaIds.includes(nbaId);
      });

      // Extract unique values for filters
      teams.value = [...new Set(availableCards.value.map((card) => card.player.team))].sort();
      tiers.value = [...new Set(availableCards.value.map((card) => card.player.card_tier))].sort();

      // Get unique collections by name (deduplicate Live Series, etc.)
      const collectionMap = new Map();
      availableCards.value.forEach((card) => {
        if (card.player.collection_id && card.player.collection) {
          const collectionName = card.player.collection.name;
          if (!collectionMap.has(collectionName)) {
            collectionMap.set(collectionName, {
              name: collectionName,
            });
          }
        }
      });
      collections.value = Array.from(collectionMap.values()).sort((a, b) =>
        a.name.localeCompare(b.name)
      );
    }
  } catch (err) {
    console.error("Error loading cards:", err);
    error.value = "Failed to load cards.";
  } finally {
    loadingCards.value = false;
  }
};

const selectPlayer = async (userCardId) => {
  try {
    const response = await api.updateLineupSlot(
      lineup.value.id,
      selectedPosition.value,
      userCardId
    );
    if (response.data.success) {
      // Update the slot in the lineup
      const slotIndex = lineup.value.slots.findIndex((s) => s.position === selectedPosition.value);
      if (slotIndex !== -1) {
        lineup.value.slots[slotIndex] = response.data.slot;
      }
      showPlayerModal.value = false;
      selectedPosition.value = null;
    }
  } catch (err) {
    console.error("Error updating slot:", err);
    if (err.response?.data?.message) {
      error.value = err.response.data.message;
    } else {
      error.value = "Failed to update slot.";
    }
  }
};

const removePlayer = async (position) => {
  try {
    const response = await api.updateLineupSlot(lineup.value.id, position, null);
    if (response.data.success) {
      // Update the slot in the lineup
      const slotIndex = lineup.value.slots.findIndex((s) => s.position === position);
      if (slotIndex !== -1) {
        lineup.value.slots[slotIndex] = response.data.slot;
      }
    }
  } catch (err) {
    console.error("Error removing player:", err);
    error.value = "Failed to remove player.";
  }
};

const handleViewStats = (userCardId) => {
  selectedUserCardId.value = userCardId;
  showStatsModal.value = true;
};

const closeStatsModal = () => {
  showStatsModal.value = false;
  selectedUserCardId.value = null;
};

onMounted(() => {
  loadLineup();
});
</script>

<template>
  <div class="w-full p-4">
    <div class="mb-6">
      <router-link
        to="/lineups"
        class="text-brand-primary no-underline font-semibold transition-colors duration-200 hover:text-brand-secondary"
        >← Back to Lineups</router-link
      >
    </div>

    <div v-if="error" class="p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 mb-4">
      {{ error }}
    </div>

    <div v-if="loading" class="text-center py-16">
      <div
        class="w-[50px] h-[50px] border-4 border-gray-200 border-t-brand-primary rounded-full animate-spin mx-auto mb-4"
      ></div>
      <p>Loading lineup...</p>
    </div>

    <div v-else-if="lineup">
      <div class="mb-8">
        <div v-if="!editingName" class="flex items-center gap-4">
          <h1 class="text-4xl font-extrabold bg-brand-gradient bg-clip-text text-transparent">
            {{ lineup.name }}
          </h1>
          <button
            class="bg-gray-200 border-none w-9 h-9 rounded-lg text-xl cursor-pointer text-gray-600 transition-all duration-200 hover:bg-brand-primary hover:text-white"
            @click="editingName = true"
            title="Edit name"
          >
            ✎
          </button>
        </div>
        <div v-else>
          <input
            v-model="newName"
            type="text"
            maxlength="50"
            class="text-4xl font-extrabold p-2 border-2 border-brand-primary rounded-lg w-full max-w-[500px] focus:outline-none"
            @keyup.enter="updateLineupName"
            @blur="updateLineupName"
            autofocus
          />
        </div>
      </div>

      <!-- Starters -->
      <div class="mb-12">
        <h2 class="text-2xl font-bold mb-4 text-gray-800">Starters</h2>
        <div class="grid grid-cols-[repeat(auto-fill,minmax(320px,1fr))] gap-10">
          <div v-for="pos in starterPositions" :key="pos.key" class="flex flex-col items-center">
            <div class="font-bold text-lg text-brand-primary mb-2">{{ pos.key }}</div>
            <div
              class="bg-white border-2 border-dashed border-gray-400 rounded-xl w-full max-w-[320px] cursor-pointer transition-all duration-200 relative hover:border-brand-primary hover:-translate-y-0.5 hover:shadow-[0_4px_12px_rgba(102,126,234,0.2)]"
              :class="{
                'border-solid border-brand-primary': getSlotForPosition(pos.key)?.user_card,
              }"
              @click="openPlayerSelection(pos.key)"
            >
              <div
                v-if="!getSlotForPosition(pos.key)?.user_card"
                class="flex flex-col items-center justify-center h-[420px] text-gray-400"
              >
                <div class="text-5xl mb-2">+</div>
                <div class="font-semibold text-sm">Add Player</div>
              </div>
              <div v-else class="relative p-2">
                <button
                  class="absolute top-4 right-[44px] bg-white/90 border-none w-7 h-7 rounded-full text-2xl leading-none cursor-pointer text-gray-600 transition-all duration-200 z-10 shadow-[0_2px_4px_rgba(0,0,0,0.1)] hover:bg-red-50 hover:text-red-700"
                  @click.stop="removePlayer(pos.key)"
                  title="Remove player"
                >
                  ×
                </button>
                <button
                  class="absolute top-4 right-4 bg-brand-primary/95 border-none w-7 h-7 rounded-full text-base leading-none cursor-pointer text-white transition-all duration-200 z-10 shadow-[0_2px_4px_rgba(0,0,0,0.1)] flex items-center justify-center hover:bg-[rgb(90,103,216)] hover:scale-110"
                  @click.stop="handleViewStats(getSlotForPosition(pos.key).user_card.id)"
                  title="View stats"
                >
                  📊
                </button>
                <PlayerCard
                  :player="getSlotForPosition(pos.key).user_card.player"
                  :player-xp="getSlotForPosition(pos.key).user_card.player_xp || 0"
                  :games-played="getSlotForPosition(pos.key).user_card.games_played || 0"
                  :owned="true"
                  :show-lock-button="false"
                />
              </div>
            </div>
            <div class="text-sm text-gray-600 mt-2 text-center">{{ pos.label }}</div>
          </div>
        </div>
      </div>

      <!-- Bench -->
      <div class="mb-12">
        <h2 class="text-2xl font-bold mb-4 text-gray-800">Bench</h2>
        <div class="grid grid-cols-[repeat(auto-fill,minmax(320px,1fr))] gap-10">
          <div v-for="pos in benchPositions" :key="pos.key" class="flex flex-col items-center">
            <div class="font-bold text-lg text-brand-primary mb-2">{{ pos.key }}</div>
            <div
              class="bg-white border-2 border-dashed border-gray-400 rounded-xl w-full max-w-[320px] cursor-pointer transition-all duration-200 relative hover:border-brand-primary hover:-translate-y-0.5 hover:shadow-[0_4px_12px_rgba(102,126,234,0.2)]"
              :class="{
                'border-solid border-brand-primary': getSlotForPosition(pos.key)?.user_card,
              }"
              @click="openPlayerSelection(pos.key)"
            >
              <div
                v-if="!getSlotForPosition(pos.key)?.user_card"
                class="flex flex-col items-center justify-center h-[420px] text-gray-400"
              >
                <div class="text-5xl mb-2">+</div>
                <div class="font-semibold text-sm">Add Player</div>
              </div>
              <div v-else class="relative p-2">
                <button
                  class="absolute top-4 right-[44px] bg-white/90 border-none w-7 h-7 rounded-full text-2xl leading-none cursor-pointer text-gray-600 transition-all duration-200 z-10 shadow-[0_2px_4px_rgba(0,0,0,0.1)] hover:bg-red-50 hover:text-red-700"
                  @click.stop="removePlayer(pos.key)"
                  title="Remove player"
                >
                  ×
                </button>
                <button
                  class="absolute top-4 right-4 bg-brand-primary/95 border-none w-7 h-7 rounded-full text-base leading-none cursor-pointer text-white transition-all duration-200 z-10 shadow-[0_2px_4px_rgba(0,0,0,0.1)] flex items-center justify-center hover:bg-[rgb(90,103,216)] hover:scale-110"
                  @click.stop="handleViewStats(getSlotForPosition(pos.key).user_card.id)"
                  title="View stats"
                >
                  📊
                </button>
                <PlayerCard
                  :player="getSlotForPosition(pos.key).user_card.player"
                  :player-xp="getSlotForPosition(pos.key).user_card.player_xp || 0"
                  :games-played="getSlotForPosition(pos.key).user_card.games_played || 0"
                  :owned="true"
                  :show-lock-button="false"
                />
              </div>
            </div>
            <div class="text-sm text-gray-600 mt-2 text-center">{{ pos.label }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Player Selection Modal -->
    <div
      v-if="showPlayerModal"
      class="fixed inset-0 bg-black/50 flex items-center justify-center z-[1000] p-8"
      @click="showPlayerModal = false"
    >
      <div
        class="bg-white rounded-xl w-[95%] max-w-[1400px] max-h-[90vh] flex flex-col shadow-[0_8px_32px_rgba(0,0,0,0.2)]"
        @click.stop
      >
        <div class="flex justify-between items-center p-6 border-b border-gray-300">
          <h2 class="m-0 text-gray-800">Select Player for {{ selectedPosition }}</h2>
          <button
            class="bg-gray-200 border-none w-9 h-9 rounded-full text-2xl leading-none cursor-pointer text-gray-600 transition-all duration-200 hover:bg-red-50 hover:text-red-700"
            @click="showPlayerModal = false"
          >
            ×
          </button>
        </div>

        <div v-if="loadingCards" class="py-16 px-8 text-center">
          <div
            class="w-[50px] h-[50px] border-4 border-gray-200 border-t-brand-primary rounded-full animate-spin mx-auto mb-4"
          ></div>
          <p>Loading players...</p>
        </div>

        <div v-else class="flex-1 overflow-y-auto flex flex-col">
          <!-- Filters -->
          <div class="p-6 border-b border-gray-200 bg-gray-50">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
              <!-- Search -->
              <input
                v-model="searchQuery"
                type="text"
                placeholder="Search players..."
                class="px-4 py-2 border-2 border-gray-300 rounded-lg text-sm focus:outline-none focus:border-brand-primary transition-colors duration-200"
              />

              <!-- Team Filter -->
              <select
                v-model="filterTeam"
                class="px-4 py-2 border-2 border-gray-300 rounded-lg text-sm focus:outline-none focus:border-brand-primary transition-colors duration-200"
              >
                <option value="">All Teams</option>
                <option v-for="team in teams" :key="team" :value="team">{{ team }}</option>
              </select>

              <!-- Tier Filter -->
              <select
                v-model="filterTier"
                class="px-4 py-2 border-2 border-gray-300 rounded-lg text-sm focus:outline-none focus:border-brand-primary transition-colors duration-200"
              >
                <option value="">All Tiers</option>
                <option v-for="tier in tiers" :key="tier" :value="tier">{{ tier }}</option>
              </select>

              <!-- Position Filter -->
              <select
                v-model="filterPosition"
                class="px-4 py-2 border-2 border-gray-300 rounded-lg text-sm focus:outline-none focus:border-brand-primary transition-colors duration-200"
              >
                <option value="">All Positions</option>
                <option v-for="pos in filterPositions" :key="pos" :value="pos">{{ pos }}</option>
              </select>

              <!-- Collection Filter -->
              <select
                v-model="filterCollection"
                class="px-4 py-2 border-2 border-gray-300 rounded-lg text-sm focus:outline-none focus:border-brand-primary transition-colors duration-200"
              >
                <option value="">All Collections</option>
                <option
                  v-for="collection in collections"
                  :key="collection.name"
                  :value="collection.name"
                >
                  {{ collection.name }}
                </option>
              </select>
            </div>

            <!-- Active Filters Summary -->
            <div
              v-if="searchQuery || filterTeam || filterTier || filterPosition || filterCollection"
              class="mt-3 flex flex-wrap gap-2"
            >
              <button
                v-if="searchQuery"
                @click="searchQuery = ''"
                class="px-3 py-1 bg-brand-primary text-white text-xs rounded-full flex items-center gap-1 hover:bg-brand-secondary transition-colors duration-200"
              >
                Search: "{{ searchQuery }}" <span class="font-bold">×</span>
              </button>
              <button
                v-if="filterTeam"
                @click="filterTeam = ''"
                class="px-3 py-1 bg-brand-primary text-white text-xs rounded-full flex items-center gap-1 hover:bg-brand-secondary transition-colors duration-200"
              >
                Team: {{ filterTeam }} <span class="font-bold">×</span>
              </button>
              <button
                v-if="filterTier"
                @click="filterTier = ''"
                class="px-3 py-1 bg-brand-primary text-white text-xs rounded-full flex items-center gap-1 hover:bg-brand-secondary transition-colors duration-200"
              >
                Tier: {{ filterTier }} <span class="font-bold">×</span>
              </button>
              <button
                v-if="filterPosition"
                @click="filterPosition = ''"
                class="px-3 py-1 bg-brand-primary text-white text-xs rounded-full flex items-center gap-1 hover:bg-brand-secondary transition-colors duration-200"
              >
                Position: {{ filterPosition }} <span class="font-bold">×</span>
              </button>
              <button
                v-if="filterCollection"
                @click="filterCollection = ''"
                class="px-3 py-1 bg-brand-primary text-white text-xs rounded-full flex items-center gap-1 hover:bg-brand-secondary transition-colors duration-200"
              >
                Collection: {{ filterCollection }} <span class="font-bold">×</span>
              </button>
            </div>
          </div>

          <!-- Results Count -->
          <div class="px-6 py-3 bg-gray-100 border-b border-gray-200 text-sm text-gray-600">
            Showing {{ filteredCards.length }} card{{ filteredCards.length !== 1 ? "s" : "" }}
          </div>

          <!-- Player Cards Grid -->
          <div v-if="filteredCards.length === 0" class="py-16 px-8 text-center text-gray-500">
            <p>No players match your filters.</p>
          </div>

          <div v-else class="flex-1 overflow-y-auto p-6">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
              <div
                v-for="card in filteredCards"
                :key="card.id"
                class="flex flex-col items-center gap-2 cursor-pointer transition-transform duration-200 hover:scale-105"
                @click="selectPlayer(card.id)"
              >
                <div class="w-full max-w-[320px] mx-auto">
                  <PlayerCard
                    :player="card.player"
                    :player-xp="card.player_xp || 0"
                    :games-played="card.games_played || 0"
                    :owned="true"
                    :show-lock-button="false"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Stats Modal -->
    <StatsModal
      :show="showStatsModal"
      :user-card-id="selectedUserCardId"
      @close="closeStatsModal"
    />
  </div>
</template>
