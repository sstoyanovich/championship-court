<template>
  <div class="max-w-[1200px] mx-auto p-6 md:p-4">
    <div
      class="inline-flex items-center gap-2 py-2 px-4 bg-gray-100 rounded-lg cursor-pointer mb-6 font-semibold text-gray-600 transition-all duration-300 hover:bg-gray-200 hover:text-gray-800"
      @click="goBack"
    >
      ← Back to Programs
    </div>

    <div v-if="loading" class="text-center py-12 px-6">
      <p>Loading program details...</p>
    </div>

    <div v-else-if="error" class="text-center py-12 px-6">
      <p>{{ error }}</p>
      <button
        @click="loadProgramDetails"
        class="mt-4 py-3 px-6 bg-blue-700 text-white border-none rounded-lg cursor-pointer text-base font-semibold"
      >
        Retry
      </button>
    </div>

    <div v-else-if="program">
      <!-- Program Header -->
      <div
        class="flex justify-between items-start mb-8 p-6 bg-white rounded-xl shadow-[0_2px_8px_rgba(0,0,0,0.1)] md:flex-col md:gap-4"
      >
        <div class="flex-1">
          <h1 class="text-[2.5rem] font-bold text-gray-800 my-0 mb-2 md:text-[2rem]">
            {{ program.name }}
          </h1>
          <p class="text-gray-600 my-0 mb-3 text-lg leading-relaxed">{{ program.description }}</p>
          <span
            class="inline-block py-1.5 px-4 rounded-full text-sm font-semibold uppercase"
            :class="
              program.type === 'xp' ? 'bg-blue-50 text-blue-700' : 'bg-orange-50 text-orange-600'
            "
          >
            {{ program.type === "xp" ? "XP Program" : "Star Program" }}
          </span>
        </div>
        <StubsDisplay :stubsBalance="userStubsBalance" />
      </div>

      <!-- Progress Section -->
      <div
        v-if="program.user_progress"
        class="bg-white p-6 rounded-xl shadow-[0_2px_8px_rgba(0,0,0,0.1)] mb-8"
      >
        <div class="flex justify-between items-center mb-4">
          <h2 class="m-0 text-2xl text-gray-800">Your Progress</h2>
          <div class="flex gap-4 text-lg">
            <span v-if="program.type === 'xp'" class="text-gray-600 font-semibold">
              {{ formatNumber(program.user_progress.current_xp) }} /
              {{ formatNumber(program.total_xp_required) }} XP
            </span>
            <span v-else class="text-gray-600 font-semibold">
              {{ program.user_progress.current_stars }} / {{ program.stars_required }} Stars
            </span>
            <span class="text-blue-700 font-bold"
              >{{ Math.round(program.user_progress.progress_percentage) }}%</span
            >
          </div>
        </div>
        <div class="w-full h-4 bg-gray-100 rounded-lg overflow-hidden">
          <div
            class="h-full transition-all duration-500"
            :class="
              program.user_progress.completed
                ? 'bg-gradient-to-r from-green-500 to-green-400'
                : 'bg-gradient-to-r from-blue-700 to-blue-400'
            "
            :style="{ width: `${program.user_progress.progress_percentage}%` }"
          ></div>
        </div>
        <div
          v-if="program.user_progress.completed"
          class="mt-4 p-4 bg-green-50 text-green-800 rounded-lg text-center text-lg font-semibold"
        >
          🎉 Program Completed! Well done!
        </div>
      </div>

      <!-- Content Tabs -->
      <div class="flex gap-4 mb-6 border-b-2 border-gray-300">
        <button
          class="py-3 px-6 bg-none border-none border-b-[3px] border-transparent text-lg font-semibold text-gray-600 cursor-pointer transition-all duration-300 -mb-0.5 hover:text-blue-700 md:py-2 md:px-4 md:text-base"
          :class="{ 'text-blue-700 !border-blue-700': activeTab === 'rewards' }"
          @click="activeTab = 'rewards'"
        >
          Rewards ({{ program.rewards.length }})
        </button>
        <button
          class="py-3 px-6 bg-none border-none border-b-[3px] border-transparent text-lg font-semibold text-gray-600 cursor-pointer transition-all duration-300 -mb-0.5 hover:text-blue-700 md:py-2 md:px-4 md:text-base"
          :class="{ 'text-blue-700 !border-blue-700': activeTab === 'challenges' }"
          @click="activeTab = 'challenges'"
        >
          Challenges ({{ program.challenges.length }})
        </button>
      </div>

      <!-- Rewards Tab -->
      <div
        v-if="activeTab === 'rewards'"
        class="p-6 bg-white rounded-xl shadow-[0_2px_8px_rgba(0,0,0,0.1)]"
      >
        <div
          v-if="program.rewards.length === 0"
          class="text-center py-12 px-6 text-gray-400 text-lg"
        >
          No rewards available for this program.
        </div>
        <ProgramRewardTier
          v-for="reward in sortedRewards"
          :key="reward.id"
          :reward="reward"
          :currentProgress="program.user_progress?.current_xp || 0"
          :currentStars="program.user_progress?.current_stars || 0"
          :starsRequired="program.stars_required || 50"
          :programType="program.type"
          @claim="claimReward"
        />
      </div>

      <!-- Challenges Tab -->
      <div
        v-if="activeTab === 'challenges'"
        class="p-6 bg-white rounded-xl shadow-[0_2px_8px_rgba(0,0,0,0.1)]"
      >
        <div
          v-if="program.challenges.length === 0"
          class="text-center py-12 px-6 text-gray-400 text-lg"
        >
          No challenges available for this program.
        </div>
        <ProgramChallengeItem
          v-for="challenge in program.challenges"
          :key="challenge.id"
          :challenge="challenge"
        />
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import { useProgramStore } from "../stores/programStore";
import ProgramRewardTier from "../components/ProgramRewardTier.vue";
import ProgramChallengeItem from "../components/ProgramChallengeItem.vue";
import StubsDisplay from "../components/StubsDisplay.vue";

const router = useRouter();
const route = useRoute();
const programStore = useProgramStore();

const activeTab = ref("rewards");
const userStubsBalance = ref(0); // TODO: Get from user store

const loading = computed(() => programStore.loading);
const error = computed(() => programStore.error);
const program = computed(() => programStore.currentProgram);

const sortedRewards = computed(() => {
  if (!program.value?.rewards) return [];
  return [...program.value.rewards].sort((a, b) => {
    // Sort by xp_threshold or stars_threshold
    const aThreshold = a.xp_threshold ?? a.stars_threshold ?? Infinity;
    const bThreshold = b.xp_threshold ?? b.stars_threshold ?? Infinity;
    return aThreshold - bThreshold;
  });
});

const loadProgramDetails = async () => {
  const programId = parseInt(route.params.id);
  await programStore.fetchProgramDetails(programId);
};

const claimReward = async (rewardId) => {
  try {
    await programStore.claimReward(rewardId);
    // Optionally show success message
    alert("Reward claimed successfully!");
  } catch (error) {
    alert("Failed to claim reward. Please try again.");
  }
};

const goBack = () => {
  router.push({ name: "Programs" });
};

const formatNumber = (num) => {
  if (!num) return "0";
  return num.toLocaleString();
};

onMounted(() => {
  loadProgramDetails();
});
</script>

<style scoped>
/* No additional styles needed - all converted to Tailwind */
</style>
