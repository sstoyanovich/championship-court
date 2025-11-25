<script setup>
import { ref, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useUserStore } from "../stores/user";
import api from "../services/api";
import PlayerCard from "../components/PlayerCard.vue";

const route = useRoute();
const router = useRouter();
const userStore = useUserStore();

const pack = ref(null);
const cards = ref([]);
const options = ref([]);
const selectedPlayers = ref([]);
const isOpening = ref(false);
const showCards = ref(false);
const currentCardIndex = ref(0);
const isChoicePack = ref(false);
const mustSelect = ref(0);
const purchasedUserPackId = ref(null); // Store user_pack_id from purchase response

const tierColors = {
  common: "#6B7280",
  bronze: "#CD7F32",
  silver: "#C0C0C0",
  gold: "#FFD700",
  emerald: "#50C878",
  sapphire: "#0F52BA",
  amethyst: "#9966CC",
  diamond: "#B9F2FF",
  pink_diamond: "#FF69B4",
};

const openPack = async () => {
  if (isOpening.value) return;

  const packId = route.params.packId;
  const userPackId = route.query.user_pack_id;

  if (!packId) {
    alert("No pack selected!");
    router.push({ name: "pack-selection" });
    return;
  }

  isOpening.value = true;
  showCards.value = false;
  currentCardIndex.value = 0;
  cards.value = [];
  options.value = [];
  selectedPlayers.value = [];

  try {
    const response = await api.openPack(packId, userPackId);
    if (response.data.success) {
      pack.value = response.data.pack;

      // Refresh user balance if this was a purchase from store (not from inventory)
      if (!userPackId) {
        await userStore.fetchUser();
      }

      if (response.data.type === "choice") {
        // Choice pack: show options
        isChoicePack.value = true;
        options.value = response.data.options;
        mustSelect.value = response.data.must_select;

        // Store the user_pack_id from the response (for store purchases)
        if (response.data.user_pack_id) {
          purchasedUserPackId.value = response.data.user_pack_id;
        }

        setTimeout(() => {
          showCards.value = true;
          // Reveal all options at once
          currentCardIndex.value = options.value.length;
        }, 500);
      } else {
        // Standard pack: auto-reveal cards
        isChoicePack.value = false;
        cards.value = response.data.cards;

        setTimeout(() => {
          showCards.value = true;
          revealNextCard();
        }, 500);
      }
    }
  } catch (error) {
    console.error("Error opening pack:", error);

    // Handle specific error messages
    if (error.response?.data?.message) {
      const message = error.response.data.message;
      if (error.response.status === 400 && error.response.data.shortage) {
        alert(
          `Not enough stubs!\n\nRequired: ${error.response.data.required.toLocaleString()} stubs\nYou have: ${error.response.data.current.toLocaleString()} stubs\nNeed: ${error.response.data.shortage.toLocaleString()} more stubs`
        );
      } else {
        alert(message);
      }
    } else {
      alert("Failed to open pack. Please try again.");
    }

    isOpening.value = false;
    router.push({ name: "pack-selection" });
  }
};

const revealNextCard = () => {
  if (currentCardIndex.value < cards.value.length) {
    currentCardIndex.value++;
    if (currentCardIndex.value < cards.value.length) {
      setTimeout(revealNextCard, 800);
    } else {
      setTimeout(() => {
        isOpening.value = false;
      }, 500);
    }
  }
};

const togglePlayerSelection = (player) => {
  const index = selectedPlayers.value.findIndex((p) => p.id === player.id);

  if (index > -1) {
    // Deselect
    selectedPlayers.value.splice(index, 1);
  } else {
    // Select (if not at limit)
    if (selectedPlayers.value.length < mustSelect.value) {
      selectedPlayers.value.push(player);
    }
  }
};

const isSelected = (player) => {
  return selectedPlayers.value.some((p) => p.id === player.id);
};

const confirmSelection = async () => {
  if (selectedPlayers.value.length !== mustSelect.value) {
    alert(`Please select exactly ${mustSelect.value} card(s)!`);
    return;
  }

  try {
    // Use purchasedUserPackId from purchase response, or from route query (inventory)
    const userPackId = route.query.user_pack_id || purchasedUserPackId.value;

    if (!userPackId) {
      alert("Invalid pack selection. Please try again.");
      return;
    }

    const selectedIds = selectedPlayers.value.map((p) => p.id);
    const response = await api.selectCards(pack.value.id, selectedIds, userPackId);

    if (response.data.success) {
      // Convert to standard card reveal
      cards.value = response.data.cards;
      isChoicePack.value = false;
      showCards.value = false;
      currentCardIndex.value = 0;

      setTimeout(() => {
        showCards.value = true;
        revealNextCard();
      }, 300);
    }
  } catch (error) {
    console.error("Error selecting cards:", error);
    alert("Failed to select cards. Please try again.");
  }
};

const backToStore = () => {
  router.push({ name: "pack-selection" });
};

onMounted(() => {
  if (!route.params.packId) {
    router.push({ name: "pack-selection" });
  }
});
</script>

<template>
  <div class="pack-opening-container">
    <button class="btn-back" @click="backToStore">
      <svg
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
        stroke-width="2"
        stroke="currentColor"
        class="back-icon"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"
        />
      </svg>
      Back to Store
    </button>

    <h1 class="page-title">
      {{ pack?.name || "Pack Opening" }}
    </h1>
    <p v-if="pack" class="pack-subtitle">{{ pack.description }}</p>

    <div class="pack-section">
      <!-- Pack box before opening -->
      <div v-if="!showCards" class="pack-image-container">
        <div class="pack-box" :class="{ opening: isOpening }">
          <div class="pack-shimmer"></div>
          <div class="pack-label">{{ pack?.name || "NBA Pack" }}</div>
        </div>
        <button class="btn btn-open" @click="openPack" :disabled="isOpening">
          {{ isOpening ? "Opening..." : "Open Pack" }}
        </button>
      </div>

      <!-- Choice Pack: Show options for selection -->
      <div v-else-if="isChoicePack" class="choice-pack-section">
        <div class="selection-header">
          <h2>Choose Your Cards</h2>
          <div class="selection-counter">
            {{ selectedPlayers.length }} / {{ mustSelect }} selected
          </div>
        </div>

        <div class="cards-grid choice-grid">
          <div
            v-for="(option, index) in options"
            :key="option.id"
            class="card-wrapper choice-card"
            :class="{
              revealed: index < currentCardIndex,
              selected: isSelected(option),
              disabled: !isSelected(option) && selectedPlayers.length >= mustSelect,
            }"
            @click="togglePlayerSelection(option)"
          >
            <div class="selection-indicator">
              <svg
                v-if="isSelected(option)"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="3"
                stroke="currentColor"
                class="check-icon"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
              </svg>
            </div>
            <PlayerCard v-if="option" :player="option" :revealed="index < currentCardIndex" />
          </div>
        </div>

        <div class="choice-actions">
          <button
            class="btn btn-confirm"
            @click="confirmSelection"
            :disabled="selectedPlayers.length !== mustSelect"
          >
            Confirm Selection
          </button>
        </div>
      </div>

      <!-- Standard Pack or after choice: Reveal cards -->
      <div v-else class="cards-reveal">
        <div class="cards-grid">
          <div
            v-for="(card, index) in cards"
            :key="card.id"
            class="card-wrapper"
            :class="{ revealed: index < currentCardIndex }"
          >
            <PlayerCard
              v-if="card.player"
              :player="card.player"
              :revealed="index < currentCardIndex"
            />
          </div>
        </div>
        <div v-if="currentCardIndex >= cards.length" class="action-buttons">
          <button class="btn btn-open" @click="backToStore">Back to Store</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.pack-opening-container {
  padding: 2rem;
  max-width: 1400px;
  margin: 0 auto;
  min-height: 100vh;
}

.btn-back {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  background: rgba(255, 255, 255, 0.1);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 8px;
  color: white;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  margin-bottom: 2rem;
}

.btn-back:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: translateX(-4px);
}

.back-icon {
  width: 20px;
  height: 20px;
}

.page-title {
  text-align: center;
  font-size: 3rem;
  font-weight: 800;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 0.5rem;
}

.pack-subtitle {
  text-align: center;
  font-size: 1.125rem;
  color: #cbd5e1;
  margin-bottom: 3rem;
}

.pack-section {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 500px;
}

.pack-image-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 2rem;
}

.pack-box {
  width: 300px;
  height: 400px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-radius: 20px;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(102, 126, 234, 0.4);
  cursor: pointer;
  transition: transform 0.3s ease;
}

.pack-box:hover {
  transform: scale(1.05);
}

.pack-box.opening {
  animation: shake 0.5s ease-in-out infinite;
}

@keyframes shake {
  0%,
  100% {
    transform: rotate(0deg);
  }
  25% {
    transform: rotate(-5deg);
  }
  75% {
    transform: rotate(5deg);
  }
}

.pack-shimmer {
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.3), transparent);
  animation: shimmer 2s infinite;
}

@keyframes shimmer {
  0% {
    transform: translateX(-100%) translateY(-100%);
  }
  100% {
    transform: translateX(100%) translateY(100%);
  }
}

.pack-label {
  font-size: 1.5rem;
  font-weight: 800;
  color: white;
  text-transform: uppercase;
  letter-spacing: 2px;
  z-index: 1;
  text-align: center;
  padding: 1rem;
}

.btn {
  padding: 1rem 2rem;
  font-size: 1.1rem;
  font-weight: 600;
  border: none;
  border-radius: 12px;
  cursor: pointer;
  transition: all 0.3s ease;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.btn-open {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.btn-open:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
}

.btn-open:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-secondary {
  background: rgba(255, 255, 255, 0.1);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.2);
}

.btn-secondary:hover {
  background: rgba(255, 255, 255, 0.2);
}

.choice-pack-section {
  width: 100%;
}

.selection-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
  padding: 1.5rem;
  background: rgba(102, 126, 234, 0.1);
  border: 2px solid rgba(102, 126, 234, 0.3);
  border-radius: 12px;
}

.selection-header h2 {
  font-size: 2rem;
  font-weight: 700;
  color: white;
  margin: 0;
}

.selection-counter {
  font-size: 1.5rem;
  font-weight: 700;
  color: #667eea;
  padding: 0.5rem 1.5rem;
  background: rgba(102, 126, 234, 0.2);
  border-radius: 8px;
}

.cards-reveal {
  width: 100%;
}

.cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 2rem;
  padding: 2rem;
}

.choice-grid {
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 2.5rem;
}

.card-wrapper {
  opacity: 0;
  transform: scale(0.8) rotateY(180deg);
  transition: all 0.6s ease;
  position: relative;
}

.card-wrapper.revealed {
  opacity: 1;
  transform: scale(1) rotateY(0deg);
}

.choice-card {
  cursor: pointer;
  border-radius: 12px;
  overflow: hidden;
}

.choice-card:hover:not(.disabled) {
  transform: scale(1.05) rotateY(0deg);
}

.choice-card.selected {
  box-shadow: 0 0 0 4px #10b981, 0 10px 30px rgba(16, 185, 129, 0.4);
}

.choice-card.disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.selection-indicator {
  position: absolute;
  top: 10px;
  right: 10px;
  width: 40px;
  height: 40px;
  background: #10b981;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 10;
  opacity: 0;
  transform: scale(0);
  transition: all 0.3s ease;
}

.choice-card.selected .selection-indicator {
  opacity: 1;
  transform: scale(1);
}

.check-icon {
  width: 24px;
  height: 24px;
  color: white;
}

.choice-actions {
  display: flex;
  justify-content: center;
  margin-top: 2rem;
}

.btn-confirm {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  font-size: 1.25rem;
  padding: 1.25rem 3rem;
  box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4);
}

.btn-confirm:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(16, 185, 129, 0.6);
}

.btn-confirm:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.action-buttons {
  display: flex;
  justify-content: center;
  gap: 1rem;
  margin-top: 2rem;
}

@media (max-width: 768px) {
  .page-title {
    font-size: 2rem;
  }

  .cards-grid {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }

  .selection-header {
    flex-direction: column;
    gap: 1rem;
    text-align: center;
  }

  .action-buttons {
    flex-direction: column;
  }

  .btn {
    width: 100%;
  }
}
</style>
