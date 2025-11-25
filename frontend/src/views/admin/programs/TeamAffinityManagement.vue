<template>
  <div class="team-affinity-management p-6">
    <div class="header mb-6">
      <div>
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Team Affinity Management</h1>
        <p class="text-gray-600">Manage all 30 Team Affinity programs in bulk</p>
      </div>
      <div class="flex gap-3 mt-4">
        <button @click="showCreateDialog = true" class="btn-primary">
          Create All Team Affinity Programs
        </button>
        <router-link to="/admin/programs" class="btn-secondary"> ← Back to Programs </router-link>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="text-center py-12">
      <p class="text-gray-600">Loading Team Affinity programs...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="text-center py-12">
      <p class="text-red-600">{{ error }}</p>
      <button @click="loadTeamAffinity" class="btn-primary mt-4">Retry</button>
    </div>

    <!-- Programs Table -->
    <div v-else class="bg-white rounded-lg shadow overflow-hidden">
      <div class="p-4 bg-gray-50 border-b border-gray-200">
        <div class="flex gap-3">
          <button @click="showBulkRewardDialog = true" class="btn-secondary">
            + Add Reward to Selected Teams
          </button>
          <button @click="showBulkChallengeDialog = true" class="btn-secondary">
            + Add Challenge to Selected Teams
          </button>
          <button
            v-if="selectedTeams.length > 0"
            @click="clearSelection"
            class="btn-secondary ml-auto"
          >
            Clear Selection ({{ selectedTeams.length }})
          </button>
        </div>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full">
          <thead class="bg-gray-100">
            <tr>
              <th class="px-4 py-3 text-left">
                <input
                  type="checkbox"
                  @change="toggleAllTeams"
                  :checked="allSelected"
                  class="cursor-pointer"
                />
              </th>
              <th class="px-4 py-3 text-left font-semibold text-gray-700">Team</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-700">Stars Required</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-700">Rewards</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-700">Challenges</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-700">Status</th>
              <th class="px-4 py-3 text-left font-semibold text-gray-700">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="program in teamAffinityPrograms"
              :key="program.id"
              class="border-b border-gray-200 hover:bg-gray-50"
            >
              <td class="px-4 py-3">
                <input
                  type="checkbox"
                  v-model="selectedTeams"
                  :value="program.id"
                  class="cursor-pointer"
                />
              </td>
              <td class="px-4 py-3 font-medium text-gray-800">{{ program.team }}</td>
              <td class="px-4 py-3 text-gray-600">{{ program.stars_required }}</td>
              <td class="px-4 py-3">
                <span
                  class="bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-sm font-semibold"
                >
                  {{ program.rewards_count }}
                </span>
              </td>
              <td class="px-4 py-3">
                <span
                  class="bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-semibold"
                >
                  {{ program.challenges_count }}
                </span>
              </td>
              <td class="px-4 py-3">
                <span
                  :class="
                    program.active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                  "
                  class="px-3 py-1 rounded-full text-sm font-semibold"
                >
                  {{ program.active ? "Active" : "Inactive" }}
                </span>
              </td>
              <td class="px-4 py-3">
                <router-link
                  :to="`/admin/programs/${program.id}/edit`"
                  class="text-blue-600 hover:text-blue-800 font-semibold"
                >
                  Edit
                </router-link>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="teamAffinityPrograms.length === 0" class="text-center py-12 text-gray-400">
          No Team Affinity programs found. Click "Create All Team Affinity Programs" to get started.
        </div>
      </div>
    </div>

    <!-- Create All Dialog -->
    <div v-if="showCreateDialog" class="modal-overlay" @click.self="showCreateDialog = false">
      <div class="modal-content">
        <h2 class="text-2xl font-bold mb-4">Create All Team Affinity Programs</h2>
        <p class="text-gray-600 mb-4">
          This will create Team Affinity programs for all 30 NBA teams with default rewards and
          challenges.
        </p>
        <div class="mb-4">
          <label class="flex items-center gap-2">
            <input type="checkbox" v-model="createFresh" />
            <span>Delete existing Team Affinity programs first</span>
          </label>
        </div>
        <div class="flex gap-3 justify-end">
          <button @click="showCreateDialog = false" class="btn-secondary">Cancel</button>
          <button @click="createAllPrograms" class="btn-primary">Create Programs</button>
        </div>
      </div>
    </div>

    <!-- Bulk Add Reward Dialog -->
    <div
      v-if="showBulkRewardDialog"
      class="modal-overlay"
      @click.self="showBulkRewardDialog = false"
    >
      <div class="modal-content max-w-2xl">
        <h2 class="text-2xl font-bold mb-4">Add Reward to Selected Teams</h2>
        <div class="space-y-4">
          <div>
            <label class="block font-semibold mb-1">Stars Threshold</label>
            <input
              v-model.number="bulkReward.stars_threshold"
              type="number"
              class="input-field"
              required
            />
          </div>
          <div>
            <label class="block font-semibold mb-1">Reward Type</label>
            <select v-model="bulkReward.reward_type" class="input-field" required>
              <option value="">Select Type</option>
              <option value="player">Player Card</option>
              <option value="pack">Pack</option>
              <option value="stubs">Stubs</option>
              <option value="xp">XP</option>
            </select>
          </div>
          <div v-if="bulkReward.reward_type === 'stubs' || bulkReward.reward_type === 'xp'">
            <label class="block font-semibold mb-1">Amount</label>
            <input v-model.number="bulkReward.reward_amount" type="number" class="input-field" />
          </div>
          <div>
            <label class="block font-semibold mb-1">Description</label>
            <input v-model="bulkReward.description" type="text" class="input-field" required />
          </div>
        </div>
        <div class="flex gap-3 justify-end mt-6">
          <button @click="showBulkRewardDialog = false" class="btn-secondary">Cancel</button>
          <button @click="addBulkRewards" class="btn-primary">
            Add to {{ selectedTeams.length }} Teams
          </button>
        </div>
      </div>
    </div>

    <!-- Bulk Add Challenge Dialog -->
    <div
      v-if="showBulkChallengeDialog"
      class="modal-overlay"
      @click.self="showBulkChallengeDialog = false"
    >
      <div class="modal-content max-w-2xl">
        <h2 class="text-2xl font-bold mb-4">Add Challenge to Selected Teams</h2>
        <div class="space-y-4">
          <div>
            <label class="block font-semibold mb-1">Challenge Type</label>
            <select v-model="bulkChallenge.type" class="input-field" required>
              <option value="">Select Type</option>
              <option value="stat">Stat</option>
              <option value="game_count">Game Count</option>
              <option value="pxp">PXP</option>
            </select>
          </div>
          <div v-if="bulkChallenge.type === 'stat'">
            <label class="block font-semibold mb-1">Target Stat</label>
            <input
              v-model="bulkChallenge.target_stat"
              type="text"
              class="input-field"
              placeholder="e.g., points"
            />
          </div>
          <div>
            <label class="block font-semibold mb-1">Target Value</label>
            <input
              v-model.number="bulkChallenge.target_value"
              type="number"
              class="input-field"
              required
            />
          </div>
          <div>
            <label class="block font-semibold mb-1">Constraint Type</label>
            <select v-model="bulkChallenge.constraint_type" class="input-field">
              <option value="">No Constraint</option>
              <option value="position">Position</option>
              <option value="team">Team (Will use program's team)</option>
              <option value="tier">Tier</option>
              <option value="player">Specific Player</option>
            </select>
          </div>
          <div v-if="bulkChallenge.constraint_type && bulkChallenge.constraint_type !== 'team'">
            <label class="block font-semibold mb-1">Constraint Value</label>
            <input v-model="bulkChallenge.constraint_value" type="text" class="input-field" />
          </div>
          <div>
            <label class="block font-semibold mb-1">Stars Reward</label>
            <input
              v-model.number="bulkChallenge.stars_reward"
              type="number"
              class="input-field"
              required
            />
          </div>
          <div>
            <label class="block font-semibold mb-1">Description</label>
            <input
              v-model="bulkChallenge.description"
              type="text"
              class="input-field"
              required
              placeholder="Use {TEAM} for team name"
            />
          </div>
          <div>
            <label class="flex items-center gap-2">
              <input type="checkbox" v-model="bulkChallenge.use_team_name" />
              <span>Replace {TEAM} with actual team name</span>
            </label>
          </div>
        </div>
        <div class="flex gap-3 justify-end mt-6">
          <button @click="showBulkChallengeDialog = false" class="btn-secondary">Cancel</button>
          <button @click="addBulkChallenges" class="btn-primary">
            Add to {{ selectedTeams.length }} Teams
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import api from "@/services/api";

const loading = ref(false);
const error = ref(null);
const teamAffinityPrograms = ref([]);
const selectedTeams = ref([]);
const showCreateDialog = ref(false);
const showBulkRewardDialog = ref(false);
const showBulkChallengeDialog = ref(false);
const createFresh = ref(false);

const bulkReward = ref({
  stars_threshold: null,
  reward_type: "",
  reward_id: null,
  reward_amount: null,
  description: "",
});

const bulkChallenge = ref({
  type: "",
  target_stat: "",
  target_value: 0,
  constraint_type: "",
  constraint_value: "",
  stars_reward: 0,
  xp_reward: 0,
  description: "",
  use_team_name: true,
});

const allSelected = computed(() => {
  return (
    teamAffinityPrograms.value.length > 0 &&
    selectedTeams.value.length === teamAffinityPrograms.value.length
  );
});

const loadTeamAffinity = async () => {
  loading.value = true;
  error.value = null;
  try {
    const response = await api.getTeamAffinityPrograms();
    if (response.data.success) {
      teamAffinityPrograms.value = response.data.data;
    }
  } catch (err) {
    error.value = err.message || "Failed to load Team Affinity programs";
    console.error("Error loading Team Affinity:", err);
  } finally {
    loading.value = false;
  }
};

const createAllPrograms = async () => {
  loading.value = true;
  try {
    const response = await api.createAllTeamAffinity(createFresh.value);
    if (response.data.success) {
      alert("Team Affinity programs created successfully!");
      showCreateDialog.value = false;
      await loadTeamAffinity();
    }
  } catch (err) {
    alert("Failed to create programs: " + (err.response?.data?.message || err.message));
  } finally {
    loading.value = false;
  }
};

const addBulkRewards = async () => {
  if (selectedTeams.value.length === 0) {
    alert("Please select at least one team");
    return;
  }

  loading.value = true;
  try {
    const response = await api.bulkAddRewards(selectedTeams.value, [bulkReward.value]);
    if (response.data.success) {
      alert("Rewards added successfully!");
      showBulkRewardDialog.value = false;
      await loadTeamAffinity();
      // Reset form
      bulkReward.value = {
        stars_threshold: null,
        reward_type: "",
        reward_id: null,
        reward_amount: null,
        description: "",
      };
    }
  } catch (err) {
    alert("Failed to add rewards: " + (err.response?.data?.message || err.message));
  } finally {
    loading.value = false;
  }
};

const addBulkChallenges = async () => {
  if (selectedTeams.value.length === 0) {
    alert("Please select at least one team");
    return;
  }

  loading.value = true;
  try {
    const response = await api.bulkAddChallenges(selectedTeams.value, [bulkChallenge.value]);
    if (response.data.success) {
      alert("Challenges added successfully!");
      showBulkChallengeDialog.value = false;
      await loadTeamAffinity();
      // Reset form
      bulkChallenge.value = {
        type: "",
        target_stat: "",
        target_value: 0,
        constraint_type: "",
        constraint_value: "",
        stars_reward: 0,
        xp_reward: 0,
        description: "",
        use_team_name: true,
      };
    }
  } catch (err) {
    alert("Failed to add challenges: " + (err.response?.data?.message || err.message));
  } finally {
    loading.value = false;
  }
};

const toggleAllTeams = () => {
  if (allSelected.value) {
    selectedTeams.value = [];
  } else {
    selectedTeams.value = teamAffinityPrograms.value.map((p) => p.id);
  }
};

const clearSelection = () => {
  selectedTeams.value = [];
};

onMounted(() => {
  loadTeamAffinity();
});
</script>

<style scoped>
.btn-primary {
  @apply px-4 py-2 bg-blue-600 text-white rounded-lg font-semibold hover:bg-blue-700 transition-colors;
}

.btn-secondary {
  @apply px-4 py-2 bg-gray-200 text-gray-800 rounded-lg font-semibold hover:bg-gray-300 transition-colors;
}

.input-field {
  @apply w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500;
}

.modal-overlay {
  @apply fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4;
}

.modal-content {
  @apply bg-white rounded-xl p-6 max-w-lg w-full max-h-[90vh] overflow-y-auto;
}
</style>
