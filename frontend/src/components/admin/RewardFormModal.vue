<template>
  <div v-if="isOpen" class="modal-backdrop" @click.self="closeModal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>{{ isEditMode ? "Edit Reward" : "Add Reward" }}</h2>
        <button @click="closeModal" class="close-btn">&times;</button>
      </div>

      <form @submit.prevent="handleSubmit" class="modal-form">
        <div class="form-field">
          <label>Required Cards *</label>
          <input
            v-model.number="formData.required_cards"
            type="number"
            min="1"
            required
            placeholder="Number of locked cards needed"
          />
          <span class="field-hint">How many cards must be locked to earn this reward</span>
        </div>

        <div class="form-field">
          <label>Reward Type *</label>
          <select v-model="formData.reward_type" required @change="handleRewardTypeChange">
            <option value="">Select Type</option>
            <option value="stubs">Stubs</option>
            <option value="player_card">Player Card</option>
            <option value="pack">Pack</option>
          </select>
        </div>

        <div v-if="formData.reward_type === 'stubs'" class="form-field">
          <label>Stubs Amount *</label>
          <input
            v-model.number="formData.reward_quantity"
            type="number"
            min="1"
            required
            placeholder="e.g., 500"
          />
        </div>

        <div v-if="formData.reward_type === 'player_card'" class="form-field">
          <label>Select Player *</label>
          <select v-model="formData.player_id" required>
            <option value="">Choose a player</option>
            <option v-for="player in players" :key="player.id" :value="player.id">
              {{ player.name }} ({{ player.overall_rating }} OVR - {{ player.team }})
            </option>
          </select>
        </div>

        <div v-if="formData.reward_type === 'pack'" class="form-field">
          <label>Select Pack *</label>
          <select v-model="formData.pack_id" required>
            <option value="">Choose a pack</option>
            <option v-for="pack in packs" :key="pack.id" :value="pack.id">
              {{ pack.name }} ({{ pack.card_count }} cards)
            </option>
          </select>
          <label style="margin-top: 10px">Pack Quantity</label>
          <input
            v-model.number="formData.reward_quantity"
            type="number"
            min="1"
            :required="formData.reward_type === 'pack'"
            placeholder="Number of packs"
          />
        </div>

        <div class="form-field">
          <label>Description</label>
          <textarea
            v-model="formData.description"
            rows="3"
            placeholder="Optional description of the reward"
          ></textarea>
        </div>

        <div class="form-field">
          <label>Display Order</label>
          <input
            v-model.number="formData.order"
            type="number"
            min="0"
            placeholder="Optional (auto-increments if not set)"
          />
          <span class="field-hint">Lower numbers appear first</span>
        </div>

        <div class="modal-actions">
          <button type="submit" class="btn-submit" :disabled="loading">
            {{ isEditMode ? "Update Reward" : "Create Reward" }}
          </button>
          <button type="button" @click="closeModal" class="btn-cancel">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from "vue";
import { useAdminStore } from "@/stores/admin";

interface Props {
  isOpen: boolean;
  reward?: any;
  collectionId: number;
}

const props = defineProps<Props>();
const emit = defineEmits(["close", "saved"]);

const adminStore = useAdminStore();
const loading = ref(false);
const players = ref<any[]>([]);
const packs = ref<any[]>([]);

const isEditMode = ref(false);
const formData = ref({
  required_cards: 1,
  reward_type: "",
  reward_quantity: 1,
  player_id: null as number | null,
  pack_id: null as number | null,
  description: "",
  order: null as number | null,
});

onMounted(async () => {
  // Load players and packs for selection
  await adminStore.fetchAllPlayers();
  await adminStore.fetchAllPacks();
  players.value = adminStore.allPlayers;
  packs.value = adminStore.allPacks;
});

watch(
  () => props.reward,
  (newReward) => {
    if (newReward) {
      isEditMode.value = true;
      formData.value = {
        required_cards: newReward.required_cards || 1,
        reward_type: newReward.reward_type || "",
        reward_quantity: newReward.reward_quantity || 1,
        player_id: newReward.player_id || null,
        pack_id: newReward.pack_id || null,
        description: newReward.description || "",
        order: newReward.order ?? null,
      };
    } else {
      isEditMode.value = false;
      resetForm();
    }
  },
  { immediate: true }
);

function handleRewardTypeChange() {
  // Reset relevant fields when reward type changes
  formData.value.player_id = null;
  formData.value.pack_id = null;
  formData.value.reward_quantity = 1;
}

function resetForm() {
  formData.value = {
    required_cards: 1,
    reward_type: "",
    reward_quantity: 1,
    player_id: null,
    pack_id: null,
    description: "",
    order: null,
  };
}

async function handleSubmit() {
  loading.value = true;
  try {
    let result;
    const payload = { ...formData.value };

    // Clean up payload based on reward type
    if (payload.reward_type === "stubs") {
      payload.player_id = null;
      payload.pack_id = null;
    } else if (payload.reward_type === "player_card") {
      payload.pack_id = null;
      payload.reward_quantity = 1;
    } else if (payload.reward_type === "pack") {
      payload.player_id = null;
    }

    if (isEditMode.value && props.reward) {
      result = await adminStore.updateCollectionReward(props.reward.id, payload);
    } else {
      result = await adminStore.createCollectionReward(props.collectionId, payload);
    }

    if (result.success) {
      emit("saved");
      closeModal();
    } else {
      alert(`Failed: ${result.message}`);
    }
  } finally {
    loading.value = false;
  }
}

function closeModal() {
  resetForm();
  emit("close");
}
</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 600px;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.modal-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1.5rem;
  border-bottom: 1px solid #e5e7eb;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 600;
  color: #1a1d29;
}

.close-btn {
  background: none;
  border: none;
  font-size: 2rem;
  line-height: 1;
  color: #6b7280;
  cursor: pointer;
  padding: 0;
  width: 32px;
  height: 32px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  transition: background-color 0.2s;
}

.close-btn:hover {
  background-color: #f3f4f6;
}

.modal-form {
  padding: 1.5rem;
}

.form-field {
  margin-bottom: 1.5rem;
}

.form-field label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #374151;
  font-size: 0.875rem;
}

.form-field input[type="text"],
.form-field input[type="number"],
.form-field select,
.form-field textarea {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  font-size: 1rem;
  transition: border-color 0.2s ease;
  box-sizing: border-box;
}

.form-field input:focus,
.form-field select:focus,
.form-field textarea:focus {
  outline: none;
  border-color: #3b82f6;
}

.field-hint {
  display: block;
  margin-top: 0.25rem;
  font-size: 0.75rem;
  color: #6b7280;
  font-style: italic;
}

.modal-actions {
  display: flex;
  gap: 1rem;
  padding-top: 1rem;
  border-top: 1px solid #e5e7eb;
}

.btn-submit {
  flex: 1;
  padding: 0.75rem 1.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);
}

.btn-submit:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.btn-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.btn-cancel {
  padding: 0.75rem 1.5rem;
  background: white;
  color: #374151;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-cancel:hover {
  background: #f9fafb;
  border-color: #3b82f6;
}
</style>
