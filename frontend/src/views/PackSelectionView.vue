<script setup>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import api from "../services/api";

const router = useRouter();
const storePacks = ref([]);
const inventory = ref([]);
const loading = ref(true);
const activeTab = ref("store"); // 'store' or 'inventory'

const tierColorMap = {
  bronze: "#CD7F32",
  silver: "#C0C0C0",
  gold: "#FFD700",
  emerald: "#50C878",
  sapphire: "#0F52BA",
  amethyst: "#9966CC",
  diamond: "#B9F2FF",
  pink_diamond: "#FF69B4",
};

const loadPacks = async () => {
  try {
    const response = await api.getPacks();
    if (response.data.success) {
      storePacks.value = response.data.store_packs || [];
      inventory.value = response.data.inventory || [];
    }
  } catch (error) {
    console.error("Error loading packs:", error);
    alert("Failed to load packs. Please try again.");
  } finally {
    loading.value = false;
  }
};

const selectStorePack = (pack) => {
  router.push({
    name: "pack-opening",
    params: { packId: pack.id },
  });
};

const openInventoryPack = (inventoryItem) => {
  // Open the first pack in the group
  const userPackId = inventoryItem.user_pack_ids[0];
  router.push({
    name: "pack-opening",
    params: { packId: inventoryItem.pack.id },
    query: { user_pack_id: userPackId },
  });
};

const getTopTier = (oddsConfig) => {
  // Handle null/undefined odds_config (e.g., player pools packs)
  if (!oddsConfig || typeof oddsConfig !== "object") {
    return "gold";
  }

  // Get the highest tier with meaningful odds
  const tiers = Object.entries(oddsConfig).sort((a, b) => {
    const tierOrder = [
      "bronze",
      "silver",
      "gold",
      "emerald",
      "sapphire",
      "amethyst",
      "diamond",
      "pink_diamond",
    ];
    return tierOrder.indexOf(b[0]) - tierOrder.indexOf(a[0]);
  });
  return tiers[0]?.[0] || "gold";
};

const formatCost = (cost) => {
  return cost.toLocaleString();
};

onMounted(() => {
  loadPacks();
});
</script>

<template>
  <div class="pack-selection-container">
    <div class="header">
      <h1 class="page-title">Pack Store</h1>
      <p class="subtitle">Choose a pack to open and build your collection!</p>
    </div>

    <!-- Tabs -->
    <div class="tabs">
      <button
        class="tab-button"
        :class="{ active: activeTab === 'store' }"
        @click="activeTab = 'store'"
      >
        🏪 Store
      </button>
      <button
        class="tab-button"
        :class="{ active: activeTab === 'inventory' }"
        @click="activeTab = 'inventory'"
      >
        🎁 Inventory
        <span v-if="inventory.length > 0" class="inventory-badge">{{ inventory.length }}</span>
      </button>
    </div>

    <div v-if="loading" class="loading">
      <div class="spinner"></div>
      <p>Loading packs...</p>
    </div>

    <!-- Store Packs Tab -->
    <div v-else-if="activeTab === 'store'" class="packs-grid">
      <div
        v-for="pack in storePacks"
        :key="pack.id"
        class="pack-card"
        :class="pack.type"
        :style="{ '--top-tier-color': tierColorMap[getTopTier(pack.odds_config)] }"
        @click="selectStorePack(pack)"
      >
        <div class="pack-shine"></div>

        <div class="pack-header">
          <h2 class="pack-name">{{ pack.name }}</h2>
          <div class="pack-badge" v-if="pack.type === 'choice'">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke-width="1.5"
              stroke="currentColor"
              class="icon"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
              />
            </svg>
            Choice Pack
          </div>
        </div>

        <p class="pack-description">{{ pack.description }}</p>

        <div class="pack-details">
          <div class="detail-item">
            <span class="detail-label">Cards:</span>
            <span class="detail-value">
              {{ pack.card_count }} card{{ pack.card_count > 1 ? "s" : "" }}
            </span>
          </div>
          <div class="detail-item" v-if="pack.type === 'choice'">
            <span class="detail-label">Choose:</span>
            <span class="detail-value"> {{ pack.choice_count }} of {{ pack.card_count }} </span>
          </div>
        </div>

        <div class="odds-preview" v-if="pack.odds_config">
          <div class="odds-title">Odds:</div>
          <div class="odds-bars">
            <div
              v-for="([tier, percentage], index) in Object.entries(pack.odds_config).slice(0, 4)"
              :key="tier"
              class="odds-bar"
              :style="{
                width: `${Math.max(percentage, 2)}%`,
                backgroundColor: tierColorMap[tier],
                opacity: 0.7 + index * 0.1,
              }"
              :title="`${tier}: ${percentage}%`"
            >
              <span class="odds-label" v-if="percentage > 5">{{ percentage }}%</span>
            </div>
          </div>
        </div>
        <div class="odds-preview" v-else-if="pack.player_pools">
          <div class="odds-title">Player Pools:</div>
          <div class="odds-bars">
            <div
              v-for="(pool, index) in pack.player_pools.slice(0, 4)"
              :key="pool.name"
              class="odds-bar"
              :style="{
                width: `${Math.max(pool.odds, 2)}%`,
                backgroundColor: '#3b82f6',
                opacity: 0.7 + index * 0.1,
              }"
              :title="`${pool.name}: ${pool.odds}%`"
            >
              <span class="odds-label" v-if="pool.odds > 5">{{ pool.odds }}%</span>
            </div>
          </div>
        </div>

        <div class="pack-footer">
          <div class="pack-cost">
            <svg
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke-width="1.5"
              stroke="currentColor"
              class="coin-icon"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
              />
            </svg>
            {{ formatCost(pack.cost) }} Stubs
          </div>
          <button class="btn-open-pack">
            Open Pack
            <svg
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke-width="2"
              stroke="currentColor"
              class="arrow-icon"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Inventory Tab -->
    <div v-else-if="activeTab === 'inventory'" class="inventory-section">
      <div v-if="inventory.length === 0" class="empty-inventory">
        <div class="empty-icon">📦</div>
        <h3>No Packs in Inventory</h3>
        <p>Earn packs from Program rewards or purchase them from the store!</p>
        <button class="btn-goto-store" @click="activeTab = 'store'">Go to Store</button>
      </div>

      <div v-else class="packs-grid">
        <div
          v-for="item in inventory"
          :key="item.pack.id"
          class="pack-card inventory-pack"
          :class="item.pack.type"
          :style="{ '--top-tier-color': tierColorMap[getTopTier(item.pack.odds_config)] }"
          @click="openInventoryPack(item)"
        >
          <div class="pack-shine"></div>

          <!-- Quantity Badge -->
          <div class="quantity-badge">x{{ item.quantity }}</div>

          <div class="pack-header">
            <h2 class="pack-name">{{ item.pack.name }}</h2>
            <div class="pack-badge" v-if="item.pack.type === 'choice'">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="icon"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
                />
              </svg>
              <span>Choice Pack</span>
            </div>
          </div>

          <p class="pack-description">{{ item.pack.description }}</p>

          <div class="pack-details">
            <div class="detail-item">
              <span class="detail-label">Cards:</span>
              <span class="detail-value">
                {{ item.pack.card_count }} card{{ item.pack.card_count > 1 ? "s" : "" }}
              </span>
            </div>
            <div class="detail-item" v-if="item.pack.type === 'choice'">
              <span class="detail-label">Choose:</span>
              <span class="detail-value">
                {{ item.pack.choice_count }} of {{ item.pack.card_count }}
              </span>
            </div>
          </div>

          <div class="odds-preview" v-if="item.pack.odds_config">
            <div class="odds-title">Odds:</div>
            <div class="odds-bars">
              <div
                v-for="([tier, percentage], index) in Object.entries(item.pack.odds_config).slice(
                  0,
                  4
                )"
                :key="tier"
                class="odds-bar"
                :style="{
                  width: `${Math.max(percentage, 2)}%`,
                  backgroundColor: tierColorMap[tier],
                  opacity: 0.7 + index * 0.1,
                }"
                :title="`${tier}: ${percentage}%`"
              >
                <span class="odds-label" v-if="percentage > 5">{{ percentage }}%</span>
              </div>
            </div>
          </div>
          <div class="odds-preview" v-else-if="item.pack.player_pools">
            <div class="odds-title">Player Pools:</div>
            <div class="odds-bars">
              <div
                v-for="(pool, index) in item.pack.player_pools.slice(0, 4)"
                :key="pool.name"
                class="odds-bar"
                :style="{
                  width: `${Math.max(pool.odds, 2)}%`,
                  backgroundColor: '#3b82f6',
                  opacity: 0.7 + index * 0.1,
                }"
                :title="`${pool.name}: ${pool.odds}%`"
              >
                <span class="odds-label" v-if="pool.odds > 5">{{ pool.odds }}%</span>
              </div>
            </div>
          </div>

          <div class="pack-footer">
            <div class="pack-source">
              <span class="source-badge">🎁 From Rewards</span>
            </div>
            <button class="btn-open-pack inventory-open">
              Open Pack
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="2"
                stroke="currentColor"
                class="arrow-icon"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="m8.25 4.5 7.5 7.5-7.5 7.5"
                />
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.pack-selection-container {
  min-height: 100vh;
  padding: 2rem;
  background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
  color: white;
}

.header {
  text-align: center;
  margin-bottom: 3rem;
}

.page-title {
  font-size: 3rem;
  font-weight: bold;
  margin-bottom: 0.5rem;
  background: linear-gradient(to right, #60a5fa, #a78bfa);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.subtitle {
  font-size: 1.25rem;
  color: #9ca3af;
}

.loading {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 4rem;
  gap: 1rem;
}

.spinner {
  width: 50px;
  height: 50px;
  border: 4px solid rgba(255, 255, 255, 0.1);
  border-top-color: #60a5fa;
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.packs-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 2rem;
  max-width: 1400px;
  margin: 0 auto;
}

.pack-card {
  position: relative;
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
  border: 2px solid #334155;
  border-radius: 16px;
  padding: 2rem;
  cursor: pointer;
  transition: all 0.3s ease;
  overflow: hidden;
}

.pack-card::before {
  content: "";
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 4px;
  background: linear-gradient(to right, var(--top-tier-color), transparent);
  opacity: 0.7;
}

.pack-card:hover {
  transform: translateY(-8px) scale(1.02);
  border-color: var(--top-tier-color);
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4), 0 0 30px var(--top-tier-color);
}

.pack-card:hover .pack-shine {
  opacity: 0.15;
}

.pack-shine {
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: linear-gradient(
    45deg,
    transparent 30%,
    rgba(255, 255, 255, 0.1) 50%,
    transparent 70%
  );
  opacity: 0;
  transition: opacity 0.3s ease;
  pointer-events: none;
}

.pack-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 1rem;
  gap: 1rem;
}

.pack-name {
  font-size: 1.75rem;
  font-weight: bold;
  margin: 0;
  color: #f1f5f9;
}

.pack-badge {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.25rem 0.75rem;
  background: linear-gradient(135deg, #8b5cf6, #6366f1);
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 600;
  white-space: nowrap;
}

.pack-badge .icon {
  width: 14px;
  height: 14px;
}

.pack-description {
  color: #cbd5e1;
  margin-bottom: 1.5rem;
  line-height: 1.6;
  min-height: 48px;
}

.pack-details {
  display: flex;
  gap: 1.5rem;
  margin-bottom: 1.5rem;
  padding: 1rem;
  background: rgba(0, 0, 0, 0.3);
  border-radius: 8px;
}

.detail-item {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.detail-label {
  font-size: 0.75rem;
  color: #94a3b8;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.detail-value {
  font-size: 1.125rem;
  font-weight: 600;
  color: #f1f5f9;
}

.odds-preview {
  margin-bottom: 1.5rem;
}

.odds-title {
  font-size: 0.875rem;
  color: #94a3b8;
  margin-bottom: 0.5rem;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.odds-bars {
  display: flex;
  gap: 2px;
  height: 24px;
  background: rgba(0, 0, 0, 0.3);
  border-radius: 4px;
  overflow: hidden;
}

.odds-bar {
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s ease;
  position: relative;
}

.odds-bar:hover {
  opacity: 1 !important;
  transform: scaleY(1.2);
}

.odds-label {
  font-size: 0.625rem;
  font-weight: 700;
  color: white;
  text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
}

.pack-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 1.5rem;
  border-top: 1px solid #334155;
}

.pack-cost {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 1.25rem;
  font-weight: bold;
  color: #fbbf24;
}

.coin-icon {
  width: 24px;
  height: 24px;
}

.btn-open-pack {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  background: linear-gradient(135deg, #3b82f6, #2563eb);
  border: none;
  border-radius: 8px;
  color: white;
  font-weight: 600;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-open-pack:hover {
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  transform: translateX(4px);
}

.arrow-icon {
  width: 16px;
  height: 16px;
  transition: transform 0.2s ease;
}

.btn-open-pack:hover .arrow-icon {
  transform: translateX(4px);
}

@media (max-width: 768px) {
  .pack-selection-container {
    padding: 1rem;
  }

  .page-title {
    font-size: 2rem;
  }

  .packs-grid {
    grid-template-columns: 1fr;
    gap: 1.5rem;
  }

  .pack-card {
    padding: 1.5rem;
  }

  .pack-header {
    flex-direction: column;
  }

  .pack-footer {
    flex-direction: column;
    gap: 1rem;
    align-items: stretch;
  }

  .btn-open-pack {
    width: 100%;
    justify-content: center;
  }

  .tabs {
    margin-bottom: 2rem;
  }

  .tab-button {
    padding: 0.75rem 1.5rem;
    margin: 0 0.5rem;
  }
}

/* Tabs */
.tabs {
  display: flex;
  justify-content: center;
  gap: 1rem;
  margin-bottom: 2rem;
}

.tab-button {
  position: relative;
  padding: 1rem 2rem;
  background: rgba(30, 41, 59, 0.6);
  border: 2px solid #334155;
  border-radius: 12px;
  color: #94a3b8;
  font-size: 1.1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.tab-button:hover {
  border-color: #60a5fa;
  color: white;
  transform: translateY(-2px);
}

.tab-button.active {
  background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
  border-color: #60a5fa;
  color: white;
  box-shadow: 0 8px 16px rgba(59, 130, 246, 0.3);
}

.inventory-badge {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 24px;
  height: 24px;
  padding: 0 8px;
  background: rgba(239, 68, 68, 0.9);
  color: white;
  font-size: 0.85rem;
  font-weight: bold;
  border-radius: 12px;
  margin-left: 0.5rem;
}

/* Inventory Section */
.inventory-section {
  max-width: 1400px;
  margin: 0 auto;
}

.empty-inventory {
  text-align: center;
  padding: 4rem 2rem;
  background: rgba(30, 41, 59, 0.4);
  border: 2px dashed #334155;
  border-radius: 16px;
  margin: 0 auto;
  max-width: 600px;
}

.empty-icon {
  font-size: 5rem;
  margin-bottom: 1rem;
  opacity: 0.5;
}

.empty-inventory h3 {
  font-size: 1.75rem;
  margin-bottom: 0.5rem;
  color: white;
}

.empty-inventory p {
  color: #9ca3af;
  margin-bottom: 2rem;
  font-size: 1.1rem;
}

.btn-goto-store {
  padding: 1rem 2rem;
  background: linear-gradient(135deg, #3b82f6 0%, #8b5cf6 100%);
  border: none;
  border-radius: 12px;
  color: white;
  font-size: 1.1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn-goto-store:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 16px rgba(59, 130, 246, 0.4);
}

/* Quantity Badge */
.quantity-badge {
  position: absolute;
  top: 1rem;
  right: 1rem;
  background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);
  color: white;
  font-weight: bold;
  font-size: 1.1rem;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
  z-index: 10;
}

/* Inventory Pack Styling */
.inventory-pack {
  border-color: #3b82f6;
}

.inventory-pack::before {
  background: linear-gradient(to right, #3b82f6, #8b5cf6);
}

.pack-source {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.source-badge {
  padding: 0.5rem 1rem;
  background: rgba(59, 130, 246, 0.2);
  border: 1px solid rgba(59, 130, 246, 0.5);
  border-radius: 8px;
  color: #60a5fa;
  font-size: 0.9rem;
  font-weight: 600;
}

.btn-open-pack.inventory-open {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  border-color: #10b981;
}

.btn-open-pack.inventory-open:hover {
  background: linear-gradient(135deg, #059669 0%, #047857 100%);
  box-shadow: 0 6px 12px rgba(16, 185, 129, 0.4);
}
</style>
