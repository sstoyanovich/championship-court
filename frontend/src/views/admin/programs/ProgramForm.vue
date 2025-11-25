<template>
  <div class="program-form">
    <div class="header">
      <h1>{{ isEditMode ? "Edit Program" : "Create Program" }}</h1>
      <router-link to="/admin/programs" class="btn-back">← Back to Programs</router-link>
    </div>

    <div v-if="adminStore.loading" class="loading">Loading...</div>

    <form v-else @submit.prevent="handleSubmit" class="form">
      <!-- Program Information -->
      <div class="form-section">
        <h2>Program Information</h2>
        <div class="form-grid">
          <div class="form-field full-width">
            <label>Name *</label>
            <input v-model="formData.name" type="text" required />
          </div>
          <div class="form-field full-width">
            <label>Description</label>
            <textarea v-model="formData.description" rows="4"></textarea>
          </div>
          <div class="form-field">
            <label>Type *</label>
            <select v-model="formData.type" required>
              <option value="">Select Type</option>
              <option value="xp">XP</option>
              <option value="star">Star</option>
            </select>
          </div>
          <div class="form-field">
            <label>Category *</label>
            <select v-model="formData.category" required>
              <option value="">Select Category</option>
              <option value="general">General</option>
              <option value="team_affinity">Team Affinity</option>
            </select>
          </div>
          <div class="form-field" v-if="formData.category === 'team_affinity'">
            <label>Team *</label>
            <select v-model="formData.team">
              <option value="">Select Team</option>
              <option v-for="team in NBA_TEAMS" :key="team" :value="team">
                {{ team }}
              </option>
            </select>
          </div>
          <div class="form-field">
            <label>Total XP Required</label>
            <input v-model.number="formData.total_xp_required" type="number" min="0" />
          </div>
          <div class="form-field">
            <label>Stars Required</label>
            <input v-model.number="formData.stars_required" type="number" min="0" />
          </div>
          <div class="form-field">
            <label>Active</label>
            <div class="checkbox-field">
              <input v-model="formData.active" type="checkbox" id="active" />
              <label for="active">Program is active</label>
            </div>
          </div>
          <div class="form-field full-width">
            <label>Image URL</label>
            <input v-model="formData.image_url" type="text" placeholder="https://..." />
          </div>
        </div>
      </div>

      <!-- Rewards Section -->
      <div class="form-section">
        <div class="section-header">
          <h2>Rewards</h2>
          <button type="button" @click="addReward" class="btn-add">+ Add Reward</button>
        </div>

        <div v-if="formData.rewards.length === 0" class="empty-message">
          No rewards added yet. Click "Add Reward" to create one.
        </div>

        <div v-for="(reward, index) in formData.rewards" :key="index" class="item-card">
          <div class="item-header">
            <h3>Reward #{{ index + 1 }}</h3>
            <button type="button" @click="removeReward(index)" class="btn-remove">Remove</button>
          </div>
          <div class="form-grid">
            <div class="form-field">
              <label>XP Threshold</label>
              <input v-model.number="reward.xp_threshold" type="number" min="0" />
              <span class="field-hint">For XP-based programs</span>
            </div>
            <div class="form-field">
              <label>Stars Threshold</label>
              <input v-model.number="reward.stars_threshold" type="number" min="0" />
              <span class="field-hint">For star-based programs</span>
            </div>
            <div class="form-field">
              <label>Reward Type *</label>
              <select v-model="reward.reward_type" required>
                <option value="">Select Type</option>
                <option value="stubs">Stubs</option>
                <option value="player">Player</option>
                <option value="pack">Pack</option>
                <option value="program_xp">Program XP</option>
              </select>
            </div>
            <div class="form-field" v-if="reward.reward_type === 'stubs'">
              <label>Stubs Amount *</label>
              <input v-model.number="reward.reward_amount" type="number" min="0" required />
              <span class="field-hint">How many stubs to award</span>
            </div>
            <div class="form-field" v-if="reward.reward_type === 'player'">
              <label>Player *</label>
              <select v-model.number="reward.reward_id" required>
                <option value="">Select Player</option>
                <option v-for="player in adminStore.allPlayers" :key="player.id" :value="player.id">
                  {{ player.name }} - {{ player.team }} ({{ player.card_tier }})
                </option>
              </select>
            </div>
            <div class="form-field" v-if="reward.reward_type === 'pack'">
              <label>Pack *</label>
              <select v-model.number="reward.reward_id" required>
                <option value="">Select Pack</option>
                <option v-for="pack in adminStore.allPacks" :key="pack.id" :value="pack.id">
                  {{ pack.name }} - {{ pack.cost }} Stubs
                </option>
              </select>
            </div>
            <div class="form-field" v-if="reward.reward_type === 'pack'">
              <label>Pack Quantity *</label>
              <input v-model.number="reward.reward_amount" type="number" min="1" required />
              <span class="field-hint">How many packs to award</span>
            </div>
            <div class="form-field" v-if="reward.reward_type === 'program_xp'">
              <label>Target Program *</label>
              <select v-model.number="reward.reward_id" required>
                <option value="">Select Program</option>
                <option v-for="program in xpPrograms" :key="program.id" :value="program.id">
                  {{ program.name }} ({{ program.category }})
                </option>
              </select>
              <span class="field-hint">XP will be added to this program</span>
            </div>
            <div class="form-field" v-if="reward.reward_type === 'program_xp'">
              <label>XP Amount *</label>
              <input v-model.number="reward.reward_amount" type="number" min="1" required />
              <span class="field-hint">How much XP to award</span>
            </div>
            <div class="form-field full-width">
              <label>Description</label>
              <input v-model="reward.description" type="text" />
            </div>
            <div class="form-field">
              <label>Availability</label>
              <div class="checkbox-field">
                <input v-model="reward.available" type="checkbox" :id="'available-' + index" />
                <label :for="'available-' + index">Reward is available to claim</label>
              </div>
              <span class="field-hint"
                >Uncheck if reward should be pending (e.g. All-Star cards)</span
              >
            </div>
            <div class="form-field" v-if="!reward.available">
              <label>Coming Soon Label</label>
              <input
                v-model="reward.coming_soon_label"
                type="text"
                placeholder="e.g., Available after All-Star Game"
              />
              <span class="field-hint">Message shown to users when reward is pending</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Challenges Section -->
      <div class="form-section">
        <div class="section-header">
          <h2>Challenges</h2>
          <button type="button" @click="addChallenge" class="btn-add">+ Add Challenge</button>
        </div>

        <div v-if="formData.challenges.length === 0" class="empty-message">
          No challenges added yet. Click "Add Challenge" to create one.
        </div>

        <div v-for="(challenge, index) in formData.challenges" :key="index" class="item-card">
          <div class="item-header">
            <h3>Challenge #{{ index + 1 }}</h3>
            <button type="button" @click="removeChallenge(index)" class="btn-remove">Remove</button>
          </div>
          <div class="form-grid">
            <div class="form-field">
              <label>Type *</label>
              <select v-model="challenge.type" required>
                <option value="">Select Type</option>
                <option value="stat">Stat</option>
                <option value="game_count">Game Count</option>
                <option value="pxp">PXP</option>
              </select>
            </div>
            <div class="form-field" v-if="challenge.type === 'stat'">
              <label>Target Stat *</label>
              <select v-model="challenge.target_stat" required>
                <option value="">Select Stat</option>
                <option value="points">Points</option>
                <option value="rebounds">Rebounds</option>
                <option value="assists">Assists</option>
                <option value="steals">Steals</option>
                <option value="blocks">Blocks</option>
                <option value="threes">Three Pointers</option>
              </select>
            </div>
            <div class="form-field">
              <label>Target Value *</label>
              <input v-model.number="challenge.target_value" type="number" min="0" required />
            </div>
            <div class="form-field">
              <label>Constraint Type</label>
              <select v-model="challenge.constraint_type">
                <option value="">No Constraint</option>
                <option value="position">Position</option>
                <option value="team">Team</option>
                <option value="tier">Tier</option>
                <option value="player">Specific Player</option>
                <option value="collection">Collection</option>
              </select>
            </div>
            <div class="form-field" v-if="challenge.constraint_type === 'player'">
              <label>Select Player *</label>
              <select v-model="challenge.constraint_value">
                <option value="">Select Player</option>
                <option v-for="player in adminStore.allPlayers" :key="player.id" :value="player.id">
                  {{ player.name }} ({{ player.team }}) - {{ player.card_tier }}
                </option>
              </select>
            </div>
            <div class="form-field" v-else-if="challenge.constraint_type === 'team'">
              <label>Select Team *</label>
              <select v-model="challenge.constraint_value">
                <option value="">Select Team</option>
                <option v-for="team in NBA_TEAMS" :key="team" :value="team">
                  {{ team }}
                </option>
              </select>
            </div>
            <div class="form-field" v-else-if="challenge.constraint_type === 'collection'">
              <label>Select Collection *</label>
              <select v-model="challenge.constraint_value">
                <option value="">Select Collection</option>
                <option v-for="collection in adminStore.allCollections" :key="collection.id" :value="collection.id">
                  {{ collection.name }}{{ collection.sub_collection ? ` - ${collection.sub_collection}` : '' }}
                </option>
              </select>
            </div>
            <div
              class="form-field"
              v-else-if="
                challenge.constraint_type &&
                challenge.constraint_type !== 'player' &&
                challenge.constraint_type !== 'team' &&
                challenge.constraint_type !== 'collection'
              "
            >
              <label>Constraint Value</label>
              <input v-model="challenge.constraint_value" type="text" />
            </div>
            <div class="form-field">
              <label>Stars Reward</label>
              <input v-model.number="challenge.stars_reward" type="number" min="0" />
            </div>
            <div class="form-field">
              <label>XP Reward</label>
              <input v-model.number="challenge.xp_reward" type="number" min="0" />
            </div>
            <div class="form-field full-width">
              <label>Description *</label>
              <input v-model="challenge.description" type="text" required />
            </div>
          </div>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn-submit" :disabled="adminStore.loading">
          {{ isEditMode ? "Update Program" : "Create Program" }}
        </button>
        <router-link to="/admin/programs" class="btn-cancel">Cancel</router-link>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAdminStore } from "@/stores/admin";
import { NBA_TEAMS } from "@/constants/nbaTeams";

const route = useRoute();
const router = useRouter();
const adminStore = useAdminStore();

// Filter to show only XP-type programs for program_xp rewards
const xpPrograms = computed(() => {
  return adminStore.allPrograms.filter((program) => program.type === "xp");
});

const isEditMode = ref(false);
const formData = ref({
  name: "",
  description: "",
  type: "",
  category: "general",
  team: "",
  total_xp_required: undefined,
  stars_required: undefined,
  active: true,
  image_url: "",
  rewards: [] as any[],
  challenges: [] as any[],
});

onMounted(async () => {
  // Load all players, packs, programs, and collections for dropdowns
  await adminStore.fetchAllPlayers();
  await adminStore.fetchAllPacks();
  await adminStore.fetchAllPrograms();
  await adminStore.fetchAllCollections();

  const programId = route.params.id;
  if (programId) {
    isEditMode.value = true;
    const result = await adminStore.getProgram(Number(programId));
    if (result.success) {
      formData.value = {
        ...result.data,
        rewards: result.data.rewards || [],
        challenges: result.data.challenges || [],
      };
    }
  }
});

function addReward() {
  formData.value.rewards.push({
    xp_threshold: undefined,
    stars_threshold: undefined,
    reward_type: "",
    reward_id: undefined,
    reward_amount: undefined,
    description: "",
    available: true,
    coming_soon_label: "",
  });
}

function removeReward(index: number) {
  formData.value.rewards.splice(index, 1);
}

function addChallenge() {
  formData.value.challenges.push({
    type: "",
    target_stat: "",
    target_value: 0,
    constraint_type: "",
    constraint_value: "",
    stars_reward: 0,
    xp_reward: 0,
    description: "",
  });
}

function removeChallenge(index: number) {
  formData.value.challenges.splice(index, 1);
}

async function handleSubmit() {
  let result;
  if (isEditMode.value) {
    result = await adminStore.updateProgram((formData.value as any).id, formData.value);
  } else {
    result = await adminStore.createProgram(formData.value);
  }

  if (result.success) {
    router.push("/admin/programs");
  } else {
    alert(`Failed: ${result.message}`);
  }
}
</script>

<style scoped>
.program-form {
  padding: 1rem;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 2rem;
}

.header h1 {
  font-size: 2rem;
  font-weight: 700;
  color: #1a1d29;
  margin: 0;
}

.btn-back {
  padding: 0.5rem 1rem;
  background: white;
  color: #374151;
  text-decoration: none;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  font-weight: 500;
  transition: all 0.2s ease;
}

.btn-back:hover {
  background: #f9fafb;
  border-color: #3b82f6;
}

.loading {
  padding: 2rem;
  text-align: center;
  background: white;
  border-radius: 8px;
}

.form {
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.form-section {
  padding: 2rem;
  border-bottom: 1px solid #e5e7eb;
}

.form-section:last-of-type {
  border-bottom: none;
}

.form-section h2 {
  margin: 0 0 1.5rem 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: #1a1d29;
}

.section-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1.5rem;
}

.section-header h2 {
  margin: 0;
}

.btn-add {
  padding: 0.5rem 1rem;
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
  border: none;
  border-radius: 6px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 0.875rem;
}

.btn-add:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
}

.empty-message {
  padding: 2rem;
  text-align: center;
  color: #6b7280;
  background: #f9fafb;
  border-radius: 8px;
  font-style: italic;
}

.item-card {
  background: #f9fafb;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 1.5rem;
  margin-bottom: 1rem;
}

.item-card:last-child {
  margin-bottom: 0;
}

.item-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  padding-bottom: 0.75rem;
  border-bottom: 1px solid #e5e7eb;
}

.item-header h3 {
  margin: 0;
  font-size: 1rem;
  font-weight: 600;
  color: #374151;
}

.btn-remove {
  padding: 0.375rem 0.75rem;
  background: #fee2e2;
  color: #991b1b;
  border: none;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 0.875rem;
}

.btn-remove:hover {
  background: #fecaca;
}

.form-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
}

.form-field {
  display: flex;
  flex-direction: column;
}

.form-field.full-width {
  grid-column: 1 / -1;
}

.form-field label {
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #374151;
  font-size: 0.875rem;
}

.form-field input[type="text"],
.form-field input[type="number"],
.form-field select,
.form-field textarea {
  padding: 0.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  font-size: 1rem;
  transition: border-color 0.2s ease;
}

.form-field input:focus,
.form-field select:focus,
.form-field textarea:focus {
  outline: none;
  border-color: #3b82f6;
}

.field-hint {
  margin-top: 0.25rem;
  font-size: 0.75rem;
  color: #6b7280;
  font-style: italic;
}

.checkbox-field {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 0;
}

.checkbox-field input[type="checkbox"] {
  width: 1.25rem;
  height: 1.25rem;
  cursor: pointer;
}

.checkbox-field label {
  margin: 0;
  cursor: pointer;
}

.form-actions {
  padding: 2rem;
  display: flex;
  gap: 1rem;
}

.btn-submit {
  padding: 0.75rem 2rem;
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
}

.btn-cancel {
  padding: 0.75rem 2rem;
  background: white;
  color: #374151;
  text-decoration: none;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-weight: 600;
  transition: all 0.2s ease;
  display: inline-block;
}

.btn-cancel:hover {
  background: #f9fafb;
  border-color: #3b82f6;
}
</style>
