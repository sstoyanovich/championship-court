<template>
  <div class="reward-manager">
    <div class="section-header">
      <h3>Collection Rewards</h3>
      <button @click="openAddModal" class="btn-add">+ Add Reward</button>
    </div>

    <div v-if="loading" class="loading">Loading rewards...</div>

    <div v-else-if="rewards.length === 0" class="empty-state">
      No rewards configured yet. Add your first reward to get started.
    </div>

    <div v-else class="rewards-list">
      <div v-for="reward in sortedRewards" :key="reward.id" class="reward-item">
        <div class="reward-info">
          <div class="reward-header">
            <span class="reward-type-badge" :class="`type-${reward.reward_type}`">
              {{ formatRewardType(reward.reward_type) }}
            </span>
            <span class="reward-requirement">{{ reward.required_cards }} cards</span>
          </div>
          <div class="reward-details">
            <strong>{{ getRewardDisplayText(reward) }}</strong>
            <p v-if="reward.description" class="reward-description">{{ reward.description }}</p>
          </div>
        </div>
        <div class="reward-actions">
          <button @click="editReward(reward)" class="btn-edit">Edit</button>
          <button @click="deleteReward(reward)" class="btn-delete">Delete</button>
        </div>
      </div>
    </div>

    <RewardFormModal
      :is-open="isModalOpen"
      :reward="selectedReward"
      :collection-id="collectionId"
      @close="closeModal"
      @saved="handleSaved"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { useAdminStore } from "@/stores/admin";
import RewardFormModal from "./RewardFormModal.vue";

interface Props {
  collectionId: number;
}

const props = defineProps<Props>();

const adminStore = useAdminStore();
const loading = ref(false);
const rewards = ref<any[]>([]);
const isModalOpen = ref(false);
const selectedReward = ref<any>(null);

const sortedRewards = computed(() => {
  return [...rewards.value].sort((a, b) => {
    // Sort by order first, then by required_cards
    if (a.order !== b.order) {
      return (a.order || 0) - (b.order || 0);
    }
    return a.required_cards - b.required_cards;
  });
});

onMounted(() => {
  loadRewards();
});

async function loadRewards() {
  loading.value = true;
  try {
    const result = await adminStore.fetchCollectionRewards(props.collectionId);
    if (result.success) {
      rewards.value = result.data;
    }
  } finally {
    loading.value = false;
  }
}

function openAddModal() {
  selectedReward.value = null;
  isModalOpen.value = true;
}

function editReward(reward: any) {
  selectedReward.value = reward;
  isModalOpen.value = true;
}

async function deleteReward(reward: any) {
  if (!confirm(`Are you sure you want to delete this reward?`)) {
    return;
  }

  const result = await adminStore.deleteCollectionReward(reward.id);
  if (result.success) {
    await loadRewards();
  } else {
    alert(`Failed to delete reward: ${result.message}`);
  }
}

function closeModal() {
  isModalOpen.value = false;
  selectedReward.value = null;
}

async function handleSaved() {
  await loadRewards();
}

function formatRewardType(type: string): string {
  const types: Record<string, string> = {
    stubs: "Stubs",
    player_card: "Player Card",
    pack: "Pack",
  };
  return types[type] || type;
}

function getRewardDisplayText(reward: any): string {
  switch (reward.reward_type) {
    case "stubs":
      return `${reward.reward_quantity.toLocaleString()} Stubs`;
    case "player_card":
      return reward.player
        ? `${reward.player.name} (${reward.player.overall_rating} OVR)`
        : "Player Card";
    case "pack":
      return reward.pack
        ? `${reward.reward_quantity}x ${reward.pack.name}`
        : `${reward.reward_quantity}x Pack`;
    default:
      return "Unknown Reward";
  }
}
</script>

<style scoped>
.reward-manager {
  background: white;
  border-radius: 8px;
  padding: 1.5rem;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  margin-top: 2rem;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.section-header h3 {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: #1a1d29;
}

.btn-add {
  padding: 0.5rem 1rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);
}

.btn-add:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.loading,
.empty-state {
  padding: 2rem;
  text-align: center;
  color: #6b7280;
  background: #f9fafb;
  border-radius: 8px;
}

.rewards-list {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.reward-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem;
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  transition: all 0.2s ease;
}

.reward-item:hover {
  background: #f3f4f6;
  border-color: #d1d5db;
}

.reward-info {
  flex: 1;
}

.reward-header {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  margin-bottom: 0.5rem;
}

.reward-type-badge {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 999px;
  font-weight: 600;
  font-size: 0.75rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.type-stubs {
  background: #fef3c7;
  color: #92400e;
}

.type-player_card {
  background: #dbeafe;
  color: #1e40af;
}

.type-pack {
  background: #e9d5ff;
  color: #6b21a8;
}

.reward-requirement {
  font-size: 0.875rem;
  color: #6b7280;
  font-weight: 500;
}

.reward-details strong {
  color: #1a1d29;
  font-size: 1rem;
}

.reward-description {
  margin: 0.25rem 0 0 0;
  font-size: 0.875rem;
  color: #6b7280;
}

.reward-actions {
  display: flex;
  gap: 0.5rem;
}

.btn-edit,
.btn-delete {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-edit {
  background: #dbeafe;
  color: #1e40af;
}

.btn-edit:hover {
  background: #bfdbfe;
}

.btn-delete {
  background: #fee2e2;
  color: #991b1b;
}

.btn-delete:hover {
  background: #fecaca;
}
</style>
