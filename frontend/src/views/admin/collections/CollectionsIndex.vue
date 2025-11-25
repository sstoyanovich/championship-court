<template>
  <div class="collections-index">
    <div class="header">
      <div>
        <h1>Collections</h1>
        <p class="subtitle">Manage all collections in the system</p>
      </div>
      <router-link to="/admin/collections/create" class="btn-primary">
        + Add Collection
      </router-link>
    </div>

    <div class="search-bar">
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Search collections by name, type, or sub-collection..."
        @input="handleSearch"
        class="search-input"
      />
    </div>

    <div v-if="adminStore.loading" class="loading">Loading collections...</div>

    <div v-else-if="adminStore.error" class="error">
      {{ adminStore.error }}
    </div>

    <div v-else class="table-container">
      <table class="data-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Type</th>
            <th>Sub-Collection</th>
            <th>Total Items</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="collection in adminStore.collections" :key="collection.id">
            <td class="collection-name">{{ collection.name }}</td>
            <td>
              <span class="badge-type">{{ collection.type }}</span>
            </td>
            <td>{{ collection.sub_collection || "N/A" }}</td>
            <td>{{ collection.total_items }}</td>
            <td>
              <span :class="['badge-status', collection.active ? 'active' : 'inactive']">
                {{ collection.active ? "Active" : "Inactive" }}
              </span>
            </td>
            <td class="actions">
              <router-link :to="`/admin/collections/${collection.id}/edit`" class="btn-edit">
                Edit
              </router-link>
              <button @click="confirmDelete(collection)" class="btn-delete">Delete</button>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-if="adminStore.collections.length === 0" class="empty-state">No collections found</div>
    </div>

    <div
      v-if="adminStore.collectionsPagination && adminStore.collectionsPagination.last_page > 1"
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
        Page {{ currentPage }} of {{ adminStore.collectionsPagination.last_page }}
      </span>
      <button
        @click="changePage(currentPage + 1)"
        :disabled="currentPage === adminStore.collectionsPagination.last_page"
        class="pagination-btn"
      >
        Next →
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useAdminStore } from "@/stores/admin";

const adminStore = useAdminStore();
const searchQuery = ref("");
const currentPage = ref(1);
let searchTimeout: any = null;

onMounted(() => {
  loadCollections();
});

async function loadCollections() {
  await adminStore.fetchCollections(currentPage.value, searchQuery.value);
}

function handleSearch() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    currentPage.value = 1;
    loadCollections();
  }, 300);
}

function changePage(page: number) {
  currentPage.value = page;
  loadCollections();
}

async function confirmDelete(collection: any) {
  if (confirm(`Are you sure you want to delete ${collection.name}?`)) {
    const result = await adminStore.deleteCollection(collection.id);
    if (result.success) {
      loadCollections();
    } else {
      alert(`Failed to delete collection: ${result.message}`);
    }
  }
}
</script>

<style scoped>
.collections-index {
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

.search-bar {
  margin-bottom: 1.5rem;
}

.search-input {
  width: 100%;
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

.data-table td {
  padding: 1rem;
  color: #1f2937;
}

.collection-name {
  font-weight: 600;
}

.badge-type {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  background: #dbeafe;
  color: #1e40af;
  border-radius: 999px;
  font-weight: 600;
  font-size: 0.875rem;
  text-transform: uppercase;
}

.badge-status {
  display: inline-block;
  padding: 0.25rem 0.75rem;
  border-radius: 999px;
  font-weight: 600;
  font-size: 0.875rem;
}

.badge-status.active {
  background: #d1fae5;
  color: #065f46;
}

.badge-status.inactive {
  background: #fee2e2;
  color: #991b1b;
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
