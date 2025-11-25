<script setup>
import { RouterLink, RouterView, useRouter } from "vue-router";
import { useUserStore } from "./stores/user";

const router = useRouter();
const userStore = useUserStore();

const handleLogout = async () => {
  await userStore.logout();
  router.push("/login");
};
</script>

<template>
  <div class="min-h-screen flex flex-col">
    <nav v-if="userStore.isAuthenticated" class="bg-white shadow-md py-4 sticky top-0 z-[100]">
      <div class="w-full px-8 flex justify-between items-center gap-8 max-lg:flex-wrap">
        <RouterLink
          to="/"
          class="text-2xl font-extrabold bg-brand-gradient bg-clip-text text-transparent no-underline"
        >
          Championship Court
        </RouterLink>
        <div
          class="flex gap-8 flex-1 justify-center max-lg:order-3 max-lg:w-full max-lg:justify-start max-lg:pt-4 max-lg:border-t max-lg:border-gray-200 max-sm:flex-wrap max-sm:gap-4"
        >
          <RouterLink to="/" class="nav-link">Home</RouterLink>
          <RouterLink to="/programs" class="nav-link">Programs</RouterLink>
          <RouterLink to="/packs" class="nav-link">Pack Store</RouterLink>
          <RouterLink to="/lineups" class="nav-link">Lineups</RouterLink>
          <RouterLink to="/collection" class="nav-link">Collection</RouterLink>
          <RouterLink to="/stats/upload" class="nav-link">Upload Stats</RouterLink>
          <RouterLink
            v-if="userStore.isAdmin"
            to="/admin"
            class="nav-link text-brand-primary font-bold"
            >⚙️ Admin</RouterLink
          >
        </div>
        <div class="flex items-center gap-4">
          <div class="flex flex-col items-end gap-1">
            <span class="font-bold text-gray-800 text-[0.95rem]">{{ userStore.user?.name }}</span>
            <span class="font-semibold text-brand-primary text-[0.9rem]"
              >💰 {{ userStore.user?.stubs_balance?.toLocaleString() }} Stubs</span
            >
          </div>
          <button
            @click="handleLogout"
            class="px-4 py-2 bg-brand-gradient text-white border-none rounded-lg font-semibold cursor-pointer transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_4px_8px_rgba(102,126,234,0.3)]"
          >
            Logout
          </button>
        </div>
      </div>
    </nav>

    <main class="flex-1">
      <RouterView />
    </main>
  </div>
</template>

<style scoped>
.nav-link {
  @apply no-underline text-gray-600 font-semibold transition-colors duration-300 relative max-sm:text-[0.9rem];
}

.nav-link:hover {
  @apply text-brand-primary;
}

.nav-link.router-link-active {
  @apply text-brand-primary;
}

.nav-link.router-link-active::after {
  content: "";
  @apply absolute -bottom-2 left-0 right-0 h-[3px] bg-brand-gradient rounded-sm;
}
</style>
