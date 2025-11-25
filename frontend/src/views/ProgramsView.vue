<template>
  <div class="max-w-[1200px] mx-auto p-6 md:p-4">
    <div class="flex justify-between items-center mb-8 md:flex-col md:items-start md:gap-4">
      <h1 class="text-[2.5rem] font-bold text-gray-800 m-0 md:text-[2rem]">Programs</h1>
      <StubsDisplay :stubsBalance="userStubsBalance" />
    </div>

    <div v-if="loading" class="text-center py-12 px-6">
      <p>Loading programs...</p>
    </div>

    <div v-else-if="error" class="text-center py-12 px-6">
      <p>{{ error }}</p>
      <button
        @click="loadPrograms"
        class="mt-4 py-3 px-6 bg-blue-700 text-white border-none rounded-lg cursor-pointer text-base font-semibold"
      >
        Retry
      </button>
    </div>

    <div v-else class="flex flex-col gap-12">
      <!-- Team Affinity Banner -->
      <section class="mb-6">
        <div
          @click="navigateToTeamAffinity"
          class="team-affinity-banner bg-gradient-to-r from-blue-600 to-purple-700 rounded-2xl p-8 text-white cursor-pointer hover:shadow-2xl transition-all hover:scale-[1.02]"
        >
          <div class="flex justify-between items-center md:flex-col md:items-start md:gap-4">
            <div>
              <h2 class="text-[2.2rem] font-bold mb-2 flex items-center gap-3">
                <span class="text-[2.5rem]">🏀</span>
                Team Affinity
              </h2>
              <p class="text-white/90 text-lg mb-4">
                Complete year-long team journeys for exclusive rewards. Choose from all 30 NBA
                teams!
              </p>
              <div class="flex gap-4 text-sm md:flex-col">
                <div class="bg-white/20 rounded-lg px-4 py-2">
                  <span class="font-semibold">30 Teams</span>
                </div>
                <div class="bg-white/20 rounded-lg px-4 py-2">
                  <span class="font-semibold">365 Star Journey</span>
                </div>
                <div class="bg-white/20 rounded-lg px-4 py-2">
                  <span class="font-semibold">Exclusive Cards</span>
                </div>
              </div>
            </div>
            <div
              class="text-4xl font-bold bg-white/20 rounded-full w-16 h-16 flex items-center justify-center"
            >
              →
            </div>
          </div>
        </div>
      </section>

      <!-- XP Programs Section -->
      <section v-if="xpPrograms.length > 0" class="mb-6">
        <h2 class="text-[2rem] font-bold text-gray-800 mb-2 flex items-center gap-3">
          <span class="text-[2rem]">⚡</span>
          XP Programs
        </h2>
        <p class="text-gray-600 mb-6 text-lg">
          Earn XP by playing games and completing challenges. Unlock rewards at different XP
          milestones.
        </p>
        <div class="grid grid-cols-[repeat(auto-fill,minmax(350px,1fr))] gap-6 md:grid-cols-1">
          <ProgramCard
            v-for="program in xpPrograms"
            :key="program.id"
            :program="program"
            @click="navigateToProgram"
          />
        </div>
      </section>

      <!-- Star Programs Section -->
      <section v-if="starPrograms.length > 0" class="mb-6">
        <h2 class="text-[2rem] font-bold text-gray-800 mb-2 flex items-center gap-3">
          <span class="text-[2rem]">⭐</span>
          Star Programs
        </h2>
        <p class="text-gray-600 mb-6 text-lg">
          Complete challenges to earn stars. Reach 50 stars to unlock the final reward!
        </p>
        <div class="grid grid-cols-[repeat(auto-fill,minmax(350px,1fr))] gap-6 md:grid-cols-1">
          <ProgramCard
            v-for="program in starPrograms"
            :key="program.id"
            :program="program"
            @click="navigateToProgram"
          />
        </div>
      </section>

      <div v-if="programs.length === 0" class="text-center py-12 px-6 text-gray-400 text-lg">
        <p>No active programs available at the moment.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from "vue";
import { useRouter } from "vue-router";
import { useProgramStore } from "../stores/programStore";
import ProgramCard from "../components/ProgramCard.vue";
import StubsDisplay from "../components/StubsDisplay.vue";

const router = useRouter();
const programStore = useProgramStore();

const userStubsBalance = ref(0); // TODO: Get from user store

const loading = computed(() => programStore.loading);
const error = computed(() => programStore.error);
const programs = computed(() => programStore.programs);
const xpPrograms = computed(() => programStore.xpPrograms);
const starPrograms = computed(() => programStore.starPrograms);

const loadPrograms = async () => {
  await programStore.fetchPrograms();
};

const navigateToProgram = (programId) => {
  router.push({ name: "ProgramDetail", params: { id: programId } });
};

const navigateToTeamAffinity = () => {
  router.push({ name: "TeamAffinity" });
};

onMounted(() => {
  loadPrograms();
});
</script>

<style scoped>
/* No additional styles needed - all converted to Tailwind */
</style>
