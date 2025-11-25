<template>
  <div class="flex min-h-screen bg-gray-50">
    <!-- Sidebar -->
    <aside
      class="w-[260px] bg-gradient-to-b from-[#1a1d29] to-[#252936] text-white fixed h-screen overflow-y-auto shadow-[2px_0_8px_rgba(0,0,0,0.1)] md:w-[200px]"
    >
      <div class="py-8 px-6 border-b border-white/10">
        <h2 class="m-0 text-xl font-semibold text-white md:text-base">⚙️ Admin Panel</h2>
      </div>
      <nav class="py-4">
        <router-link to="/admin" class="admin-nav-item" exact-active-class="active">
          <span class="mr-3 text-lg">📊</span>
          Dashboard
        </router-link>
        <router-link to="/admin/players" class="admin-nav-item" active-class="active">
          <span class="mr-3 text-lg">👤</span>
          Players
        </router-link>
        <router-link to="/admin/programs" class="admin-nav-item" active-class="active">
          <span class="mr-3 text-lg">🏆</span>
          Programs
        </router-link>
        <router-link to="/admin/packs" class="admin-nav-item" active-class="active">
          <span class="mr-3 text-lg">📦</span>
          Packs
        </router-link>
        <router-link to="/admin/collections" class="admin-nav-item" active-class="active">
          <span class="mr-3 text-lg">📚</span>
          Collections
        </router-link>
        <div class="h-px bg-white/10 my-4 mx-6"></div>
        <router-link to="/" class="admin-nav-item">
          <span class="mr-3 text-lg">←</span>
          Back to App
        </router-link>
      </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 ml-[260px] p-8 md:ml-[200px] md:p-4">
      <div class="max-w-[1400px] mx-auto">
        <router-view />
      </div>
    </main>
  </div>
</template>

<script setup lang="ts"></script>

<style scoped>
.admin-nav-item {
  @apply flex items-center py-3 px-6 text-white/70 no-underline transition-all duration-200 text-[0.95rem] hover:bg-white/5 hover:text-white md:py-2.5 md:px-4 md:text-sm;
}

.admin-nav-item.active {
  @apply bg-blue-500/10 text-blue-400 border-l-[3px] border-blue-500;
}
</style>
