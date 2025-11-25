<template>
  <div class="pack-form">
    <div class="header">
      <h1>{{ isEditMode ? "Edit Pack" : "Create Pack" }}</h1>
      <router-link to="/admin/packs" class="btn-back">← Back to Packs</router-link>
    </div>

    <div v-if="adminStore.loading" class="loading">Loading...</div>

    <form v-else @submit.prevent="handleSubmit" class="form">
      <div class="form-section">
        <h2>Pack Information</h2>
        <div class="form-grid">
          <div class="form-field full-width">
            <label>Name *</label>
            <input v-model="formData.name" type="text" required />
          </div>
          <div class="form-field full-width">
            <label>Description</label>
            <textarea v-model="formData.description" rows="3"></textarea>
          </div>
          <div class="form-field">
            <label>Type *</label>
            <select v-model="formData.type" required>
              <option value="">Select Type</option>
              <option value="standard">Standard</option>
              <option value="choice">Choice</option>
            </select>
          </div>
          <div class="form-field">
            <label>Cost (Stubs) *</label>
            <input v-model.number="formData.cost" type="number" min="0" required />
          </div>
          <div class="form-field">
            <label>Card Count *</label>
            <input v-model.number="formData.card_count" type="number" min="1" required />
          </div>
          <div class="form-field">
            <label>Choice Count</label>
            <input v-model.number="formData.choice_count" type="number" min="0" />
          </div>
          <div class="form-field">
            <label>Active</label>
            <div class="checkbox-field">
              <input v-model="formData.active" type="checkbox" id="active" />
              <label for="active">Pack is active</label>
            </div>
          </div>
          <div class="form-field">
            <label>Available in Shop</label>
            <div class="checkbox-field">
              <input v-model="formData.available_in_shop" type="checkbox" id="available_in_shop" />
              <label for="available_in_shop">Show in shop</label>
            </div>
            <span class="field-hint">Uncheck for reward-only packs</span>
          </div>
        </div>
      </div>

      <div class="form-section">
        <h2>Collection Restriction (Optional)</h2>
        <p class="help-text">
          Restrict this pack to only contain cards from a specific collection. Leave empty for all
          cards.
        </p>
        <div class="form-field">
          <label>Collection</label>
          <select v-model="formData.collection_id">
            <option :value="null">All Collections</option>
            <option v-for="collection in collections" :key="collection.id" :value="collection.id">
              {{ collection.name }} - {{ collection.sub_collection || "General" }}
            </option>
          </select>
        </div>
      </div>

      <div class="form-section">
        <h2>Odds Configuration (JSON)</h2>
        <p class="help-text">
          Enter the pack odds configuration as JSON. Example: {"bronze": 50, "silver": 30, "gold":
          20}
        </p>
        <div class="form-field">
          <textarea
            v-model="oddsConfigString"
            rows="10"
            class="json-editor"
            placeholder='{"bronze": 50, "silver": 30, "gold": 20}'
          ></textarea>
          <div v-if="jsonError" class="json-error">
            {{ jsonError }}
          </div>
        </div>
      </div>

      <div class="form-section" v-if="formData.type === 'choice'">
        <h2>Specified Players (Optional)</h2>
        <p class="help-text">
          For choice packs, you can specify exact players that will appear as options instead of
          random generation.
        </p>
        <div class="specified-players">
          <div
            v-for="(playerId, index) in specifiedPlayers"
            :key="index"
            class="specified-player-item"
          >
            <div class="form-field" style="flex: 1">
              <label>Player {{ index + 1 }}</label>
              <select v-model="specifiedPlayers[index]" required>
                <option :value="null">Select Player</option>
                <option v-for="player in adminStore.allPlayers" :key="player.id" :value="player.id">
                  {{ player.name }} - {{ player.team }} ({{ player.card_tier }})
                </option>
              </select>
            </div>
            <button type="button" @click="removeSpecifiedPlayer(index)" class="btn-remove">
              Remove
            </button>
          </div>
          <button type="button" @click="addSpecifiedPlayer" class="btn-add">+ Add Player</button>
        </div>
      </div>

      <div class="form-section">
        <h2>Featured Players (Optional)</h2>
        <p class="help-text">
          Add special "chase cards" with individual odds. For example, Dwyane Wade at 0.02%.
          Featured players are checked before normal tier-based selection.
        </p>
        <div class="featured-players">
          <div
            v-for="(featured, index) in featuredPlayers"
            :key="index"
            class="featured-player-item"
          >
            <div class="form-field" style="flex: 1">
              <label>Player</label>
              <select v-model="featuredPlayers[index].player_id" required>
                <option :value="null">Select Player</option>
                <option v-for="player in adminStore.allPlayers" :key="player.id" :value="player.id">
                  {{ player.name }} - {{ player.team }} ({{ player.card_tier }})
                </option>
              </select>
            </div>
            <div class="form-field" style="flex: 0 0 150px">
              <label>Odds (%)</label>
              <input
                v-model.number="featuredPlayers[index].odds"
                type="number"
                min="0"
                max="100"
                step="0.01"
                placeholder="0.02"
                required
              />
              <span class="field-hint">e.g., 0.02 for 0.02%</span>
            </div>
            <button type="button" @click="removeFeaturedPlayer(index)" class="btn-remove">
              Remove
            </button>
          </div>
          <button type="button" @click="addFeaturedPlayer" class="btn-add">
            + Add Featured Player
          </button>
        </div>
      </div>

      <div class="form-section">
        <h2>Player Pools (Optional)</h2>
        <p class="help-text">
          Create custom tiers with specific players and odds. For example, a base pool (90% chance)
          with 3 players and a medium pool (10% chance) with 3 players. Note: Player pools override
          tier-based odds when configured.
        </p>
        <div class="player-pools">
          <div v-for="(pool, poolIndex) in playerPools" :key="poolIndex" class="player-pool-item">
            <div class="pool-header">
              <div class="form-field" style="flex: 1">
                <label>Pool Name</label>
                <input
                  v-model="playerPools[poolIndex].name"
                  type="text"
                  placeholder="e.g., Base, Medium, Premium"
                  required
                />
              </div>
              <div class="form-field" style="flex: 0 0 150px">
                <label>Odds (%)</label>
                <input
                  v-model.number="playerPools[poolIndex].odds"
                  type="number"
                  min="0"
                  max="100"
                  step="1"
                  placeholder="90"
                  required
                />
              </div>
              <button type="button" @click="removePlayerPool(poolIndex)" class="btn-remove">
                Remove Pool
              </button>
            </div>
            <div class="pool-players">
              <label>Players in this pool:</label>
              <div class="pool-player-list">
                <div
                  v-for="(playerId, playerIndex) in playerPools[poolIndex].player_ids"
                  :key="playerIndex"
                  class="pool-player-item"
                >
                  <select v-model="playerPools[poolIndex].player_ids[playerIndex]" required>
                    <option :value="null">Select Player</option>
                    <option
                      v-for="player in adminStore.allPlayers"
                      :key="player.id"
                      :value="player.id"
                    >
                      {{ player.name }} - {{ player.team }} ({{ player.card_tier }})
                    </option>
                  </select>
                  <button
                    type="button"
                    @click="removePlayerFromPool(poolIndex, playerIndex)"
                    class="btn-remove-small"
                  >
                    ×
                  </button>
                </div>
                <button type="button" @click="addPlayerToPool(poolIndex)" class="btn-add-small">
                  + Add Player to Pool
                </button>
              </div>
            </div>
          </div>
          <button type="button" @click="addPlayerPool" class="btn-add">+ Add Player Pool</button>
        </div>
      </div>

      <div class="form-section">
        <h2>Guaranteed Tier Slots (Optional)</h2>
        <p class="help-text">
          Define slots that guarantee a minimum tier. For example, slot 5 could guarantee Emerald or
          above. Note: Ignored if using specified players.
        </p>
        <div class="guaranteed-slots">
          <div v-for="(slot, index) in guaranteedSlots" :key="index" class="guaranteed-slot-item">
            <div class="form-field">
              <label>Slot Number</label>
              <input
                v-model.number="slot.slot"
                type="number"
                min="1"
                :max="formData.card_count"
                required
              />
            </div>
            <div class="form-field">
              <label>Minimum Tier</label>
              <select v-model="slot.min_tier" required>
                <option value="bronze">Bronze</option>
                <option value="silver">Silver</option>
                <option value="gold">Gold</option>
                <option value="emerald">Emerald</option>
                <option value="sapphire">Sapphire</option>
                <option value="amethyst">Amethyst</option>
                <option value="diamond">Diamond</option>
                <option value="pink_diamond">Pink Diamond</option>
              </select>
            </div>
            <button type="button" @click="removeGuaranteedSlot(index)" class="btn-remove">
              Remove
            </button>
          </div>
          <button type="button" @click="addGuaranteedSlot" class="btn-add">
            + Add Guaranteed Slot
          </button>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn-submit" :disabled="adminStore.loading || !!jsonError">
          {{ isEditMode ? "Update Pack" : "Create Pack" }}
        </button>
        <router-link to="/admin/packs" class="btn-cancel">Cancel</router-link>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAdminStore } from "@/stores/admin";

const route = useRoute();
const router = useRouter();
const adminStore = useAdminStore();

const isEditMode = ref(false);
const oddsConfigString = ref("");
const jsonError = ref("");
const collections = ref<any[]>([]);
const guaranteedSlots = ref<Array<{ slot: number; min_tier: string }>>([]);
const specifiedPlayers = ref<number[]>([]);
const featuredPlayers = ref<Array<{ player_id: number | null; odds: number }>>([]);
const playerPools = ref<Array<{ name: string; odds: number; player_ids: number[] }>>([]);

const formData = ref({
  name: "",
  description: "",
  type: "",
  cost: 0,
  card_count: 1,
  choice_count: null,
  collection_id: null,
  active: true,
  available_in_shop: true,
  odds_config: null,
  guaranteed_slots: null,
  specified_players: null,
  featured_players: null,
  player_pools: null,
});

// Watch for changes in the JSON string and validate
watch(oddsConfigString, (newValue) => {
  if (!newValue.trim()) {
    jsonError.value = "";
    formData.value.odds_config = null;
    return;
  }

  try {
    const parsed = JSON.parse(newValue);
    formData.value.odds_config = parsed;
    jsonError.value = "";
  } catch (error: any) {
    jsonError.value = `Invalid JSON: ${error.message}`;
  }
});

function addGuaranteedSlot() {
  guaranteedSlots.value.push({ slot: formData.value.card_count, min_tier: "emerald" });
}

function removeGuaranteedSlot(index: number) {
  guaranteedSlots.value.splice(index, 1);
}

function addSpecifiedPlayer() {
  specifiedPlayers.value.push(null as any);
}

function removeSpecifiedPlayer(index: number) {
  specifiedPlayers.value.splice(index, 1);
}

function addFeaturedPlayer() {
  featuredPlayers.value.push({ player_id: null, odds: 0.1 });
}

function removeFeaturedPlayer(index: number) {
  featuredPlayers.value.splice(index, 1);
}

function addPlayerPool() {
  playerPools.value.push({ name: "", odds: 0, player_ids: [] });
}

function removePlayerPool(poolIndex: number) {
  playerPools.value.splice(poolIndex, 1);
}

function addPlayerToPool(poolIndex: number) {
  playerPools.value[poolIndex].player_ids.push(null as any);
}

function removePlayerFromPool(poolIndex: number, playerIndex: number) {
  playerPools.value[poolIndex].player_ids.splice(playerIndex, 1);
}

onMounted(async () => {
  // Load collections and players for the dropdowns
  await adminStore.fetchAllCollections();
  await adminStore.fetchAllPlayers();
  collections.value = adminStore.allCollections;

  const packId = route.params.id;
  if (packId) {
    isEditMode.value = true;
    const result = await adminStore.getPack(Number(packId));
    if (result.success) {
      formData.value = { ...result.data };
      // Convert odds_config object to JSON string
      if (formData.value.odds_config) {
        oddsConfigString.value = JSON.stringify(formData.value.odds_config, null, 2);
      }
      // Load guaranteed slots if they exist
      if (formData.value.guaranteed_slots) {
        guaranteedSlots.value = [...formData.value.guaranteed_slots];
      }
      // Load specified players if they exist
      if (formData.value.specified_players) {
        specifiedPlayers.value = [...formData.value.specified_players];
      }
      // Load featured players if they exist
      if (formData.value.featured_players) {
        featuredPlayers.value = [...formData.value.featured_players];
      }
      // Load player pools if they exist
      if (formData.value.player_pools) {
        playerPools.value = [...formData.value.player_pools];
      }
    }
  }
});

async function handleSubmit() {
  if (jsonError.value) {
    return;
  }

  // Add guaranteed_slots to formData
  formData.value.guaranteed_slots = guaranteedSlots.value.length > 0 ? guaranteedSlots.value : null;

  // Add specified_players to formData (filter out null values)
  const validSpecifiedPlayers = specifiedPlayers.value.filter((id) => id !== null);
  formData.value.specified_players =
    validSpecifiedPlayers.length > 0 ? validSpecifiedPlayers : null;

  // Add featured_players to formData (filter out invalid entries)
  const validFeaturedPlayers = featuredPlayers.value.filter(
    (fp) => fp.player_id !== null && fp.odds > 0
  );
  formData.value.featured_players = validFeaturedPlayers.length > 0 ? validFeaturedPlayers : null;

  // Add player_pools to formData (filter out invalid entries)
  const validPlayerPools = playerPools.value.filter(
    (pool) =>
      pool.name &&
      pool.odds > 0 &&
      pool.player_ids &&
      pool.player_ids.length > 0 &&
      pool.player_ids.every((id) => id !== null)
  );
  formData.value.player_pools = validPlayerPools.length > 0 ? validPlayerPools : null;

  let result;
  if (isEditMode.value) {
    result = await adminStore.updatePack((formData.value as any).id, formData.value);
  } else {
    result = await adminStore.createPack(formData.value);
  }

  if (result.success) {
    router.push("/admin/packs");
  } else {
    alert(`Failed: ${result.message}`);
  }
}
</script>

<style scoped>
.pack-form {
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

.help-text {
  color: #6b7280;
  font-size: 0.875rem;
  margin: 0 0 1rem 0;
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

.json-editor {
  font-family: "Monaco", "Menlo", "Ubuntu Mono", "Consolas", monospace;
  font-size: 0.875rem;
  background: #1e293b;
  color: #e2e8f0;
  padding: 1rem;
  border-radius: 6px;
  border: 1px solid #334155;
}

.json-error {
  margin-top: 0.5rem;
  padding: 0.75rem;
  background: #fee2e2;
  color: #991b1b;
  border-radius: 6px;
  font-size: 0.875rem;
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

.guaranteed-slots,
.specified-players,
.featured-players,
.player-pools {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.guaranteed-slot-item {
  display: grid;
  grid-template-columns: 1fr 1fr auto;
  gap: 1rem;
  align-items: end;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
}

.specified-player-item {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 1rem;
  align-items: end;
  padding: 1rem;
  background: #f9fafb;
  border-radius: 6px;
  border: 1px solid #e5e7eb;
}

.featured-player-item {
  display: grid;
  grid-template-columns: 1fr 150px auto;
  gap: 1rem;
  align-items: end;
  padding: 1rem;
  background: #fef3c7;
  border-radius: 6px;
  border: 1px solid #fbbf24;
}

.player-pool-item {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  padding: 1.5rem;
  background: #dbeafe;
  border-radius: 8px;
  border: 2px solid #3b82f6;
}

.pool-header {
  display: grid;
  grid-template-columns: 1fr 150px auto;
  gap: 1rem;
  align-items: end;
}

.pool-players {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.pool-players label {
  font-weight: 600;
  color: #1e40af;
}

.pool-player-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.pool-player-item {
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 0.5rem;
  align-items: center;
}

.pool-player-item select {
  padding: 0.5rem;
  border: 1px solid #cbd5e1;
  border-radius: 6px;
  background: white;
  font-size: 0.9rem;
}

.btn-remove-small {
  padding: 0.25rem 0.5rem;
  background: #ef4444;
  color: white;
  border: none;
  border-radius: 4px;
  font-weight: 600;
  cursor: pointer;
  font-size: 1.2rem;
  line-height: 1;
  width: 32px;
  height: 32px;
}

.btn-remove-small:hover {
  background: #dc2626;
}

.btn-add-small {
  padding: 0.5rem 1rem;
  background: #10b981;
  color: white;
  border: none;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  font-size: 0.875rem;
  align-self: flex-start;
}

.btn-add-small:hover {
  background: #059669;
}

.btn-remove {
  padding: 0.75rem 1rem;
  background: #ef4444;
  color: white;
  border: none;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s ease;
  white-space: nowrap;
}

.btn-remove:hover {
  background: #dc2626;
}

.btn-add {
  padding: 0.75rem 1rem;
  background: #10b981;
  color: white;
  border: none;
  border-radius: 6px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.2s ease;
  align-self: flex-start;
}

.btn-add:hover {
  background: #059669;
}
</style>
