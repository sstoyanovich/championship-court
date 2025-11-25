<template>
  <div class="stats-upload-container">
    <div class="stats-upload-card">
      <h1 class="title">Upload Game Stats</h1>
      <p class="subtitle">Upload a screenshot from NBA 2K to track your player stats</p>

      <!-- Step 1: Upload -->
      <div v-if="step === 'upload'">
        <!-- Lineup Selector -->
        <div class="form-group">
          <label for="lineup-select">Select Lineup (Optional)</label>
          <select
            id="lineup-select"
            v-model="selectedLineupId"
            class="lineup-select"
            :disabled="isUploading"
          >
            <option :value="null">No lineup (match by name)</option>
            <option v-for="lineup in lineups" :key="lineup.id" :value="lineup.id">
              {{ lineup.name }}
            </option>
          </select>
        </div>

        <!-- File Upload -->
        <div class="upload-area">
          <div
            class="dropzone"
            :class="{ 'drag-over': isDragging, 'has-file': selectedFile }"
            @drop.prevent="handleDrop"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
          >
            <input
              type="file"
              ref="fileInput"
              accept="image/png, image/jpeg, image/jpg, image/gif"
              @change="handleFileSelect"
              class="file-input"
              :disabled="isUploading"
            />

            <div v-if="!selectedFile" class="upload-prompt">
              <svg class="upload-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"
                />
              </svg>
              <p>
                Drag and drop or
                <span class="browse-link" @click="$refs.fileInput.click()">browse</span>
              </p>
              <p class="file-hint">PNG, JPG, GIF up to 10MB</p>
            </div>

            <div v-else class="file-preview">
              <img :src="previewUrl" alt="Preview" class="preview-image" />
              <div class="file-info">
                <p class="file-name">{{ selectedFile.name }}</p>
                <p class="file-size">{{ formatFileSize(selectedFile.size) }}</p>
                <button @click="clearFile" class="remove-btn" :disabled="isUploading">
                  Remove
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Submit Button -->
        <button
          @click="uploadScreenshot"
          class="submit-btn"
          :disabled="!selectedFile || isUploading"
        >
          <span v-if="!isUploading">Extract Stats</span>
          <span v-else>
            <svg class="spinner" viewBox="0 0 24 24">
              <circle
                class="spinner-circle"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
                fill="none"
              />
            </svg>
            Processing...
          </span>
        </button>

        <!-- Error Message -->
        <div v-if="errorMessage" class="error-message">
          {{ errorMessage }}
        </div>
      </div>

      <!-- Step 2: Review & Edit -->
      <div v-else-if="step === 'review'" class="review-section">
        <div class="review-header">
          <h2 class="review-title">📝 Review Extracted Stats</h2>
          <p class="review-subtitle">
            Please verify the stats below and make any corrections before saving
          </p>
        </div>

        <!-- Screenshot Preview -->
        <div v-if="extractedData.screenshot_url" class="screenshot-preview">
          <img
            :src="getFullImageUrl(extractedData.screenshot_url)"
            alt="Game Screenshot"
            class="screenshot-img"
          />
        </div>

        <!-- Validation Warnings -->
        <div
          v-if="extractedData.validation_errors && extractedData.validation_errors.length > 0"
          class="validation-warning"
        >
          <h4>⚠️ Please Check:</h4>
          <ul>
            <li v-for="(error, index) in extractedData.validation_errors" :key="index">
              {{ error }}
            </li>
          </ul>
        </div>

        <!-- Editable Stats Tables -->
        <div class="stats-tables">
          <!-- Matched Players -->
          <div
            v-if="editableStats.matched && editableStats.matched.length > 0"
            class="matched-players-section"
          >
            <h3 class="table-title">✓ Matched Players ({{ editableStats.matched.length }})</h3>
            <div class="stats-table-container">
              <table class="stats-table">
                <thead>
                  <tr>
                    <th>Player</th>
                    <th>MIN</th>
                    <th>PTS</th>
                    <th>REB</th>
                    <th>AST</th>
                    <th>STL</th>
                    <th>BLK</th>
                    <th>TO</th>
                    <th>FGM</th>
                    <th>FGA</th>
                    <th>3PM</th>
                    <th>3PA</th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="(player, index) in editableStats.matched"
                    :key="index"
                    :class="{ 'manual-entry-row': player.extracted_name === 'Manual Entry' }"
                  >
                    <td class="player-name-cell">
                      <div class="player-name-info">
                        <span class="actual-name">
                          {{ player.player_name }}
                          <span
                            v-if="player.extracted_name === 'Manual Entry'"
                            class="manual-badge"
                          >
                            ✏️ Manual
                          </span>
                        </span>
                        <span
                          v-if="
                            player.extracted_name !== player.player_name &&
                            player.extracted_name !== 'Manual Entry'
                          "
                          class="extracted-name"
                        >
                          (OCR: {{ player.extracted_name }})
                        </span>
                      </div>
                    </td>
                    <td>
                      <input
                        type="number"
                        v-model.number="player.stats.minutes"
                        min="0"
                        max="48"
                        class="stat-input"
                      />
                    </td>
                    <td>
                      <input
                        type="number"
                        v-model.number="player.stats.points"
                        min="0"
                        class="stat-input"
                      />
                    </td>
                    <td>
                      <input
                        type="number"
                        v-model.number="player.stats.rebounds"
                        min="0"
                        class="stat-input"
                      />
                    </td>
                    <td>
                      <input
                        type="number"
                        v-model.number="player.stats.assists"
                        min="0"
                        class="stat-input"
                      />
                    </td>
                    <td>
                      <input
                        type="number"
                        v-model.number="player.stats.steals"
                        min="0"
                        class="stat-input"
                      />
                    </td>
                    <td>
                      <input
                        type="number"
                        v-model.number="player.stats.blocks"
                        min="0"
                        class="stat-input"
                      />
                    </td>
                    <td>
                      <input
                        type="number"
                        v-model.number="player.stats.turnovers"
                        min="0"
                        class="stat-input"
                      />
                    </td>
                    <td>
                      <input
                        type="number"
                        v-model.number="player.stats.fgm"
                        min="0"
                        class="stat-input stat-input-small"
                      />
                    </td>
                    <td>
                      <input
                        type="number"
                        v-model.number="player.stats.fga"
                        min="0"
                        class="stat-input stat-input-small"
                      />
                    </td>
                    <td>
                      <input
                        type="number"
                        v-model.number="player.stats.tpm"
                        min="0"
                        class="stat-input stat-input-small"
                      />
                    </td>
                    <td>
                      <input
                        type="number"
                        v-model.number="player.stats.tpa"
                        min="0"
                        class="stat-input stat-input-small"
                      />
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Unmatched Players -->
          <div
            v-if="editableStats.unmatched && editableStats.unmatched.length > 0"
            class="unmatched-players-section"
          >
            <h3 class="table-title">⚠ Unmatched Players ({{ editableStats.unmatched.length }})</h3>
            <p class="unmatched-info">
              These players were not found in your collection and will not be saved.
            </p>
            <div class="unmatched-list">
              <div
                v-for="(player, index) in editableStats.unmatched"
                :key="index"
                class="unmatched-player"
              >
                <span class="unmatched-name">{{ player.extracted_name }}</span>
                <span class="unmatched-stats">
                  {{ player.stats.points }} PTS, {{ player.stats.rebounds }} REB,
                  {{ player.stats.assists }} AST
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="review-actions">
          <button @click="cancelReview" class="cancel-btn" :disabled="isConfirming">Cancel</button>
          <button
            @click="confirmAndSave"
            class="confirm-btn"
            :disabled="isConfirming || !hasMatchedPlayers"
          >
            <span v-if="!isConfirming">✓ Confirm & Save Stats</span>
            <span v-else>
              <svg class="spinner" viewBox="0 0 24 24">
                <circle
                  class="spinner-circle"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                  fill="none"
                />
              </svg>
              Saving...
            </span>
          </button>
        </div>
      </div>

      <!-- Step 3: Results -->
      <div v-else-if="step === 'results'" class="results-section">
        <h2 class="results-title">✅ Stats Saved Successfully!</h2>

        <!-- Results Tabs -->
        <div class="results-tabs">
          <button
            @click="activeResultsTab = 'player-progress'"
            :class="['results-tab', { active: activeResultsTab === 'player-progress' }]"
          >
            ⭐ Player Progress
          </button>
          <button
            @click="activeResultsTab = 'challenges'"
            :class="['results-tab', { active: activeResultsTab === 'challenges' }]"
          >
            🎯 Challenges
          </button>
          <button
            @click="activeResultsTab = 'rewards'"
            :class="['results-tab', { active: activeResultsTab === 'rewards' }]"
          >
            🎁 Rewards
          </button>
        </div>

        <!-- Player Progress Tab -->
        <div v-show="activeResultsTab === 'player-progress'" class="results-tab-content">
          <div
            v-if="savedResults.program_results?.pxp_earned?.length > 0"
            class="player-progress-grid"
          >
            <div
              v-for="player in savedResults.program_results.pxp_earned"
              :key="player.player_id"
              class="player-progress-card"
            >
              <!-- Player Header -->
              <div class="player-progress-header">
                <div class="player-info-block">
                  <h4 class="player-name-title">{{ player.player_name }}</h4>
                  <div class="pxp-earned-display">
                    <span class="pxp-amount-large">+{{ player.pxp_earned.toLocaleString() }}</span>
                    <span class="pxp-label">PXP</span>
                  </div>
                  <div v-if="player.achievement" class="achievement-inline">
                    <span
                      class="achievement-badge-small"
                      :class="player.achievement.toLowerCase().replace('-', '')"
                    >
                      {{ player.achievement === "Triple-Double" ? "🏆" : "⭐" }}
                      {{ player.achievement }}
                    </span>
                    <span v-if="player.bonus > 0" class="bonus-indicator">+{{ player.bonus }}</span>
                  </div>
                </div>
              </div>

              <!-- Level Up Celebration -->
              <div v-if="player.level_up" class="level-up-banner">
                🎉 LEVEL UP! {{ player.old_parallel.numeral }} → {{ player.new_parallel.numeral }}
              </div>

              <!-- Parallel Progress Bar -->
              <div class="parallel-progress">
                <div class="parallel-info">
                  <div class="parallel-current">
                    <span class="parallel-numeral" :style="{ color: player.new_parallel.color }">
                      {{ player.new_parallel.numeral || "Base" }}
                    </span>
                    <span class="parallel-name">{{ player.new_parallel.name || "Base" }}</span>
                  </div>
                  <div class="parallel-next">
                    <span v-if="player.new_parallel.next_threshold" class="pxp-to-next">
                      {{ player.new_parallel.pxp_to_next }} to next
                    </span>
                    <span v-else class="max-level">MAX LEVEL</span>
                  </div>
                </div>

                <!-- Progress Bar -->
                <div class="progress-bar-container">
                  <div
                    class="progress-bar-fill"
                    :style="{
                      width: player.new_parallel.progress_to_next + '%',
                      backgroundColor: player.new_parallel.color,
                    }"
                  ></div>
                </div>

                <!-- PXP Numbers -->
                <div class="pxp-thresholds">
                  <span class="current-pxp">{{ player.new_pxp.toLocaleString() }} PXP</span>
                  <span v-if="player.new_parallel.next_threshold" class="next-threshold">
                    {{ player.new_parallel.next_threshold.toLocaleString() }}
                  </span>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="no-data-message">
            <p>No player XP data available</p>
          </div>
        </div>

        <!-- Challenges Tab -->
        <div v-show="activeResultsTab === 'challenges'" class="results-tab-content">
          <!-- Challenge Progress -->
          <div
            v-if="
              savedResults.program_results &&
              (savedResults.program_results.challenges_updated?.length > 0 ||
                savedResults.program_results.challenges_completed?.length > 0)
            "
            class="challenge-progress-section"
          >
            <h3 class="section-title">🎯 Challenge Progress</h3>

            <!-- Completed Challenges -->
            <div
              v-if="
                savedResults.program_results.challenges_completed &&
                savedResults.program_results.challenges_completed.length > 0
              "
              class="completed-challenges"
            >
              <h4 class="subsection-title">✅ Completed!</h4>
              <div class="challenge-list">
                <div
                  v-for="challenge in savedResults.program_results.challenges_completed"
                  :key="challenge.challenge_id"
                  class="challenge-item completed"
                >
                  <div class="challenge-header">
                    <div class="challenge-info">
                      <span class="program-badge">{{ challenge.program_name }}</span>
                      <span class="challenge-reward">{{ challenge.reward }}</span>
                    </div>
                  </div>
                  <div class="challenge-description">{{ challenge.challenge_description }}</div>
                  <div class="challenge-progress-info">
                    <span class="progress-added">+{{ challenge.progress_added }}</span>
                    <span class="progress-total">
                      {{ challenge.current_progress }} / {{ challenge.target_value }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Updated Challenges -->
            <div
              v-if="
                savedResults.program_results.challenges_updated &&
                savedResults.program_results.challenges_updated.length > 0
              "
              class="updated-challenges"
            >
              <h4 class="subsection-title">📈 Progress Updated</h4>
              <div class="challenge-list">
                <div
                  v-for="challenge in savedResults.program_results.challenges_updated"
                  :key="challenge.challenge_id"
                  class="challenge-item updated"
                >
                  <div class="challenge-header">
                    <div class="challenge-info">
                      <span class="program-badge">{{ challenge.program_name }}</span>
                    </div>
                    <div class="progress-percentage">
                      {{ Math.round(challenge.progress_percentage) }}%
                    </div>
                  </div>
                  <div class="challenge-description">{{ challenge.challenge_description }}</div>
                  <div class="challenge-progress-bar">
                    <div
                      class="progress-bar-fill"
                      :style="{ width: challenge.progress_percentage + '%' }"
                    ></div>
                  </div>
                  <div class="challenge-progress-info">
                    <span class="progress-added">+{{ challenge.progress_added }}</span>
                    <span class="progress-total">
                      {{ challenge.current_progress }} / {{ challenge.target_value }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
            <div v-else class="no-data-message">
              <p>No challenge updates this game</p>
            </div>
          </div>

          <!-- Rewards Tab -->
          <div v-show="activeResultsTab === 'rewards'" class="results-tab-content">
            <!-- Rewards Summary -->
            <div v-if="savedResults.rewards_summary" class="rewards-summary-section">
              <h3 class="section-title">🎁 Rewards Earned</h3>

              <!-- XP and Stubs Summary -->
              <div class="rewards-totals">
                <div v-if="savedResults.rewards_summary.total_xp > 0" class="reward-total-item xp">
                  <div class="reward-icon">⭐</div>
                  <div class="reward-info">
                    <div class="reward-label">XP Earned</div>
                    <div class="reward-amount">
                      {{ savedResults.rewards_summary.total_xp.toLocaleString() }}
                    </div>
                  </div>
                </div>
                <div
                  v-if="savedResults.rewards_summary.total_stubs > 0"
                  class="reward-total-item stubs"
                >
                  <div class="reward-icon">💰</div>
                  <div class="reward-info">
                    <div class="reward-label">Stubs Earned</div>
                    <div class="reward-amount">
                      {{ savedResults.rewards_summary.total_stubs.toLocaleString() }}
                    </div>
                  </div>
                </div>
              </div>

              <!-- Player Cards -->
              <div v-if="savedResults.rewards_summary.players?.length > 0" class="reward-players">
                <h4 class="reward-subsection-title">🏀 Player Cards Unlocked</h4>
                <div class="reward-players-grid">
                  <div
                    v-for="player in savedResults.rewards_summary.players"
                    :key="player.id"
                    class="reward-player-card"
                  >
                    <div class="player-image-container">
                      <img
                        v-if="player.image_url"
                        :src="getFullImageUrl(player.image_url)"
                        :alt="player.name"
                        class="player-image"
                      />
                      <div v-else class="player-image-placeholder">
                        {{ player.name.charAt(0) }}
                      </div>
                    </div>
                    <div class="player-info">
                      <div class="player-rating">{{ player.overall_rating }}</div>
                      <div class="player-name">{{ player.name }}</div>
                      <div class="player-tier">{{ player.card_tier }}</div>
                      <div class="player-source">{{ player.from_program }}</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Packs -->
              <div v-if="savedResults.rewards_summary.packs?.length > 0" class="reward-packs">
                <h4 class="reward-subsection-title">📦 Packs Unlocked</h4>
                <div class="reward-packs-list">
                  <div
                    v-for="(pack, index) in savedResults.rewards_summary.packs"
                    :key="index"
                    class="reward-pack-item"
                  >
                    <div class="pack-icon">📦</div>
                    <div class="pack-info">
                      <div class="pack-name">{{ pack.name }}</div>
                      <div class="pack-details">
                        <span class="pack-quantity">×{{ pack.quantity }}</span>
                        <span class="pack-source">{{ pack.from_program }}</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- No Rewards Message -->
              <div
                v-if="
                  !savedResults.rewards_summary.total_xp &&
                  !savedResults.rewards_summary.total_stubs &&
                  !savedResults.rewards_summary.players?.length &&
                  !savedResults.rewards_summary.packs?.length
                "
                class="no-rewards-message"
              >
                <p>No rewards earned this game. Keep playing to unlock rewards!</p>
              </div>
            </div>
            <div v-else class="no-data-message">
              <p>No rewards earned this game</p>
            </div>
          </div>
        </div>

        <!-- Upload Another Button -->
        <button @click="reset" class="submit-btn">Upload Another Screenshot</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import api from "@/services/api";

const router = useRouter();

const step = ref("upload"); // 'upload', 'review', 'results'
const lineups = ref([]);
const selectedLineupId = ref(null);
const selectedFile = ref(null);
const previewUrl = ref(null);
const isDragging = ref(false);
const isUploading = ref(false);
const isConfirming = ref(false);
const errorMessage = ref(null);
const fileInput = ref(null);

const extractedData = ref(null);
const editableStats = ref({ matched: [], unmatched: [] });
const savedResults = ref(null);
const selectedLineup = ref(null);
const activeResultsTab = ref("player-progress");

const hasMatchedPlayers = computed(() => {
  return editableStats.value.matched && editableStats.value.matched.length > 0;
});

const getFullImageUrl = (url) => {
  if (!url) return "";
  if (url.startsWith("http")) return url;
  return `http://localhost:8000${url}`;
};

onMounted(async () => {
  try {
    const response = await api.getLineups();
    lineups.value = response.data.lineups || response.data || [];
  } catch (error) {
    console.error("Failed to load lineups:", error);
  }
});

const handleFileSelect = (event) => {
  const file = event.target.files[0];
  if (file) {
    setFile(file);
  }
};

const handleDrop = (event) => {
  isDragging.value = false;
  const file = event.dataTransfer.files[0];
  if (file && file.type.startsWith("image/")) {
    setFile(file);
  }
};

const setFile = (file) => {
  selectedFile.value = file;
  previewUrl.value = URL.createObjectURL(file);
  errorMessage.value = null;
};

const clearFile = () => {
  selectedFile.value = null;
  if (previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value);
  }
  previewUrl.value = null;
  if (fileInput.value) {
    fileInput.value.value = "";
  }
};

const formatFileSize = (bytes) => {
  if (bytes < 1024) return bytes + " B";
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + " KB";
  return (bytes / (1024 * 1024)).toFixed(1) + " MB";
};

const uploadScreenshot = async () => {
  if (!selectedFile.value) return;

  isUploading.value = true;
  errorMessage.value = null;

  try {
    const response = await api.uploadGameScreenshot(selectedFile.value, selectedLineupId.value);

    if (response.data.success) {
      extractedData.value = response.data.data;
      // Make a deep copy of the stats for editing
      editableStats.value = {
        matched: JSON.parse(JSON.stringify(response.data.data.match_results.matched || [])),
        unmatched: JSON.parse(JSON.stringify(response.data.data.match_results.unmatched || [])),
      };

      // Store selected lineup for later reference
      selectedLineup.value = selectedLineupId.value
        ? lineups.value.find((l) => l.id === selectedLineupId.value)
        : null;

      // Add missing lineup players if lineup was selected
      if (selectedLineup.value) {
        addMissingLineupPlayers();
      }

      step.value = "review";
    } else {
      errorMessage.value = response.data.message || "Failed to process screenshot";
    }
  } catch (error) {
    console.error("Upload failed:", error);
    errorMessage.value =
      error.response?.data?.message || "Failed to upload screenshot. Please try again.";
  } finally {
    isUploading.value = false;
  }
};

const addMissingLineupPlayers = () => {
  if (!selectedLineup.value || !selectedLineup.value.slots) return;

  // Get all player IDs that were matched
  const matchedPlayerIds = editableStats.value.matched.map((m) => m.player_id);

  // Check each slot in the lineup
  selectedLineup.value.slots.forEach((slot) => {
    if (slot.user_card && slot.user_card.player) {
      const player = slot.user_card.player;
      const playerId = player.id;

      // If this player wasn't matched, add an empty row for manual entry
      if (!matchedPlayerIds.includes(playerId)) {
        editableStats.value.matched.push({
          player_name: player.name,
          player_id: playerId,
          user_card_id: slot.user_card.id,
          extracted_name: "Manual Entry",
          similarity: 0,
          stats: {
            player_name: player.name,
            minutes: 0,
            points: 0,
            rebounds: 0,
            assists: 0,
            steals: 0,
            blocks: 0,
            turnovers: 0,
            fgm: 0,
            fga: 0,
            tpm: 0,
            tpa: 0,
          },
        });
      }
    }
  });
};

const confirmAndSave = async () => {
  if (!extractedData.value?.game_session_id) return;

  isConfirming.value = true;
  errorMessage.value = null;

  try {
    // Prepare stats for API (just the stats objects with player_name)
    const playerStats = editableStats.value.matched.map((player) => ({
      player_name: player.player_name,
      ...player.stats,
    }));

    const response = await api.confirmStats(extractedData.value.game_session_id, playerStats);

    if (response.data.success) {
      savedResults.value = response.data.data;
      step.value = "results";
    } else {
      errorMessage.value = response.data.message || "Failed to save stats";
    }
  } catch (error) {
    console.error("Confirmation failed:", error);
    errorMessage.value = error.response?.data?.message || "Failed to save stats. Please try again.";
  } finally {
    isConfirming.value = false;
  }
};

const cancelReview = () => {
  step.value = "upload";
  extractedData.value = null;
  editableStats.value = { matched: [], unmatched: [] };
};

const reset = () => {
  step.value = "upload";
  selectedFile.value = null;
  if (previewUrl.value) {
    URL.revokeObjectURL(previewUrl.value);
  }
  previewUrl.value = null;
  if (fileInput.value) {
    fileInput.value.value = "";
  }
  extractedData.value = null;
  editableStats.value = { matched: [], unmatched: [] };
  savedResults.value = null;
  errorMessage.value = null;
};
</script>

<style scoped>
.stats-upload-container {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 2rem;
}

.stats-upload-card {
  max-width: 1200px;
  margin: 0 auto;
  background: rgba(255, 255, 255, 0.95);
  border-radius: 20px;
  padding: 2.5rem;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}

.title {
  font-size: 2.5rem;
  font-weight: 700;
  color: #1a202c;
  margin: 0 0 0.5rem 0;
  text-align: center;
}

.subtitle {
  font-size: 1.1rem;
  color: #718096;
  margin: 0 0 2rem 0;
  text-align: center;
}

/* Upload Step Styles */
.form-group {
  margin-bottom: 2rem;
}

.form-group label {
  display: block;
  font-size: 1rem;
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 0.5rem;
}

.lineup-select {
  width: 100%;
  padding: 0.75rem;
  font-size: 1rem;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  background: white;
  color: #2d3748;
  cursor: pointer;
  transition: all 0.2s;
}

.lineup-select:hover:not(:disabled) {
  border-color: #667eea;
}

.lineup-select:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.lineup-select:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.upload-area {
  margin-bottom: 2rem;
}

.dropzone {
  border: 3px dashed #cbd5e0;
  border-radius: 15px;
  padding: 3rem;
  text-align: center;
  transition: all 0.3s;
  background: #f7fafc;
  cursor: pointer;
  position: relative;
}

.dropzone.drag-over {
  border-color: #667eea;
  background: #edf2f7;
}

.dropzone.has-file {
  border-style: solid;
  border-color: #48bb78;
  background: white;
  padding: 1.5rem;
}

.file-input {
  position: absolute;
  width: 0;
  height: 0;
  opacity: 0;
}

.upload-prompt {
  color: #4a5568;
}

.upload-icon {
  width: 64px;
  height: 64px;
  margin: 0 auto 1rem;
  color: #667eea;
}

.browse-link {
  color: #667eea;
  font-weight: 600;
  cursor: pointer;
  text-decoration: underline;
}

.file-hint {
  font-size: 0.875rem;
  color: #a0aec0;
  margin-top: 0.5rem;
}

.file-preview {
  display: flex;
  gap: 1.5rem;
  align-items: center;
}

.preview-image {
  width: 200px;
  height: 150px;
  object-fit: cover;
  border-radius: 10px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.file-info {
  flex: 1;
  text-align: left;
}

.file-name {
  font-size: 1.1rem;
  font-weight: 600;
  color: #2d3748;
  margin: 0 0 0.25rem 0;
  word-break: break-word;
}

.file-size {
  font-size: 0.875rem;
  color: #718096;
  margin: 0 0 1rem 0;
}

.remove-btn {
  padding: 0.5rem 1rem;
  background: #f56565;
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.remove-btn:hover:not(:disabled) {
  background: #c53030;
  transform: translateY(-1px);
}

.remove-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.submit-btn,
.confirm-btn {
  width: 100%;
  padding: 1rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 1.1rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}

.submit-btn:hover:not(:disabled),
.confirm-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
}

.submit-btn:disabled,
.confirm-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.spinner {
  width: 20px;
  height: 20px;
  animation: spin 1s linear infinite;
}

.spinner-circle {
  stroke-dasharray: 50;
  stroke-dashoffset: 25;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.error-message {
  margin-top: 1rem;
  padding: 1rem;
  background: #fff5f5;
  border-left: 4px solid #fc8181;
  border-radius: 8px;
  color: #c53030;
  font-weight: 600;
}

/* Review Step Styles */
.review-section {
  animation: slideIn 0.3s ease;
}

@keyframes slideIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

.review-header {
  text-align: center;
  margin-bottom: 2rem;
}

.review-title {
  font-size: 2rem;
  font-weight: 700;
  color: #1a202c;
  margin: 0 0 0.5rem 0;
}

.review-subtitle {
  font-size: 1rem;
  color: #718096;
  margin: 0;
}

.screenshot-preview {
  margin-bottom: 2rem;
  text-align: center;
}

.screenshot-img {
  max-width: 100%;
  max-height: 300px;
  border-radius: 10px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.validation-warning {
  background: #fffaf0;
  border-left: 4px solid #ed8936;
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 2rem;
}

.validation-warning h4 {
  margin: 0 0 0.5rem 0;
  color: #c05621;
}

.validation-warning ul {
  margin: 0;
  padding-left: 1.5rem;
  color: #744210;
}

.stats-tables {
  margin-bottom: 2rem;
}

.matched-players-section,
.unmatched-players-section {
  margin-bottom: 2rem;
}

.table-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #2d3748;
  margin: 0 0 1rem 0;
}

.stats-table-container {
  overflow-x: auto;
  border-radius: 10px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.stats-table {
  width: 100%;
  border-collapse: collapse;
  background: white;
}

.stats-table th {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 0.75rem 0.5rem;
  text-align: center;
  font-weight: 600;
  font-size: 0.875rem;
  border-bottom: 2px solid #5a67d8;
}

.stats-table th:first-child {
  text-align: left;
  padding-left: 1rem;
  border-top-left-radius: 10px;
}

.stats-table th:last-child {
  border-top-right-radius: 10px;
}

.stats-table td {
  padding: 0.75rem 0.5rem;
  text-align: center;
  border-bottom: 1px solid #e2e8f0;
}

.stats-table tbody tr:hover {
  background: #f7fafc;
}

.stats-table tbody tr:last-child td {
  border-bottom: none;
}

.player-name-cell {
  text-align: left !important;
  padding-left: 1rem !important;
}

.player-name-info {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.actual-name {
  font-weight: 600;
  color: #2d3748;
}

.extracted-name {
  font-size: 0.75rem;
  color: #718096;
  font-style: italic;
}

.manual-badge {
  display: inline-block;
  margin-left: 0.5rem;
  padding: 0.125rem 0.5rem;
  background: #fbbf24;
  color: #78350f;
  font-size: 0.75rem;
  font-weight: 700;
  border-radius: 6px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.manual-entry-row {
  background: #fffbeb !important;
  border-left: 4px solid #fbbf24;
}

.manual-entry-row:hover {
  background: #fef3c7 !important;
}

.stat-input {
  width: 60px;
  padding: 0.5rem;
  border: 2px solid #e2e8f0;
  border-radius: 6px;
  text-align: center;
  font-size: 0.875rem;
  font-weight: 600;
  transition: all 0.2s;
}

.stat-input-small {
  width: 50px;
}

.stat-input:hover {
  border-color: #cbd5e0;
}

.stat-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.unmatched-info {
  font-size: 0.875rem;
  color: #718096;
  margin-bottom: 1rem;
}

.unmatched-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.unmatched-player {
  background: #fff5f5;
  padding: 0.75rem 1rem;
  border-radius: 8px;
  border-left: 4px solid #ed8936;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.unmatched-name {
  font-weight: 600;
  color: #c05621;
}

.unmatched-stats {
  font-size: 0.875rem;
  color: #744210;
}

.review-actions {
  display: flex;
  gap: 1rem;
  margin-top: 2rem;
}

.cancel-btn {
  flex: 1;
  padding: 1rem;
  background: #e2e8f0;
  color: #2d3748;
  border: none;
  border-radius: 12px;
  font-size: 1.1rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s;
}

.cancel-btn:hover:not(:disabled) {
  background: #cbd5e0;
  transform: translateY(-2px);
}

.cancel-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.confirm-btn {
  flex: 2;
}

/* Results Step Styles */
.results-section {
  animation: slideIn 0.3s ease;
}

.results-title {
  font-size: 2rem;
  font-weight: 700;
  color: #1a202c;
  margin: 0 0 2rem 0;
  text-align: center;
}

/* Results Tabs */
.results-tabs {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 2rem;
  border-bottom: 2px solid #e2e8f0;
}

.results-tab {
  padding: 1rem 2rem;
  background: transparent;
  border: none;
  border-bottom: 3px solid transparent;
  color: #718096;
  font-weight: 600;
  font-size: 1rem;
  cursor: pointer;
  transition: all 0.3s;
}

.results-tab:hover {
  color: #667eea;
  background: #f7fafc;
}

.results-tab.active {
  color: #667eea;
  border-bottom-color: #667eea;
  background: #f7fafc;
}

.results-tab-content {
  animation: fadeIn 0.3s;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Player Progress Grid */
.player-progress-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
  gap: 1.5rem;
  margin-top: 1rem;
}

.player-progress-card {
  background: white;
  border-radius: 12px;
  border: 2px solid #e2e8f0;
  overflow: hidden;
  transition: all 0.3s;
}

.player-progress-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
}

.player-progress-header {
  padding: 1.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.player-info-block {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.player-name-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 700;
}

.pxp-earned-display {
  display: flex;
  align-items: baseline;
  gap: 0.5rem;
}

.pxp-amount-large {
  font-size: 2rem;
  font-weight: 800;
  line-height: 1;
}

.pxp-label {
  font-size: 0.875rem;
  font-weight: 600;
  opacity: 0.9;
}

.achievement-inline {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.achievement-badge-small {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.25rem 0.75rem;
  border-radius: 8px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.achievement-badge-small.doubledouble {
  background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
  color: #78350f;
}

.achievement-badge-small.tripledouble {
  background: linear-gradient(135deg, #a855f7 0%, #7c3aed 100%);
  color: white;
  animation: pulse 2s infinite;
}

.bonus-indicator {
  padding: 0.25rem 0.5rem;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 600;
}

/* Level Up Banner */
.level-up-banner {
  padding: 0.75rem 1.5rem;
  background: linear-gradient(90deg, #10b981 0%, #059669 100%);
  color: white;
  text-align: center;
  font-weight: 700;
  font-size: 1rem;
  letter-spacing: 0.5px;
  animation: celebratePulse 1.5s infinite;
}

@keyframes celebratePulse {
  0%,
  100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.02);
  }
}

/* Parallel Progress */
.parallel-progress {
  padding: 1.5rem;
}

.parallel-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.75rem;
}

.parallel-current {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.parallel-numeral {
  font-size: 1.5rem;
  font-weight: 800;
  line-height: 1;
}

.parallel-name {
  font-size: 0.875rem;
  font-weight: 600;
  color: #718096;
}

.parallel-next {
  text-align: right;
}

.pxp-to-next {
  font-size: 0.875rem;
  color: #718096;
  font-weight: 600;
}

.max-level {
  font-size: 0.875rem;
  color: #10b981;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

/* Progress Bar */
.progress-bar-container {
  width: 100%;
  height: 12px;
  background: #e2e8f0;
  border-radius: 6px;
  overflow: hidden;
  margin-bottom: 0.75rem;
}

.progress-bar-fill {
  height: 100%;
  border-radius: 6px;
  transition: width 1s ease-out, background-color 0.3s;
  background: linear-gradient(
    90deg,
    currentColor 0%,
    currentColor 80%,
    rgba(255, 255, 255, 0.3) 100%
  );
}

.pxp-thresholds {
  display: flex;
  justify-content: space-between;
  font-size: 0.875rem;
  font-weight: 600;
}

.current-pxp {
  color: #667eea;
}

.next-threshold {
  color: #a0aec0;
}

.no-data-message {
  padding: 3rem;
  text-align: center;
  color: #a0aec0;
  font-style: italic;
}

/* Player PXP Summary Styles (kept for backwards compatibility if needed) */
.pxp-summary-section {
  margin-bottom: 2rem;
  padding: 1.5rem;
  background: white;
  border-radius: 12px;
  border: 2px solid #e2e8f0;
}

.pxp-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
  margin-top: 1rem;
}

.pxp-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.25rem;
  background: #f7fafc;
  border-radius: 10px;
  border: 2px solid #e2e8f0;
  transition: all 0.3s;
}

.pxp-item.has-achievement {
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
  border-color: #fbbf24;
  box-shadow: 0 2px 8px rgba(251, 191, 36, 0.2);
}

.pxp-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.pxp-player-info {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  flex: 1;
}

.pxp-player-info .player-name {
  font-weight: 600;
  font-size: 1rem;
  color: #2d3748;
}

.achievement-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  padding: 0.25rem 0.75rem;
  border-radius: 8px;
  font-size: 0.875rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.achievement-badge.doubledouble {
  background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
  color: #78350f;
  box-shadow: 0 2px 4px rgba(245, 158, 11, 0.3);
}

.achievement-badge.tripledouble {
  background: linear-gradient(135deg, #a855f7 0%, #7c3aed 100%);
  color: white;
  box-shadow: 0 2px 4px rgba(124, 58, 237, 0.4);
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%,
  100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
}

.pxp-amount {
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 0.25rem;
}

.pxp-value {
  font-size: 1.25rem;
  font-weight: 700;
  color: #667eea;
}

.pxp-bonus {
  font-size: 0.75rem;
  font-weight: 600;
  color: #059669;
  background: #d1fae5;
  padding: 0.125rem 0.5rem;
  border-radius: 6px;
}

.challenge-progress-section {
  margin-bottom: 2rem;
  padding: 1.5rem;
  background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
  border-radius: 12px;
  border: 2px solid #e2e8f0;
}

.section-title {
  font-size: 1.5rem;
  font-weight: 600;
  color: #2d3748;
  margin: 0 0 1.5rem 0;
}

.subsection-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0 0 1rem 0;
}

.completed-challenges,
.updated-challenges {
  margin-bottom: 1.5rem;
}

.completed-challenges:last-child,
.updated-challenges:last-child {
  margin-bottom: 0;
}

.challenge-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.challenge-item {
  background: white;
  padding: 1rem;
  border-radius: 10px;
  border: 2px solid transparent;
  transition: all 0.3s;
}

.challenge-item.completed {
  border-color: #48bb78;
  background: linear-gradient(135deg, #f0fff4 0%, #c6f6d5 100%);
}

.challenge-item.updated {
  border-color: #667eea;
  background: white;
}

.challenge-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.challenge-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.5rem;
}

.challenge-info {
  display: flex;
  gap: 0.5rem;
  align-items: center;
  flex-wrap: wrap;
}

.program-badge {
  background: #667eea;
  color: white;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.challenge-reward {
  background: #fbbf24;
  color: #78350f;
  padding: 0.25rem 0.75rem;
  border-radius: 12px;
  font-size: 0.75rem;
  font-weight: 700;
}

.progress-percentage {
  font-size: 1.25rem;
  font-weight: 800;
  color: #667eea;
}

.challenge-description {
  font-size: 0.95rem;
  color: #4a5568;
  margin-bottom: 0.75rem;
  line-height: 1.5;
}

.challenge-progress-bar {
  background: #e2e8f0;
  height: 8px;
  border-radius: 4px;
  overflow: hidden;
  margin-bottom: 0.5rem;
}

.progress-bar-fill {
  background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
  height: 100%;
  transition: width 0.5s ease;
  border-radius: 4px;
}

.challenge-progress-info {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.875rem;
}

.progress-added {
  color: #48bb78;
  font-weight: 700;
  background: #f0fff4;
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
}

.progress-total {
  color: #718096;
  font-weight: 600;
}

.results-summary {
  background: white;
  padding: 1.5rem;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  margin-bottom: 2rem;
}

.summary-stats {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.75rem;
  background: #f7fafc;
  border-radius: 8px;
}

.summary-label {
  font-weight: 600;
  color: #4a5568;
}

.summary-value {
  font-weight: 700;
  font-size: 1.25rem;
  color: #667eea;
}

/* Rewards Summary Styles */
.rewards-summary-section {
  margin-bottom: 2rem;
  padding: 1.5rem;
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
  border-radius: 12px;
  border: 2px solid #fbbf24;
}

.rewards-totals {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.reward-total-item {
  background: white;
  padding: 1.25rem;
  border-radius: 10px;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: transform 0.3s;
}

.reward-total-item:hover {
  transform: translateY(-2px);
}

.reward-icon {
  font-size: 2.5rem;
  line-height: 1;
}

.reward-info {
  flex: 1;
}

.reward-label {
  font-size: 0.875rem;
  color: #718096;
  margin-bottom: 0.25rem;
}

.reward-amount {
  font-size: 1.5rem;
  font-weight: 700;
  color: #1a202c;
}

.reward-subsection-title {
  font-size: 1.1rem;
  font-weight: 700;
  color: #2d3748;
  margin: 1.5rem 0 1rem 0;
}

.reward-players-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
  gap: 1rem;
}

.reward-player-card {
  background: white;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: transform 0.3s;
}

.reward-player-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.player-image-container {
  width: 100%;
  aspect-ratio: 1;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
}

.player-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.player-image-placeholder {
  font-size: 3rem;
  font-weight: 800;
  color: white;
}

.player-info {
  padding: 1rem;
  text-align: center;
}

.player-rating {
  font-size: 1.5rem;
  font-weight: 800;
  color: #667eea;
  margin-bottom: 0.25rem;
}

.player-name {
  font-size: 0.875rem;
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 0.25rem;
}

.player-tier {
  font-size: 0.75rem;
  color: #718096;
  margin-bottom: 0.5rem;
}

.player-source {
  font-size: 0.7rem;
  color: #a0aec0;
  font-style: italic;
}

.reward-packs-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.reward-pack-item {
  background: white;
  padding: 1rem;
  border-radius: 10px;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
  transition: transform 0.3s;
}

.reward-pack-item:hover {
  transform: translateX(4px);
}

.pack-icon {
  font-size: 2rem;
  line-height: 1;
}

.pack-info {
  flex: 1;
}

.pack-name {
  font-size: 1rem;
  font-weight: 600;
  color: #2d3748;
  margin-bottom: 0.25rem;
}

.pack-details {
  display: flex;
  gap: 0.75rem;
  font-size: 0.875rem;
}

.pack-quantity {
  color: #667eea;
  font-weight: 700;
}

.pack-source {
  color: #a0aec0;
  font-style: italic;
}

.no-rewards-message {
  text-align: center;
  padding: 2rem;
  color: #718096;
  font-style: italic;
}

/* Responsive */
@media (max-width: 768px) {
  .stats-upload-container {
    padding: 1rem;
  }

  .stats-upload-card {
    padding: 1.5rem;
  }

  .title {
    font-size: 2rem;
  }

  .file-preview {
    flex-direction: column;
  }

  .preview-image {
    width: 100%;
  }

  .stats-table th,
  .stats-table td {
    padding: 0.5rem 0.25rem;
    font-size: 0.75rem;
  }

  .stat-input {
    width: 50px;
    padding: 0.35rem;
  }

  .stat-input-small {
    width: 40px;
  }

  .review-actions {
    flex-direction: column;
  }

  .reward-players-grid {
    grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
  }

  .rewards-totals {
    grid-template-columns: 1fr;
  }

  .results-tabs {
    flex-direction: column;
    gap: 0;
    border-bottom: none;
  }

  .results-tab {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #e2e8f0;
    border-left: 3px solid transparent;
  }

  .results-tab.active {
    border-bottom-color: #e2e8f0;
    border-left-color: #667eea;
  }

  .player-progress-grid {
    grid-template-columns: 1fr;
    gap: 1rem;
  }

  .player-name-title {
    font-size: 1.1rem;
  }

  .pxp-amount-large {
    font-size: 1.5rem;
  }
}
</style>
