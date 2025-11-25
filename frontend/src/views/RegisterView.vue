<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useUserStore } from "../stores/user";

const router = useRouter();
const userStore = useUserStore();

const name = ref("");
const email = ref("");
const password = ref("");
const password_confirmation = ref("");
const error = ref("");
const loading = ref(false);

const handleRegister = async () => {
  error.value = "";

  if (password.value !== password_confirmation.value) {
    error.value = "Passwords do not match";
    return;
  }

  if (password.value.length < 8) {
    error.value = "Password must be at least 8 characters";
    return;
  }

  loading.value = true;

  const result = await userStore.register(
    name.value,
    email.value,
    password.value,
    password_confirmation.value
  );

  if (result.success) {
    router.push("/");
  } else {
    error.value = result.message || "Registration failed. Please try again.";
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
      <h2 class="text-2xl text-center text-slate-400 mb-8 max-sm:text-xl">Create Your Account</h2>

      <form @submit.prevent="handleRegister" class="flex flex-col gap-6">
        <div class="flex flex-col gap-2">
          <label for="name" class="text-slate-200 font-semibold text-[0.95rem]">Name</label>
          <input
            id="name"
            v-model="name"
            type="text"
            placeholder="Enter your name"
            required
            :disabled="loading"
            class="py-3.5 px-4 bg-slate-950/60 border-2 border-slate-700 rounded-xl text-white text-base transition-all duration-300 focus:outline-none focus:border-blue-400 focus:bg-slate-950/80 disabled:opacity-60 disabled:cursor-not-allowed"
          />
        </div>

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
            placeholder="At least 8 characters"
            required
            :disabled="loading"
            class="py-3.5 px-4 bg-slate-950/60 border-2 border-slate-700 rounded-xl text-white text-base transition-all duration-300 focus:outline-none focus:border-blue-400 focus:bg-slate-950/80 disabled:opacity-60 disabled:cursor-not-allowed"
          />
        </div>

        <div class="flex flex-col gap-2">
          <label for="password_confirmation" class="text-slate-200 font-semibold text-[0.95rem]"
            >Confirm Password</label
          >
          <input
            id="password_confirmation"
            v-model="password_confirmation"
            type="password"
            placeholder="Confirm your password"
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
          class="p-4 bg-gradient-to-br from-emerald-500 to-emerald-600 border-none rounded-xl text-white text-lg font-bold cursor-pointer transition-all duration-300 mt-2 hover:not(:disabled):-translate-y-0.5 hover:not(:disabled):shadow-[0_8px_16px_rgba(16,185,129,0.4)] disabled:opacity-60 disabled:cursor-not-allowed"
        >
          {{ loading ? "Creating Account..." : "Register" }}
        </button>

        <div class="text-center text-slate-400 text-[0.95rem] mt-2">
          Already have an account?
          <router-link
            to="/login"
            class="text-blue-400 no-underline font-semibold transition-colors duration-300 hover:text-blue-300"
            >Login here</router-link
          >
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
/* No additional styles needed - all converted to Tailwind */
</style>
