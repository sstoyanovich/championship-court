<template>
  <div class="player-form">
    <div class="header">
      <h1>{{ isEditMode ? "Edit Player" : "Create Player" }}</h1>
      <router-link to="/admin/players" class="btn-back">← Back to Players</router-link>
    </div>

    <div v-if="adminStore.loading" class="loading">Loading...</div>

    <form v-else @submit.prevent="handleSubmit" class="form">
      <!-- Basic Information -->
      <div class="form-section">
        <h2>Basic Information</h2>
        <div class="form-grid">
          <div class="form-field">
            <label>Name *</label>
            <input v-model="formData.name" type="text" required />
          </div>
          <div class="form-field">
            <label>NBA ID</label>
            <input v-model="formData.nba_id" type="text" />
          </div>
          <div class="form-field">
            <label>Team *</label>
            <select v-model="formData.team" required>
              <option value="">Select Team</option>
              <option v-for="team in NBA_TEAMS" :key="team" :value="team">
                {{ team }}
              </option>
            </select>
          </div>
          <div class="form-field">
            <label>Position *</label>
            <input
              v-model="formData.position"
              type="text"
              required
              placeholder="e.g., PG, SG, SF"
            />
          </div>
          <div class="form-field">
            <label>Overall Rating *</label>
            <input
              v-model.number="formData.overall_rating"
              type="number"
              min="0"
              max="99"
              required
            />
          </div>
          <div class="form-field">
            <label>Card Tier *</label>
            <select v-model="formData.card_tier" required>
              <option value="">Select Tier</option>
              <option value="common">Common</option>
              <option value="bronze">Bronze</option>
              <option value="silver">Silver</option>
              <option value="gold">Gold</option>
              <option value="emerald">Emerald</option>
              <option value="sapphire">Sapphire</option>
              <option value="ruby">Ruby</option>
              <option value="amethyst">Amethyst</option>
              <option value="diamond">Diamond</option>
              <option value="pink_diamond">Pink Diamond</option>
              <option value="galaxy_opal">Galaxy Opal</option>
            </select>
          </div>
          <div class="form-field full-width">
            <label>Image URL</label>
            <input v-model="formData.image_url" type="text" placeholder="https://..." />
          </div>
          <div class="form-field full-width">
            <label>Custom Card Art</label>
            <select v-model="formData.card_art">
              <option :value="null">No Custom Art (Use Default)</option>
              <option
                v-for="image in adminStore.cardArtImages"
                :key="image.path"
                :value="image.path"
              >
                {{ image.filename }} ({{ image.path }})
              </option>
            </select>
            <span class="field-hint">Select custom card art from /public/images/card_art</span>
          </div>
          <div class="form-field">
            <label>Collection</label>
            <select v-model.number="formData.collection_id">
              <option :value="null">No Collection</option>
              <option
                v-for="collection in adminStore.allCollections"
                :key="collection.id"
                :value="collection.id"
              >
                {{
                  collection.type === "team" && collection.sub_collection
                    ? collection.sub_collection
                    : collection.name
                }}
                ({{ collection.type }})
              </option>
            </select>
            <span class="field-hint">Assign player to a collection</span>
          </div>
          <div class="form-field full-width">
            <label class="checkbox-label">
              <input v-model="formData.obtainable_from_packs" type="checkbox" />
              <span>Obtainable from Packs</span>
            </label>
            <span class="field-hint"
              >Uncheck if this card is program-exclusive (can only be earned as rewards)</span
            >
          </div>
          <div class="form-field full-width">
            <label class="checkbox-label">
              <input v-model="formData.is_tradeable" type="checkbox" />
              <span>Tradeable (Can be Bought/Sold)</span>
            </label>
            <span class="field-hint"
              >Uncheck if this card is a reward card that cannot be bought or sold in the market</span
            >
          </div>
        </div>
      </div>

      <!-- Category Ratings -->
      <div class="form-section">
        <h2>Category Ratings</h2>
        <div class="form-grid">
          <div class="form-field">
            <label>Outside Scoring</label>
            <input v-model.number="formData.outside_scoring" type="number" min="0" max="99" />
          </div>
          <div class="form-field">
            <label>Inside Scoring</label>
            <input v-model.number="formData.inside_scoring" type="number" min="0" max="99" />
          </div>
          <div class="form-field">
            <label>Defense</label>
            <input v-model.number="formData.defense" type="number" min="0" max="99" />
          </div>
          <div class="form-field">
            <label>Athleticism</label>
            <input v-model.number="formData.athleticism" type="number" min="0" max="99" />
          </div>
          <div class="form-field">
            <label>Playmaking</label>
            <input v-model.number="formData.playmaking" type="number" min="0" max="99" />
          </div>
          <div class="form-field">
            <label>Rebounding</label>
            <input v-model.number="formData.rebounding" type="number" min="0" max="99" />
          </div>
        </div>
      </div>

      <!-- Outside Scoring Attributes -->
      <div class="form-section">
        <h2>Outside Scoring Attributes</h2>
        <div class="form-grid">
          <div class="form-field">
            <label>Close Shot</label>
            <input v-model.number="formData.close_shot" type="number" min="0" max="99" />
          </div>
          <div class="form-field">
            <label>Mid Range Shot</label>
            <input v-model.number="formData.mid_range_shot" type="number" min="0" max="99" />
          </div>
          <div class="form-field">
            <label>Three Point Shot</label>
            <input v-model.number="formData.three_point_shot" type="number" min="0" max="99" />
          </div>
          <div class="form-field">
            <label>Free Throw</label>
            <input v-model.number="formData.free_throw" type="number" min="0" max="99" />
          </div>
          <div class="form-field">
            <label>Shot IQ</label>
            <input v-model.number="formData.shot_iq" type="number" min="0" max="99" />
          </div>
          <div class="form-field">
            <label>Offensive Consistency</label>
            <input v-model.number="formData.offensive_consistency" type="number" min="0" max="99" />
          </div>
        </div>
      </div>

      <!-- Inside Scoring Attributes -->
      <div class="form-section">
        <h2>Inside Scoring Attributes</h2>
        <div class="form-grid">
          <div class="form-field">
            <label>Layup</label>
            <input v-model.number="formData.layup" type="number" min="0" max="99" />
          </div>
          <div class="form-field">
            <label>Standing Dunk</label>
            <input v-model.number="formData.standing_dunk" type="number" min="0" max="99" />
          </div>
          <div class="form-field">
            <label>Driving Dunk</label>
            <input v-model.number="formData.driving_dunk" type="number" min="0" max="99" />
          </div>
          <div class="form-field">
            <label>Post Hook</label>
            <input v-model.number="formData.post_hook" type="number" min="0" max="99" />
          </div>
          <div class="form-field">
            <label>Post Fade</label>
            <input v-model.number="formData.post_fade" type="number" min="0" max="99" />
          </div>
          <div class="form-field">
            <label>Post Control</label>
            <input v-model.number="formData.post_control" type="number" min="0" max="99" />
          </div>
          <div class="form-field">
            <label>Draw Foul</label>
            <input v-model.number="formData.draw_foul" type="number" min="0" max="99" />
          </div>
          <div class="form-field">
            <label>Hands</label>
            <input v-model.number="formData.hands" type="number" min="0" max="99" />
          </div>
        </div>
      </div>

      <!-- Defense Attributes -->
      <div class="form-section">
        <h2>Defense Attributes</h2>
        <div class="form-grid">
          <div class="form-field">
            <label>Interior Defense</label>
            <input v-model.number="formData.interior_defense" type="number" min="0" max="99" />
          </div>
          <div class="form-field">
            <label>Perimeter Defense</label>
            <input v-model.number="formData.perimeter_defense" type="number" min="0" max="99" />
          </div>
          <div class="form-field">
            <label>Steal</label>
            <input v-model.number="formData.steal" type="number" min="0" max="99" />
          </div>
          <div class="form-field">
            <label>Block</label>
            <input v-model.number="formData.block" type="number" min="0" max="99" />
          </div>
          <div class="form-field">
            <label>Help Defense IQ</label>
            <input v-model.number="formData.help_defense_iq" type="number" min="0" max="99" />
          </div>
          <div class="form-field">
            <label>Pass Perception</label>
            <input v-model.number="formData.pass_perception" type="number" min="0" max="99" />
          </div>
          <div class="form-field">
            <label>Defensive Consistency</label>
            <input v-model.number="formData.defensive_consistency" type="number" min="0" max="99" />
          </div>
        </div>
      </div>

      <!-- Athleticism Attributes -->
      <div class="form-section">
        <h2>Athleticism Attributes</h2>
        <div class="form-grid">
          <div class="form-field">
            <label>Speed</label>
            <input v-model.number="formData.speed" type="number" min="0" max="99" />
          </div>
          <div class="form-field">
            <label>Agility</label>
            <input v-model.number="formData.agility" type="number" min="0" max="99" />
          </div>
          <div class="form-field">
            <label>Strength</label>
            <input v-model.number="formData.strength" type="number" min="0" max="99" />
          </div>
          <div class="form-field">
            <label>Vertical</label>
            <input v-model.number="formData.vertical" type="number" min="0" max="99" />
          </div>
          <div class="form-field">
            <label>Stamina</label>
            <input v-model.number="formData.stamina" type="number" min="0" max="99" />
          </div>
          <div class="form-field">
            <label>Hustle</label>
            <input v-model.number="formData.hustle" type="number" min="0" max="99" />
          </div>
          <div class="form-field">
            <label>Overall Durability</label>
            <input v-model.number="formData.overall_durability" type="number" min="0" max="99" />
          </div>
        </div>
      </div>

      <!-- Playmaking Attributes -->
      <div class="form-section">
        <h2>Playmaking Attributes</h2>
        <div class="form-grid">
          <div class="form-field">
            <label>Pass Accuracy</label>
            <input v-model.number="formData.pass_accuracy" type="number" min="0" max="99" />
          </div>
          <div class="form-field">
            <label>Ball Handle</label>
            <input v-model.number="formData.ball_handle" type="number" min="0" max="99" />
          </div>
          <div class="form-field">
            <label>Speed With Ball</label>
            <input v-model.number="formData.speed_with_ball" type="number" min="0" max="99" />
          </div>
          <div class="form-field">
            <label>Pass IQ</label>
            <input v-model.number="formData.pass_iq" type="number" min="0" max="99" />
          </div>
          <div class="form-field">
            <label>Pass Vision</label>
            <input v-model.number="formData.pass_vision" type="number" min="0" max="99" />
          </div>
        </div>
      </div>

      <!-- Rebounding Attributes -->
      <div class="form-section">
        <h2>Rebounding Attributes</h2>
        <div class="form-grid">
          <div class="form-field">
            <label>Offensive Rebound</label>
            <input v-model.number="formData.offensive_rebound" type="number" min="0" max="99" />
          </div>
          <div class="form-field">
            <label>Defensive Rebound</label>
            <input v-model.number="formData.defensive_rebound" type="number" min="0" max="99" />
          </div>
        </div>
      </div>

      <!-- Other Attributes -->
      <div class="form-section">
        <h2>Other Attributes</h2>
        <div class="form-grid">
          <div class="form-field">
            <label>Intangibles</label>
            <input v-model.number="formData.intangibles" type="number" min="0" max="99" />
          </div>
          <div class="form-field">
            <label>Potential</label>
            <input v-model.number="formData.potential" type="number" min="0" max="99" />
          </div>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn-submit" :disabled="adminStore.loading">
          {{ isEditMode ? "Update Player" : "Create Player" }}
        </button>
        <router-link to="/admin/players" class="btn-cancel">Cancel</router-link>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAdminStore } from "@/stores/admin";
import { NBA_TEAMS } from "@/constants/nbaTeams";

const route = useRoute();
const router = useRouter();
const adminStore = useAdminStore();

const isEditMode = ref(false);
const formData = ref<any>({
  name: "",
  nba_id: "",
  team: "",
  position: "",
  overall_rating: 75,
  card_tier: "",
  image_url: "",
  card_art: null,
  collection_id: null,
  obtainable_from_packs: true,
  is_tradeable: true,
  outside_scoring: 80,
  inside_scoring: 80,
  defense: 80,
  athleticism: 80,
  playmaking: 80,
  rebounding: 80,
  close_shot: 80,
  mid_range_shot: 80,
  three_point_shot: 80,
  free_throw: 80,
  shot_iq: 80,
  offensive_consistency: 80,
  layup: 80,
  standing_dunk: 80,
  driving_dunk: 80,
  post_hook: 80,
  post_fade: 80,
  post_control: 80,
  draw_foul: 80,
  hands: 80,
  interior_defense: 80,
  perimeter_defense: 80,
  steal: 80,
  block: 80,
  help_defense_iq: 80,
  pass_perception: 80,
  defensive_consistency: 80,
  speed: 80,
  agility: 80,
  strength: 80,
  vertical: 80,
  stamina: 80,
  hustle: 80,
  overall_durability: 80,
  pass_accuracy: 80,
  ball_handle: 80,
  speed_with_ball: 80,
  pass_iq: 80,
  pass_vision: 80,
  offensive_rebound: 80,
  defensive_rebound: 80,
  intangibles: 80,
  potential: 80,
});

onMounted(async () => {
  // Load all collections and card art images for dropdowns
  await adminStore.fetchAllCollections();
  await adminStore.fetchCardArtImages();

  const playerId = route.params.id;
  if (playerId) {
    isEditMode.value = true;
    const result = await adminStore.getPlayer(Number(playerId));
    if (result.success) {
      formData.value = { ...result.data };
    }
  }
});

async function handleSubmit() {
  let result;
  if (isEditMode.value) {
    result = await adminStore.updatePlayer(formData.value.id, formData.value);
  } else {
    result = await adminStore.createPlayer(formData.value);
  }

  if (result.success) {
    router.push("/admin/players");
  } else {
    alert(`Failed: ${result.message}`);
  }
}
</script>

<style scoped>
.player-form {
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

.form-field input,
.form-field select {
  padding: 0.75rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  font-size: 1rem;
  transition: border-color 0.2s ease;
}

.form-field input:focus,
.form-field select:focus {
  outline: none;
  border-color: #3b82f6;
}

.field-hint {
  margin-top: 0.25rem;
  font-size: 0.75rem;
  color: #6b7280;
  font-style: italic;
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
