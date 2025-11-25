<template>
  <div class="collection-form">
    <div class="header">
      <h1>{{ isEditMode ? "Edit Collection" : "Create Collection" }}</h1>
      <router-link to="/admin/collections" class="btn-back">← Back to Collections</router-link>
    </div>

    <div v-if="adminStore.loading" class="loading">Loading...</div>

    <form v-else @submit.prevent="handleSubmit" class="form">
      <div class="form-section">
        <h2>Collection Information</h2>
        <div class="form-grid">
          <div class="form-field full-width">
            <label>Name *</label>
            <input v-model="formData.name" type="text" required />
            <span class="field-hint">e.g., "Live Series", "Legends", "Season 1"</span>
          </div>
          <div class="form-field">
            <label>Type *</label>
            <select v-model="formData.type" required>
              <option value="">Select Type</option>
              <option value="team">Team</option>
              <option value="season">Season</option>
              <option value="special">Special</option>
              <option value="legends">Legends</option>
              <option value="rewards">Rewards</option>
            </select>
            <span class="field-hint">Category of the collection</span>
          </div>
          <div class="form-field">
            <label>Sub-Collection</label>
            <input v-model="formData.sub_collection" type="text" />
            <span class="field-hint">e.g., "Lakers", "Warriors" for team collections</span>
          </div>
          <div class="form-field">
            <label>Total Items</label>
            <input v-model.number="formData.total_items" type="number" min="0" />
            <span class="field-hint">Total cards in this collection</span>
          </div>
          <div class="form-field">
            <label>Active</label>
            <div class="checkbox-field">
              <input v-model="formData.active" type="checkbox" id="active" />
              <label for="active">Collection is active</label>
            </div>
          </div>
          <div class="form-field full-width">
            <label>Description</label>
            <textarea v-model="formData.description" rows="4"></textarea>
            <span class="field-hint">Brief description of the collection</span>
          </div>
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn-submit" :disabled="adminStore.loading">
          {{ isEditMode ? "Update Collection" : "Create Collection" }}
        </button>
        <router-link to="/admin/collections" class="btn-cancel">Cancel</router-link>
      </div>
    </form>

    <!-- Rewards Manager (only shown in edit mode) -->
    <RewardManager v-if="isEditMode && formData.id" :collection-id="formData.id" />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAdminStore } from "@/stores/admin";
import RewardManager from "@/components/admin/RewardManager.vue";

const route = useRoute();
const router = useRouter();
const adminStore = useAdminStore();

const isEditMode = ref(false);
const formData = ref({
  id: undefined as number | undefined,
  name: "",
  type: "",
  sub_collection: "",
  description: "",
  total_items: 0,
  active: true,
});

onMounted(async () => {
  const collectionId = route.params.id;
  if (collectionId) {
    isEditMode.value = true;
    const result = await adminStore.getCollection(Number(collectionId));
    if (result.success) {
      formData.value = { ...result.data };
    }
  }
});

async function handleSubmit() {
  let result;
  if (isEditMode.value) {
    result = await adminStore.updateCollection((formData.value as any).id, formData.value);
  } else {
    result = await adminStore.createCollection(formData.value);
  }

  if (result.success) {
    router.push("/admin/collections");
  } else {
    alert(`Failed: ${result.message}`);
  }
}
</script>

<style scoped>
.collection-form {
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
  border-top: 1px solid #e5e7eb;
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
