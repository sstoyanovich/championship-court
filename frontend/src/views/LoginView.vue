<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useUserStore } from "../stores/user";

const router = useRouter();
const userStore = useUserStore();

const email = ref("");
const password = ref("");
const error = ref("");
const loading = ref(false);

const handleLogin = async () => {
  error.value = "";
  loading.value = true;

  const result = await userStore.login(email.value, password.value);

  if (result.success) {
    router.push("/");
  } else {
    error.value = result.message || "Login failed. Please check your credentials.";
  }

  loading.value = false;
};
</script>

<template>
  <div
    class="min-h-screen flex items-center justify-center p-8 bg-gradient-to-br from-[#1a1a2e] to-[#16213e]"
  >
    <div
      class="bg-slate-800/80 border-2 border-slate-700 rounded-3xl p-12 max-w-[450px] w-full shadow-[0_20px_40px_rgba(0,0,0,0.5)] max-sm:p-8 max-sm:px-6"
    >
      <h1
        class="text-[2.5rem] font-bold text-center mb-2 bg-gradient-to-r from-blue-400 to-purple-400 bg-clip-text text-transparent max-sm:text-[2rem]"
      >
        🏀 Championship Court
      </h1>
      <h2 class="text-2xl text-center text-slate-400 mb-8 max-sm:text-xl">Login to Your Account</h2>

      <form @submit.prevent="handleLogin" class="flex flex-col gap-6">
        <div class="flex flex-col gap-2">
          <label for="email" class="text-slate-200 font-semibold text-[0.95rem]">Email</label>
          <input
            id="email"
            v-model="email"
            type="email"
            placeholder="Enter your email"
            required
            :disabled="loading"
            class="py-3.5 px-4 bg-slate-950/60 border-2 border-slate-700 rounded-xl text-white text-base transition-all duration-300 focus:outline-none focus:border-blue-400 focus:bg-slate-950/80 disabled:opacity-60 disabled:cursor-not-allowed"
          />
        </div>

        <div class="flex flex-col gap-2">
          <label for="password" class="text-slate-200 font-semibold text-[0.95rem]">Password</label>
          <input
            id="password"
            v-model="password"
            type="password"
            placeholder="Enter your password"
            required
            :disabled="loading"
            class="py-3.5 px-4 bg-slate-950/60 border-2 border-slate-700 rounded-xl text-white text-base transition-all duration-300 focus:outline-none focus:border-blue-400 focus:bg-slate-950/80 disabled:opacity-60 disabled:cursor-not-allowed"
          />
        </div>

        <div
          v-if="error"
          class="p-4 bg-red-500/20 border border-red-500/50 rounded-xl text-red-300 text-[0.95rem]"
        >
          {{ error }}
        </div>

        <button
          type="submit"
          :disabled="loading"
          class="p-4 bg-gradient-to-br from-blue-500 to-purple-600 border-none rounded-xl text-white text-lg font-bold cursor-pointer transition-all duration-300 mt-2 hover:not(:disabled):-translate-y-0.5 hover:not(:disabled):shadow-[0_8px_16px_rgba(59,130,246,0.4)] disabled:opacity-60 disabled:cursor-not-allowed"
        >
          {{ loading ? "Logging in..." : "Login" }}
        </button>

        <div class="text-center text-slate-400 text-[0.95rem] mt-2">
          Don't have an account?
          <router-link
            to="/register"
            class="text-blue-400 no-underline font-semibold transition-colors duration-300 hover:text-blue-300"
            >Register here</router-link
          >
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
/* No additional styles needed - all converted to Tailwind */
</style>
