<script setup>
import { ref, onMounted, onActivated, computed, watch } from "vue";
import { useRoute } from "vue-router";
import api from "../services/api";
import PlayerCard from "../components/PlayerCard.vue";
import StatsModal from "../components/StatsModal.vue";

const route = useRoute();

// Navigation state
const currentLevel = ref(1); // 1 = collections, 2 = teams, 3 = cards
const selectedCollection = ref(null);
const selectedTeam = ref(null);

// Data
const collections = ref([]);
const teams = ref([]);
const cards = ref([]);
const progress = ref(null);
const collectionInfo = ref(null);
const rewards = ref([]);

const loading = ref(false);
const error = ref(null);

// Stats modal state
const showStatsModal = ref(false);
const selectedUserCardId = ref(null);

// Get team logo URL from 2kratings
const getTeamLogoUrl = (teamName, collectionType = "team") => {
  if (collectionType === "team" || selectedCollection.value === "Live Series") {
    // For NBA teams, use 2kratings
    const formatted = teamName.replace(/ /g, "-");
    return `https://www.2kratings.com/wp-content/uploads/${formatted}-Current-Logo.svg`;
  }
  // For other collection types, you could return different logo sources
  // For now, return a placeholder or default logo
  return null;
};

// Breadcrumb navigation
const breadcrumbs = computed(() => {
  const crumbs = [{ label: "Collections", level: 1 }];
  if (currentLevel.value >= 2) {
    crumbs.push({ label: selectedCollection.value, level: 2 });
  }
  if (currentLevel.value >= 3 && selectedTeam.value) {
    crumbs.push({ label: selectedTeam.value, level: 3 });
  }
  return crumbs;
});

// Load collections (Level 1)
const loadCollections = async () => {
  loading.value = true;
  error.value = null;
  try {
    const response = await api.getCollections();
    if (response.data.success) {
      collections.value = response.data.collections;
    }
  } catch (err) {
    console.error("Error loading collections:", err);
    error.value = "Failed to load collections. Please try again.";
  } finally {
    loading.value = false;
  }
};

// Load teams or cards based on collection type
const loadTeams = async (collectionName) => {
  loading.value = true;
  error.value = null;
  selectedCollection.value = collectionName;

  // Find the collection to check if it has sub-collections
  const collection = collections.value.find((c) => c.name === collectionName);

  try {
    if (collection && !collection.has_sub_collections) {
      // Skip to level 3 (cards) for collections without sub-collections
      await loadCollectionCards(collectionName);
    } else {
      // Go to level 2 (teams) for collections with sub-collections
      currentLevel.value = 2;
      const response = await api.getCollectionTeams(collectionName);
      if (response.data.success) {
        teams.value = response.data.teams;
      }
      loading.value = false;
    }
  } catch (err) {
    console.error("Error loading collection:", err);
    error.value = "Failed to load collection. Please try again.";
    loading.value = false;
  }
};

// Load all cards directly for a collection (no sub-collections)
const loadCollectionCards = async (collectionName) => {
  loading.value = true;
  error.value = null;
  currentLevel.value = 3;
  selectedTeam.value = null; // No team for direct collections

  try {
    const response = await api.getCollectionCards(collectionName);
    if (response.data.success) {
      cards.value = response.data.cards;
      progress.value = response.data.progress;
      collectionInfo.value = response.data.collection_info;
      rewards.value = response.data.rewards;
      
      // Debug logging
      const ownedCards = cards.value.filter(c => c.owned);
      console.log(`Loaded ${cards.value.length} cards for ${collectionName}, ${ownedCards.length} owned`);
      ownedCards.forEach(card => {
        console.log(`Card: ${card.player.name}, count: ${card.count}, locked: ${card.locked}, tradeable: ${card.is_tradeable}, sell_price: ${card.sell_price}, buy_price: ${card.buy_price}`);
      });
    }
  } catch (err) {
    console.error("Error loading collection cards:", err);
    error.value = "Failed to load cards. Please try again.";
  } finally {
    loading.value = false;
  }
};

// Load team cards (Level 3)
const loadTeamCards = async (teamName) => {
  loading.value = true;
  error.value = null;
  try {
    const response = await api.getTeamCards(selectedCollection.value, teamName);
    if (response.data.success) {
      cards.value = response.data.cards;
      progress.value = response.data.progress;
      collectionInfo.value = response.data.collection;
      rewards.value = response.data.rewards;
      selectedTeam.value = teamName;
      currentLevel.value = 3;
      
      // Debug logging
      const ownedCards = cards.value.filter(c => c.owned);
      console.log(`Loaded ${cards.value.length} cards, ${ownedCards.length} owned`);
      ownedCards.forEach(card => {
        console.log(`Card: ${card.player.name}, count: ${card.count}, locked: ${card.locked}, tradeable: ${card.is_tradeable}, sell_price: ${card.sell_price}, buy_price: ${card.buy_price}`);
      });
    }
  } catch (err) {
    console.error("Error loading team cards:", err);
    error.value = "Failed to load team cards. Please try again.";
  } finally {
    loading.value = false;
  }
};

// Handle lock/unlock
const handleLock = async (userCardId) => {
  try {
    const response = await api.lockCard(userCardId);
    if (response.data.success) {
      // Update the card in the list
      const cardIndex = cards.value.findIndex((c) => c.user_card_id === userCardId);
      if (cardIndex !== -1) {
        cards.value[cardIndex].locked = true;
      }
      // Update progress
      progress.value.locked_cards++;
      progress.value.percentage = (
        (progress.value.locked_cards / progress.value.total_cards) *
        100
      ).toFixed(1);
      progress.value.completed = progress.value.locked_cards === progress.value.total_cards;

      // Show earned rewards messages
      if (response.data.earned_rewards && response.data.earned_rewards.length > 0) {
        let rewardMessages = response.data.earned_rewards
          .map((reward) => {
            if (reward.reward_type === "stubs") {
              return `${reward.reward_quantity.toLocaleString()} Stubs`;
            } else if (reward.reward_type === "player_card") {
              return `${reward.player?.name || "Player Card"}`;
            } else if (reward.reward_type === "pack") {
              return `${reward.reward_quantity}x ${reward.pack?.name || "Pack"}`;
            }
            return "Reward";
          })
          .join(", ");
        alert(`🎉 Reward Earned!\n\nYou received: ${rewardMessages}`);
      }

      // Show completion message if collection is complete
      if (response.data.collection_completed) {
        alert(`🎉 Congratulations! You've completed the collection!`);
      }
    }
  } catch (err) {
    console.error("Error locking card:", err);
    alert("Failed to lock card. Please try again.");
  }
};

const handleUnlock = async (userCardId) => {
  try {
    const response = await api.unlockCard(userCardId);
    if (response.data.success) {
      // Update the card in the list
      const cardIndex = cards.value.findIndex((c) => c.user_card_id === userCardId);
      if (cardIndex !== -1) {
        cards.value[cardIndex].locked = false;
      }
      // Update progress
      progress.value.locked_cards--;
      progress.value.percentage = (
        (progress.value.locked_cards / progress.value.total_cards) *
        100
      ).toFixed(1);
      progress.value.completed = false;
    }
  } catch (err) {
    console.error("Error unlocking card:", err);
    alert("Failed to unlock card. Please try again.");
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

const handleSell = async (userCardId) => {
  const card = cards.value.find((c) => c.user_card_id === userCardId);
  if (!card) return;

  let confirmMessage = `Sell ${card.player.name} for ${card.sell_price?.toLocaleString() || 0} stubs?`;
  
  if (card.locked && card.count > 1) {
    confirmMessage += `\n\n⚠️ This card is locked in your collection, but you have ${card.count} total copies.\nSelling one will leave you with ${card.count - 1} (1 still locked in).`;
  } else if (card.count > 1) {
    confirmMessage += `\n\nYou have ${card.count} copies. Selling one will leave you with ${card.count - 1}.`;
  }
  
  if (!confirm(confirmMessage)) return;

  try {
    const response = await api.sellCard(userCardId);
    if (response.data.success) {
      // Update card count or remove from owned cards
      if (response.data.remaining_count > 0) {
        card.count = response.data.remaining_count;
      } else {
        // Card was fully sold, update to not owned
        card.owned = false;
        card.locked = false;
        card.user_card_id = null;
        card.count = 0;
      }

      // Update progress if needed
      if (progress.value && response.data.card_deleted) {
        progress.value.owned_cards--;
        progress.value.percentage = ((progress.value.owned_cards / progress.value.total_cards) * 100).toFixed(1);
      }

      alert(`✅ Card sold for ${response.data.stubs_earned.toLocaleString()} stubs!\n\nNew balance: ${response.data.new_balance.toLocaleString()} stubs`);
    }
  } catch (err) {
    console.error("Error selling card:", err);
    const errorMessage = err.response?.data?.message || "Failed to sell card. Please try again.";
    alert(`❌ ${errorMessage}`);
  }
};

const handleBuy = async (playerId) => {
  const card = cards.value.find((c) => c.player.id === playerId);
  if (!card) return;

  const confirmMessage = `Buy ${card.player.name} for ${card.buy_price?.toLocaleString() || 0} stubs?`;
  
  if (!confirm(confirmMessage)) return;

  try {
    const response = await api.buyCard(playerId);
    if (response.data.success) {
      // Update card to owned
      card.owned = true;
      card.count = response.data.card_count;
      card.user_card_id = response.data.user_card_id;

      // Update progress if needed
      if (progress.value) {
        progress.value.owned_cards++;
        progress.value.percentage = ((progress.value.owned_cards / progress.value.total_cards) * 100).toFixed(1);
      }

      alert(`✅ Card purchased for ${response.data.stubs_spent.toLocaleString()} stubs!\n\nNew balance: ${response.data.new_balance.toLocaleString()} stubs`);
    }
  } catch (err) {
    console.error("Error buying card:", err);
    const errorMessage = err.response?.data?.message || "Failed to buy card. Please try again.";
    alert(`❌ ${errorMessage}`);
  }
};

// Navigate to level
const navigateToLevel = (level) => {
  currentLevel.value = level;
  if (level === 1) {
    selectedCollection.value = null;
    selectedTeam.value = null;
    teams.value = [];
    cards.value = [];
  } else if (level === 2) {
    selectedTeam.value = null;
    cards.value = [];
  }
};

// Helper to format reward display text
const getRewardDisplayText = (reward) => {
  if (reward.reward_type === "stubs") {
    return `${reward.reward_quantity.toLocaleString()} Stubs`;
  } else if (reward.reward_type === "player_card") {
    return reward.player ? `${reward.player.name}` : "Player Card";
  } else if (reward.reward_type === "pack") {
    return reward.pack
      ? `${reward.reward_quantity}x ${reward.pack.name}`
      : `${reward.reward_quantity}x Pack`;
  }
  return "Reward";
};

// Check if a reward has been claimed
const isRewardClaimed = (reward) => {
  return reward.claimed === true;
};

// Get sorted rewards
const sortedRewards = computed(() => {
  if (!rewards.value) return [];
  return [...rewards.value].sort((a, b) => a.required_cards - b.required_cards);
});

// Refresh current view data
const refreshCurrentView = async () => {
  if (currentLevel.value === 3) {
    // Reload card view
    if (selectedTeam.value) {
      await loadTeamCards(selectedTeam.value);
    } else if (selectedCollection.value) {
      await loadCollectionCards(selectedCollection.value);
    }
  } else if (currentLevel.value === 2 && selectedCollection.value) {
    // Reload teams view
    const response = await api.getCollectionTeams(selectedCollection.value);
    if (response.data.success) {
      teams.value = response.data.teams;
    }
  } else if (currentLevel.value === 1) {
    // Reload collections list
    await loadCollections();
  }
};

onMounted(() => {
  loadCollections();
});

// Refresh data when component is reactivated (navigating back from another page)
onActivated(() => {
  console.log('CollectionView activated, refreshing data');
  refreshCurrentView();
});

// Watch for route changes to refresh data when user navigates back
watch(() => route.path, (newPath, oldPath) => {
  // Only refresh if navigating TO the collection page (not away from it)
  if (newPath === '/collection' && oldPath && oldPath !== '/collection') {
    console.log('Route changed to collection, refreshing data');
    refreshCurrentView();
  }
}, { immediate: false });
</script>

<template>
  <div class="collections-container">
    <h1 class="page-title">Collections</h1>

    <!-- Breadcrumb Navigation -->
    <div class="breadcrumbs">
      <button
        v-for="(crumb, index) in breadcrumbs"
        :key="index"
        @click="navigateToLevel(crumb.level)"
        class="breadcrumb"
        :class="{ active: currentLevel === crumb.level }"
      >
        {{ crumb.label }}
      </button>
    </div>

    <!-- Error Message -->
    <div v-if="error" class="error-message">{{ error }}</div>

    <!-- Loading State -->
    <div v-if="loading" class="loading">Loading...</div>

    <!-- Level 1: Collections -->
    <div v-if="currentLevel === 1 && !loading" class="collections-grid">
      <div
        v-for="collection in collections"
        :key="collection.name"
        class="collection-card"
        :class="`collection-type-${collection.type}`"
        @click="loadTeams(collection.name)"
      >
        <div class="collection-header">
          <h2 class="collection-name">{{ collection.name }}</h2>
          <span class="collection-type-badge">{{ collection.type }}</span>
        </div>
        <p class="collection-description">
          {{ collection.description || "Complete this collection to earn rewards!" }}
        </p>

        <div v-if="collection.progress" class="collection-progress">
          <div class="progress-info">
            <span class="progress-label">Progress</span>
            <span class="progress-value"
              >{{ collection.progress.locked }} / {{ collection.progress.total }}</span
            >
          </div>
          <div class="progress-bar">
            <div
              class="progress-fill"
              :style="{ width: collection.progress.percentage + '%' }"
            ></div>
          </div>
          <div class="progress-percentage">{{ collection.progress.percentage }}% Complete</div>
        </div>

        <button class="btn-view">
          <span v-if="collection.progress?.completed">✓ Completed</span>
          <span v-else>View Collection</span>
        </button>
      </div>
    </div>

    <!-- Level 2: Teams -->
    <div v-if="currentLevel === 2 && !loading" class="teams-grid">
      <div
        v-for="team in teams"
        :key="team.collection_id"
        class="team-card"
        @click="loadTeamCards(team.name)"
      >
        <div class="team-card-header">
          <div class="team-logo-container">
            <img
              :src="getTeamLogoUrl(team.name)"
              :alt="team.name"
              class="team-logo-large"
              @error="(e) => (e.target.style.opacity = '0.3')"
            />
          </div>
          <h3 class="team-name">{{ team.name }}</h3>
        </div>
        <div class="team-progress">
          <div class="progress-bar">
            <div class="progress-fill" :style="{ width: team.percentage + '%' }"></div>
          </div>
          <div class="progress-stats">
            <span class="locked">{{ team.locked_cards }} Locked</span>
            <span class="total">/ {{ team.total_cards }} Total</span>
          </div>
          <div class="progress-percentage">{{ team.percentage }}%</div>
        </div>
        <div v-if="team.completed" class="completed-badge">✓ COMPLETED</div>
      </div>
    </div>

    <!-- Level 3: Team Cards -->
    <div v-if="currentLevel === 3 && !loading" class="team-cards-view">
      <!-- Top Info Bar -->
      <div class="top-info-bar">
        <div class="team-header">
          <h2 class="sidebar-title">
            {{ collectionInfo?.team || collectionInfo?.name || selectedCollection }}
          </h2>
          <p class="sidebar-subtitle">
            {{ collectionInfo?.team ? collectionInfo?.name : collectionInfo?.description || "" }}
          </p>
        </div>

        <div class="progress-compact">
          <div class="progress-stats-row">
            <div class="stat-compact">
              <span class="stat-label">Locked In:</span>
              <span class="stat-value"
                >{{ progress?.locked_cards }} / {{ progress?.total_cards }}</span
              >
            </div>
            <div class="stat-compact">
              <span class="stat-label">Owned:</span>
              <span class="stat-value"
                >{{ progress?.owned_cards }} / {{ progress?.total_cards }}</span
              >
            </div>
            <div class="stat-compact">
              <span class="stat-value-large">{{ progress?.percentage }}%</span>
            </div>
          </div>
          <div class="progress-bar-compact">
            <div class="progress-fill-compact" :style="{ width: progress?.percentage + '%' }"></div>
          </div>
        </div>

        <div v-if="progress?.completed" class="completed-badge-compact">
          <span class="completed-icon">🎉</span>
          <span class="completed-text">COMPLETED!</span>
        </div>
      </div>

      <!-- Rewards Section -->
      <div v-if="sortedRewards.length > 0" class="rewards-section">
        <h3 class="rewards-title">Collection Rewards</h3>
        <div class="rewards-grid">
          <div
            v-for="reward in sortedRewards"
            :key="reward.id"
            class="reward-card"
            :class="{ claimed: isRewardClaimed(reward) }"
          >
            <div class="reward-card-header">
              <span class="reward-icon">🏆</span>
              <span v-if="isRewardClaimed(reward)" class="claimed-badge">CLAIMED</span>
            </div>
            <div class="reward-requirement">{{ reward.required_cards }} Cards</div>
            <div class="reward-name">{{ getRewardDisplayText(reward) }}</div>
            <div v-if="reward.description" class="reward-description">{{ reward.description }}</div>
            <div class="reward-progress-bar">
              <div
                class="reward-progress-fill"
                :style="{
                  width: Math.min(100, (progress.locked_cards / reward.required_cards) * 100) + '%',
                }"
              ></div>
            </div>
            <div class="reward-progress-text">
              {{ Math.min(progress.locked_cards, reward.required_cards) }} /
              {{ reward.required_cards }}
            </div>
          </div>
        </div>
      </div>

      <!-- Cards Grid - Full Width -->
      <div class="cards-section">
        <div class="cards-grid">
          <PlayerCard
            v-for="card in cards"
            :key="card.player.id"
            :player="card.player"
            :greyed="!card.owned"
            :owned="card.owned"
            :locked="card.locked"
            :user-card-id="card.user_card_id"
            :show-stats="card.owned"
            :player-xp="card.player_xp || 0"
            :games-played="card.games_played || 0"
            :count="card.count || 1"
            :sell-price="card.sell_price"
            :buy-price="card.buy_price"
            :is-tradeable="card.is_tradeable !== false"
            @lock="handleLock"
            @unlock="handleUnlock"
            @view-stats="handleViewStats"
            @sell="handleSell"
            @buy="handleBuy"
          />
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

<style scoped>
.collections-container {
  padding: 2rem;
  max-width: 1600px;
  margin: 0 auto;
}

.page-title {
  text-align: center;
  font-size: 3rem;
  font-weight: 800;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  margin-bottom: 2rem;
}

.breadcrumbs {
  display: flex;
  gap: 0.5rem;
  align-items: center;
  margin-bottom: 2rem;
  flex-wrap: wrap;
}

.breadcrumb {
  padding: 0.6rem 1.2rem;
  background: #f0f0f0;
  border: none;
  border-radius: 8px;
  font-size: 0.95rem;
  font-weight: 600;
  color: #666;
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
}

.breadcrumb:not(:last-child)::after {
  content: "›";
  position: absolute;
  right: -0.8rem;
  color: #999;
  font-size: 1.2rem;
}

.breadcrumb:hover:not(.active) {
  background: #e0e0e0;
  color: #333;
}

.breadcrumb.active {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  cursor: default;
}

.error-message {
  background: #fee;
  color: #c33;
  padding: 1rem;
  border-radius: 8px;
  text-align: center;
  margin-bottom: 2rem;
}

.loading {
  text-align: center;
  font-size: 1.5rem;
  color: #666;
  padding: 4rem;
}

/* Level 1: Collections */
.collections-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 2rem;
}

.collection-card {
  background: white;
  padding: 2rem;
  border-radius: 16px;
  color: #333;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  display: flex;
  flex-direction: column;
  gap: 1rem;
  border: 3px solid transparent;
}

.collection-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
}

/* Different colors for different collection types */
.collection-type-team {
  border-color: #667eea;
}

.collection-type-season {
  border-color: #f093fb;
}

.collection-type-special {
  border-color: #4facfe;
}

.collection-type-legends {
  border-color: #ffd700;
}

.collection-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
}

.collection-name {
  font-size: 1.8rem;
  font-weight: 800;
  margin: 0;
  color: #333;
}

.collection-type-badge {
  padding: 0.4rem 0.8rem;
  background: rgba(102, 126, 234, 0.1);
  border-radius: 8px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #667eea;
}

.collection-type-season .collection-type-badge {
  background: rgba(240, 147, 251, 0.1);
  color: #c471ed;
}

.collection-type-special .collection-type-badge {
  background: rgba(79, 172, 254, 0.1);
  color: #4facfe;
}

.collection-type-legends .collection-type-badge {
  background: rgba(255, 215, 0, 0.1);
  color: #d4af37;
}

.collection-description {
  font-size: 1rem;
  margin: 0;
  color: #666;
  line-height: 1.6;
}

.collection-progress {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  padding: 1rem;
  background: #f7fafc;
  border-radius: 12px;
}

.progress-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.progress-label {
  font-size: 0.85rem;
  font-weight: 600;
  color: #666;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.progress-value {
  font-size: 0.9rem;
  font-weight: 700;
  color: #333;
}

.collection-progress .progress-bar {
  width: 100%;
  height: 8px;
  background: #e0e0e0;
  border-radius: 4px;
  overflow: hidden;
}

.collection-progress .progress-fill {
  height: 100%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  transition: width 0.3s ease;
}

.progress-percentage {
  text-align: right;
  font-size: 0.85rem;
  font-weight: 700;
  color: #667eea;
}

.btn-view {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  padding: 0.85rem 2rem;
  border-radius: 10px;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 1rem;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
  margin-top: auto;
}

.btn-view:hover {
  background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(102, 126, 234, 0.6);
}

/* Level 2: Teams */
.teams-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 1.5rem;
}

.team-card {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
  cursor: pointer;
  transition: all 0.3s ease;
  position: relative;
}

.team-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
}

.team-card-header {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1rem;
  margin-bottom: 1rem;
}

.team-logo-container {
  width: 80px;
  height: 80px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(0, 0, 0, 0.03);
  border-radius: 50%;
  padding: 12px;
}

.team-logo-large {
  width: 100%;
  height: 100%;
  object-fit: contain;
  transition: opacity 0.3s ease;
}

.team-name {
  font-size: 1.2rem;
  font-weight: 700;
  color: #333;
  text-align: center;
}

.team-progress {
  margin-top: 1rem;
}

.progress-bar {
  width: 100%;
  height: 8px;
  background: #e0e0e0;
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 0.5rem;
}

.progress-fill {
  height: 100%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  transition: width 0.3s ease;
}

.progress-stats {
  display: flex;
  gap: 0.5rem;
  font-size: 0.9rem;
  color: #666;
  margin-bottom: 0.25rem;
}

.progress-stats .locked {
  font-weight: 600;
  color: #667eea;
}

.progress-percentage {
  font-size: 0.85rem;
  color: #999;
  text-align: right;
}

.completed-badge {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
  color: white;
  padding: 0.4rem 0.8rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 700;
  letter-spacing: 0.5px;
}

/* Level 3: Team Cards */
.team-cards-view {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

/* Top Info Bar */
.top-info-bar {
  background: white;
  padding: 1.5rem 2rem;
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
  display: grid;
  grid-template-columns: 1fr auto auto auto;
  gap: 2rem;
  align-items: center;
}

.team-header {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.sidebar-title {
  font-size: 1.5rem;
  font-weight: 800;
  color: #333;
  margin: 0;
}

.sidebar-subtitle {
  font-size: 0.85rem;
  color: #666;
  margin: 0;
}

/* Compact Progress */
.progress-compact {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  min-width: 250px;
}

.progress-stats-row {
  display: flex;
  gap: 1.5rem;
  align-items: center;
}

.stat-compact {
  display: flex;
  gap: 0.5rem;
  align-items: center;
  font-size: 0.85rem;
}

.stat-label {
  color: #666;
  font-weight: 500;
}

.stat-value {
  color: #333;
  font-weight: 700;
}

.stat-value-large {
  font-size: 1.3rem;
  font-weight: 800;
  color: #667eea;
}

.progress-bar-compact {
  width: 100%;
  height: 8px;
  background: #e0e0e0;
  border-radius: 4px;
  overflow: hidden;
}

.progress-fill-compact {
  height: 100%;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  transition: width 0.3s ease;
}

/* Compact Rewards */
.rewards-compact {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  background: linear-gradient(135deg, #fef5e7 0%, #fdebd0 100%);
  border-radius: 8px;
  font-size: 0.85rem;
  color: #666;
  white-space: nowrap;
}

.reward-icon {
  font-size: 1.2rem;
}

.reward-text {
  font-weight: 500;
}

/* Compact Completed Badge */
.completed-badge-compact {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.25rem;
  background: linear-gradient(135deg, #e6fffa 0%, #b2f5ea 100%);
  border-radius: 8px;
}

.completed-icon {
  font-size: 1.2rem;
}

.completed-text {
  font-size: 0.9rem;
  font-weight: 800;
  color: #38a169;
  letter-spacing: 0.5px;
}

.cards-section {
  min-height: 400px;
  width: 100%;
  overflow: visible;
}

.cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 2rem;
  width: 100%;
}

/* Responsive */
@media (max-width: 1024px) {
  .top-info-bar {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }

  .progress-compact {
    min-width: unset;
  }

  .rewards-compact,
  .completed-badge-compact {
    justify-content: center;
  }
}

@media (max-width: 768px) {
  .collections-container {
    padding: 1rem;
  }

  .page-title {
    font-size: 2rem;
  }

  .cards-grid {
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 1.5rem;
  }
}

/* Rewards Section */
.rewards-section {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  margin-bottom: 2rem;
}

.rewards-title {
  margin: 0 0 1.5rem 0;
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a1d29;
}

.rewards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 1rem;
}

.reward-card {
  background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
  border: 2px solid #e5e7eb;
  border-radius: 12px;
  padding: 1.25rem;
  transition: all 0.3s ease;
  position: relative;
}

.reward-card.claimed {
  background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
  border-color: #3b82f6;
}

.reward-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.reward-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.reward-card .reward-icon {
  font-size: 1.75rem;
}

.claimed-badge {
  background: #10b981;
  color: white;
  font-size: 0.7rem;
  font-weight: 700;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  letter-spacing: 0.5px;
}

.reward-requirement {
  font-size: 0.875rem;
  color: #6b7280;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.reward-name {
  font-size: 1.1rem;
  font-weight: 700;
  color: #1a1d29;
  margin-bottom: 0.5rem;
}

.reward-description {
  font-size: 0.875rem;
  color: #6b7280;
  margin-bottom: 0.75rem;
}

.reward-progress-bar {
  background: rgba(0, 0, 0, 0.1);
  height: 8px;
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 0.5rem;
}

.reward-progress-fill {
  background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
  height: 100%;
  transition: width 0.3s ease;
  border-radius: 4px;
}

.reward-card.claimed .reward-progress-fill {
  background: linear-gradient(90deg, #10b981 0%, #059669 100%);
}

.reward-progress-text {
  font-size: 0.875rem;
  color: #6b7280;
  text-align: right;
  font-weight: 600;
}
</style>
