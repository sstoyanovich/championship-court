<template>
  <div class="players-index">
    <div class="header">
      <div>
        <h1>Players</h1>
        <p class="subtitle">Manage all players in the system</p>
      </div>
      <router-link to="/admin/players/create" class="btn-primary"> + Add Player </router-link>
    </div>

    <div class="filters">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Search players by name, team, or position..."
        @input="handleSearch"
        class="search-input"
      />
      <select v-model.number="collectionFilter" @change="handleFilterChange" class="filter-select">
        <option value="">All Collections</option>
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
        </option>
      </select>
    </div>

    <div v-if="selectedPlayers.length > 0" class="bulk-actions-bar">
      <div class="bulk-info">
        <span class="selected-count">{{ selectedPlayers.length }} player(s) selected</span>
        <button @click="clearSelection" class="btn-clear">Clear Selection</button>
      </div>
      <div class="bulk-buttons">
        <button @click="bulkSetObtainable(true)" class="btn-bulk btn-bulk-green">
          ✓ Mark Obtainable from Packs
        </button>
        <button @click="bulkSetObtainable(false)" class="btn-bulk btn-bulk-orange">
          ✗ Mark Not Obtainable from Packs
        </button>
        <button @click="bulkSetTradeable(true)" class="btn-bulk btn-bulk-blue">
          ✓ Mark Tradeable
        </button>
        <button @click="bulkSetTradeable(false)" class="btn-bulk btn-bulk-red">
          ✗ Mark Not Tradeable
        </button>
      </div>
    </div>

    <div v-if="adminStore.loading" class="loading">Loading players...</div>

    <div v-else-if="adminStore.error" class="error">
      {{ adminStore.error }}
    </div>

    <div v-else class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th class="checkbox-column">
              <input
                type="checkbox"
                @change="toggleSelectAll"
                :checked="allSelected"
                class="table-checkbox"
              />
            </th>
            <th>ID</th>
            <th>Name</th>
            <th>Team</th>
            <th>Position</th>
            <th>Overall</th>
            <th>Tier</th>
            <th>Collection</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="player in adminStore.players"
            :key="player.id"
            :class="{ selected: isSelected(player.id) }"
          >
            <td class="checkbox-column">
              <input
                type="checkbox"
                :checked="isSelected(player.id)"
                @change="togglePlayer(player.id)"
                class="table-checkbox"
              />
            </td>
            <td class="player-id">{{ player.id }}</td>
            <td class="player-name">{{ player.name }}</td>
            <td>{{ player.team }}</td>
            <td>{{ player.position }}</td>
            <td>
              <span class="badge-rating">{{ player.overall_rating }}</span>
            </td>
            <td>
              <span class="badge-tier">{{ player.card_tier }}</span>
            </td>
            <td>
              <span v-if="player.collection" class="badge-collection">
                {{ player.collection.name }}
              </span>
              <span v-else class="text-muted">—</span>
            </td>
            <td class="actions">
              <router-link :to="`/admin/players/${player.id}/edit`" class="btn-edit">
                Edit
              </router-link>
              <button @click="confirmDelete(player)" class="btn-delete">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="adminStore.players.length === 0" class="empty-state">No players found</div>
    </div>

    <div
      v-if="adminStore.playersPagination && adminStore.playersPagination.last_page > 1"
      class="pagination"
    >
      <button
        @click="changePage(currentPage - 1)"
        :disabled="currentPage === 1"
        class="pagination-btn"
      >
        ← Previous
      </button>
      <span class="pagination-info">
        Page {{ currentPage }} of {{ adminStore.playersPagination.last_page }}
      </span>
      <button
        @click="changePage(currentPage + 1)"
        :disabled="currentPage === adminStore.playersPagination.last_page"
        class="pagination-btn"
      >
        Next →
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { useAdminStore } from "@/stores/admin";
import { useRouter } from "vue-router";

const adminStore = useAdminStore();
const router = useRouter();
const searchQuery = ref("");
const collectionFilter = ref<number | string>("");
const currentPage = ref(1);
const selectedPlayers = ref<number[]>([]);
let searchTimeout: any = null;

const allSelected = computed(() => {
  return (
    adminStore.players.length > 0 && selectedPlayers.value.length === adminStore.players.length
  );
});

onMounted(async () => {
  await adminStore.fetchAllCollections();
  loadPlayers();
});

async function loadPlayers() {
  const collectionId = collectionFilter.value === "" ? null : Number(collectionFilter.value);
  await adminStore.fetchPlayers(currentPage.value, searchQuery.value, 15, collectionId);
  // Clear selection when changing pages/filters
  selectedPlayers.value = [];
}

function handleSearch() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    currentPage.value = 1;
    loadPlayers();
  }, 300);
}

function handleFilterChange() {
  currentPage.value = 1;
  loadPlayers();
}

function changePage(page: number) {
  currentPage.value = page;
  loadPlayers();
}

function isSelected(playerId: number): boolean {
  return selectedPlayers.value.includes(playerId);
}

function togglePlayer(playerId: number) {
  const index = selectedPlayers.value.indexOf(playerId);
  if (index > -1) {
    selectedPlayers.value.splice(index, 1);
  } else {
    selectedPlayers.value.push(playerId);
  }
}

function toggleSelectAll() {
  if (allSelected.value) {
    selectedPlayers.value = [];
  } else {
    selectedPlayers.value = adminStore.players.map((p: any) => p.id);
  }
}

function clearSelection() {
  selectedPlayers.value = [];
}

async function bulkSetObtainable(value: boolean) {
  if (
    !confirm(
      `Mark ${selectedPlayers.value.length} player(s) as ${
        value ? "obtainable" : "NOT obtainable"
      } from packs?`
    )
  ) {
    return;
  }

  const result = await adminStore.bulkUpdatePlayers(selectedPlayers.value, {
    obtainable_from_packs: value,
  });

  if (result.success) {
    alert(result.message);
    clearSelection();
    loadPlayers();
  } else {
    alert(`Failed: ${result.message}`);
  }
}

async function bulkSetTradeable(value: boolean) {
  if (
    !confirm(
      `Mark ${selectedPlayers.value.length} player(s) as ${value ? "tradeable" : "NOT tradeable"}?`
    )
  ) {
    return;
  }

  const result = await adminStore.bulkUpdatePlayers(selectedPlayers.value, {
    is_tradeable: value,
  });

  if (result.success) {
    alert(result.message);
    clearSelection();
    loadPlayers();
  } else {
    alert(`Failed: ${result.message}`);
  }
}

async function confirmDelete(player: any) {
  if (confirm(`Are you sure you want to delete ${player.name}?`)) {
    const result = await adminStore.deletePlayer(player.id);
    if (result.success) {
      loadPlayers();
    } else {
      alert(`Failed to delete player: ${result.message}`);
    }
  }
}
</script>

<style scoped>
.players-index {
  padding: 1rem;
}

.header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 2rem;
}

.header h1 {
  font-size: 2rem;
  font-weight: 700;
  color: #1a1d29;
  margin: 0 0 0.5rem 0;
}

.subtitle {
  color: #6b7280;
  margin: 0;
}

.btn-primary {
  padding: 0.75rem 1.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  text-decoration: none;
  border-radius: 8px;
  font-weight: 600;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
  box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.filters {
  display: flex;
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.search-input {
  flex: 1;
  max-width: 500px;
  padding: 0.75rem 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-size: 1rem;
  transition: border-color 0.2s ease;
}

.search-input:focus {
  outline: none;
  border-color: #3b82f6;
}

.filter-select {
  padding: 0.75rem 1rem;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  font-size: 1rem;
  background: white;
  cursor: pointer;
  transition: border-color 0.2s ease;
  min-width: 200px;
}

.filter-select:focus {
  outline: none;
  border-color: #3b82f6;
}

.bulk-actions-bar {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 1rem;
  border-radius: 8px;
  margin-bottom: 1.5rem;
  box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
}

.bulk-info {
  display: flex;
  align-items: center;
  gap: 1rem;
  margin-bottom: 0.75rem;
}

.selected-count {
  color: white;
  font-weight: 600;
  font-size: 0.875rem;
}

.btn-clear {
  padding: 0.375rem 0.75rem;
  background: rgba(255, 255, 255, 0.2);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.3);
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-clear:hover {
  background: rgba(255, 255, 255, 0.3);
}

.bulk-buttons {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.btn-bulk {
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 6px;
  font-size: 0.875rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s ease;
  color: white;
}

.btn-bulk-green {
  background: #10b981;
}

.btn-bulk-green:hover {
  background: #059669;
}

.btn-bulk-orange {
  background: #f59e0b;
}

.btn-bulk-orange:hover {
  background: #d97706;
}

.btn-bulk-blue {
  background: #3b82f6;
}

.btn-bulk-blue:hover {
  background: #2563eb;
}

.btn-bulk-red {
  background: #ef4444;
}

.btn-bulk-red:hover {
  background: #dc2626;
}

.loading,
.error {
  padding: 2rem;
  text-align: center;
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.error {
  color: #dc2626;
}

.table-container {
  background: white;
  border-radius: 8px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table thead {
  background: #f9fafb;
}

.data-table th {
  padding: 1rem;
  text-align: left;
  font-weight: 600;
  color: #374151;
  font-size: 0.875rem;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.data-table tbody tr {
  border-top: 1px solid #e5e7eb;
  transition: background-color 0.2s ease;
}

.data-table tbody tr:hover {
  background-color: #f9fafb;
}

.data-table tbody tr.selected {
  background-color: #ede9fe;
}

.data-table tbody tr.selected:hover {
  background-color: #ddd6fe;
}

.data-table td {
  padding: 1rem;
  color: #1f2937;
}

.checkbox-column {
  width: 50px;
  text-align: center;
}

.table-checkbox {
  width: 18px;
  height: 18px;
  cursor: pointer;
  accent-color: #667eea;
}

.player-id {
  color: #6b7280;
  font-family: monospace;
  font-size: 0.875rem;
}

.player-name {
  font-weight: 600;
}

.badge-rating {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  background: #dbeafe;
  color: #1e40af;
  border-radius: 999px;
  font-weight: 600;
  font-size: 0.875rem;
}

.badge-tier {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  background: #fef3c7;
  color: #92400e;
  border-radius: 999px;
  font-weight: 600;
  font-size: 0.875rem;
}

.badge-collection {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  background: #dcfce7;
  color: #166534;
  border-radius: 999px;
  font-weight: 600;
  font-size: 0.875rem;
}

.text-muted {
  color: #9ca3af;
  font-style: italic;
}

.actions {
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
  text-decoration: none;
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

.empty-state {
  padding: 3rem;
  text-align: center;
  color: #6b7280;
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 1rem;
  margin-top: 1.5rem;
}

.pagination-btn {
  padding: 0.5rem 1rem;
  background: white;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s ease;
}

.pagination-btn:hover:not(:disabled) {
  background: #f9fafb;
  border-color: #3b82f6;
}

.pagination-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.pagination-info {
  color: #6b7280;
  font-weight: 500;
}
</style>
