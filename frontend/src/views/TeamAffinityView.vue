<template>
  <div class="max-w-[1600px] mx-auto p-6 md:p-4">
    <!-- Header -->
    <div class="mb-8">
      <div class="flex items-center gap-3 mb-2">
        <router-link to="/programs" class="text-blue-600 hover:text-blue-800 text-lg">
          Programs
        </router-link>
        <span class="text-gray-400 text-lg">›</span>
        <span class="text-gray-800 text-lg font-semibold">Team Affinity</span>
      </div>
      <h1 class="text-[2.5rem] font-bold text-gray-800 m-0 md:text-[2rem]">Team Affinity</h1>
      <p class="text-gray-600 mt-2 text-lg">
        Choose your team and complete year-long journeys to earn exclusive rewards. Each team has
        unique challenges and player cards to unlock.
      </p>
    </div>

    <!-- Loading State -->
    <div v-if="programStore.teamAffinityLoading" class="text-center py-12 px-6">
      <p class="text-gray-600 text-lg">Loading Team Affinity programs...</p>
    </div>

    <!-- Error State -->
    <div v-else-if="programStore.error" class="text-center py-12 px-6">
      <p class="text-red-600 text-lg">{{ programStore.error }}</p>
      <button
        @click="loadTeamAffinity"
        class="mt-4 py-3 px-6 bg-blue-700 text-white border-none rounded-lg cursor-pointer text-base font-semibold hover:bg-blue-800"
      >
        Retry
      </button>
    </div>

    <!-- No Programs State -->
    <div v-else-if="teamAffinityPrograms.length === 0" class="text-center py-12 px-6">
      <p class="text-gray-400 text-lg">No Team Affinity programs available yet.</p>
    </div>

    <!-- Team Affinity Programs by Division -->
    <div v-else class="space-y-12">
      <div
        v-for="(division, divisionName) in teamAffinityByDivision"
        :key="divisionName"
        class="division-section"
      >
        <h2 class="text-[1.8rem] font-bold text-gray-800 mb-6 flex items-center gap-3">
          <span class="text-blue-600">{{ divisionName }}</span>
          <span class="text-gray-400 text-xl">Division</span>
        </h2>
        <div
          class="grid grid-cols-[repeat(auto-fill,minmax(200px,1fr))] gap-4 md:grid-cols-2 sm:grid-cols-1"
        >
          <div
            v-for="program in division"
            :key="program.id"
            @click="navigateToProgram(program.id)"
            class="team-card group relative overflow-hidden rounded-xl cursor-pointer transition-all duration-300 hover:scale-105 hover:shadow-2xl"
            :style="{
              background: `linear-gradient(135deg, ${getTeamColors(program.team).primary} 0%, ${
                getTeamColors(program.team).secondary
              } 100%)`,
            }"
          >
            <!-- Team Logo Background -->
            <div
              class="absolute inset-0 opacity-10 flex items-center justify-center overflow-hidden"
            >
              <img
                v-if="getTeamLogo(program.team)"
                :src="getTeamLogo(program.team)"
                :alt="program.team"
                class="w-32 h-32 object-contain transform group-hover:scale-110 transition-transform duration-300"
              />
            </div>

            <!-- Content -->
            <div class="relative z-10 p-4">
              <!-- Team Logo & Name -->
              <div class="flex flex-col items-center gap-2 mb-3">
                <div
                  class="w-14 h-14 bg-white rounded-lg p-2 shadow-lg flex items-center justify-center"
                >
                  <img
                    v-if="getTeamLogo(program.team)"
                    :src="getTeamLogo(program.team)"
                    :alt="program.team"
                    class="w-full h-full object-contain"
                  />
                  <span v-else class="text-xl">🏀</span>
                </div>
                <div class="text-center">
                  <h3 class="text-base font-bold text-white drop-shadow-md leading-tight">
                    {{ program.team }}
                  </h3>
                </div>
              </div>

              <!-- Progress Info -->
              <div class="bg-black/20 backdrop-blur-sm rounded-lg p-3 mb-2">
                <div class="flex justify-between text-xs text-white/90 mb-1.5">
                  <span>Progress</span>
                  <span class="font-bold">
                    {{ program.user_progress?.current_stars || 0 }} /
                    {{ program.stars_required }} ⭐
                  </span>
                </div>
                <div class="w-full bg-white/20 rounded-full h-2 overflow-hidden">
                  <div
                    class="bg-white h-full rounded-full transition-all duration-500"
                    :style="{ width: `${getProgressPercentage(program)}%` }"
                  ></div>
                </div>
              </div>

              <!-- Next Milestone -->
              <div class="text-white/90 text-xs flex items-center justify-center">
                <span class="font-semibold bg-white/20 px-2 py-1 rounded-full">
                  Next: {{ getNextMilestone(program) }}
                </span>
              </div>
            </div>

            <!-- Hover Indicator -->
            <div
              class="absolute bottom-0 right-0 w-8 h-8 bg-white/20 rounded-tl-full flex items-start justify-end p-1.5 opacity-0 group-hover:opacity-100 transition-opacity"
            >
              <span class="text-white text-sm">→</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import { useProgramStore } from "../stores/programStore";

const router = useRouter();
const programStore = useProgramStore();

const teamAffinityPrograms = computed(() => programStore.teamAffinityPrograms);
const teamAffinityByDivision = computed(() => programStore.teamAffinityByDivision);

// NBA Team Colors and Logos
const teamData = {
  // Atlantic Division
  "Boston Celtics": {
    primary: "#007A33",
    secondary: "#BA9653",
    logo: "https://cdn.nba.com/logos/nba/1610612738/global/L/logo.svg",
  },
  "Brooklyn Nets": {
    primary: "#000000",
    secondary: "#FFFFFF",
    logo: "https://cdn.nba.com/logos/nba/1610612751/global/L/logo.svg",
  },
  "New York Knicks": {
    primary: "#006BB6",
    secondary: "#F58426",
    logo: "https://cdn.nba.com/logos/nba/1610612752/global/L/logo.svg",
  },
  "Philadelphia 76ers": {
    primary: "#006BB6",
    secondary: "#ED174C",
    logo: "https://cdn.nba.com/logos/nba/1610612755/global/L/logo.svg",
  },
  "Toronto Raptors": {
    primary: "#CE1141",
    secondary: "#000000",
    logo: "https://cdn.nba.com/logos/nba/1610612761/global/L/logo.svg",
  },

  // Central Division
  "Chicago Bulls": {
    primary: "#CE1141",
    secondary: "#000000",
    logo: "https://cdn.nba.com/logos/nba/1610612741/global/L/logo.svg",
  },
  "Cleveland Cavaliers": {
    primary: "#860038",
    secondary: "#FDBB30",
    logo: "https://cdn.nba.com/logos/nba/1610612739/global/L/logo.svg",
  },
  "Detroit Pistons": {
    primary: "#C8102E",
    secondary: "#1D42BA",
    logo: "https://cdn.nba.com/logos/nba/1610612765/global/L/logo.svg",
  },
  "Indiana Pacers": {
    primary: "#002D62",
    secondary: "#FDBB30",
    logo: "https://cdn.nba.com/logos/nba/1610612754/global/L/logo.svg",
  },
  "Milwaukee Bucks": {
    primary: "#00471B",
    secondary: "#EEE1C6",
    logo: "https://cdn.nba.com/logos/nba/1610612749/global/L/logo.svg",
  },

  // Southeast Division
  "Atlanta Hawks": {
    primary: "#E03A3E",
    secondary: "#C1D32F",
    logo: "https://cdn.nba.com/logos/nba/1610612737/global/L/logo.svg",
  },
  "Charlotte Hornets": {
    primary: "#1D1160",
    secondary: "#00788C",
    logo: "https://cdn.nba.com/logos/nba/1610612766/global/L/logo.svg",
  },
  "Miami Heat": {
    primary: "#98002E",
    secondary: "#F9A01B",
    logo: "https://cdn.nba.com/logos/nba/1610612748/global/L/logo.svg",
  },
  "Orlando Magic": {
    primary: "#0077C0",
    secondary: "#C4CED4",
    logo: "https://cdn.nba.com/logos/nba/1610612753/global/L/logo.svg",
  },
  "Washington Wizards": {
    primary: "#002B5C",
    secondary: "#E31837",
    logo: "https://cdn.nba.com/logos/nba/1610612764/global/L/logo.svg",
  },

  // Northwest Division
  "Denver Nuggets": {
    primary: "#0E2240",
    secondary: "#FEC524",
    logo: "https://cdn.nba.com/logos/nba/1610612743/global/L/logo.svg",
  },
  "Minnesota Timberwolves": {
    primary: "#0C2340",
    secondary: "#236192",
    logo: "https://cdn.nba.com/logos/nba/1610612750/global/L/logo.svg",
  },
  "Oklahoma City Thunder": {
    primary: "#007AC1",
    secondary: "#EF3B24",
    logo: "https://cdn.nba.com/logos/nba/1610612760/global/L/logo.svg",
  },
  "Portland Trail Blazers": {
    primary: "#E03A3E",
    secondary: "#000000",
    logo: "https://cdn.nba.com/logos/nba/1610612757/global/L/logo.svg",
  },
  "Utah Jazz": {
    primary: "#002B5C",
    secondary: "#00471B",
    logo: "https://cdn.nba.com/logos/nba/1610612762/global/L/logo.svg",
  },

  // Pacific Division
  "Golden State Warriors": {
    primary: "#1D428A",
    secondary: "#FFC72C",
    logo: "https://cdn.nba.com/logos/nba/1610612744/global/L/logo.svg",
  },
  "LA Clippers": {
    primary: "#C8102E",
    secondary: "#1D428A",
    logo: "https://cdn.nba.com/logos/nba/1610612746/global/L/logo.svg",
  },
  "Los Angeles Lakers": {
    primary: "#552583",
    secondary: "#FDB927",
    logo: "https://cdn.nba.com/logos/nba/1610612747/global/L/logo.svg",
  },
  "Phoenix Suns": {
    primary: "#1D1160",
    secondary: "#E56020",
    logo: "https://cdn.nba.com/logos/nba/1610612756/global/L/logo.svg",
  },
  "Sacramento Kings": {
    primary: "#5A2D81",
    secondary: "#63727A",
    logo: "https://cdn.nba.com/logos/nba/1610612758/global/L/logo.svg",
  },

  // Southwest Division
  "Dallas Mavericks": {
    primary: "#00538C",
    secondary: "#002B5E",
    logo: "https://cdn.nba.com/logos/nba/1610612742/global/L/logo.svg",
  },
  "Houston Rockets": {
    primary: "#CE1141",
    secondary: "#000000",
    logo: "https://cdn.nba.com/logos/nba/1610612745/global/L/logo.svg",
  },
  "Memphis Grizzlies": {
    primary: "#5D76A9",
    secondary: "#12173F",
    logo: "https://cdn.nba.com/logos/nba/1610612763/global/L/logo.svg",
  },
  "New Orleans Pelicans": {
    primary: "#0C2340",
    secondary: "#C8102E",
    logo: "https://cdn.nba.com/logos/nba/1610612740/global/L/logo.svg",
  },
  "San Antonio Spurs": {
    primary: "#C4CED4",
    secondary: "#000000",
    logo: "https://cdn.nba.com/logos/nba/1610612759/global/L/logo.svg",
  },
};

const getTeamColors = (teamName) => {
  return teamData[teamName] || { primary: "#1D428A", secondary: "#FFC72C" };
};

const getTeamLogo = (teamName) => {
  return teamData[teamName]?.logo || null;
};

const loadTeamAffinity = async () => {
  await programStore.fetchTeamAffinityPrograms();
};

const navigateToProgram = (programId) => {
  router.push({ name: "ProgramDetail", params: { id: programId } });
};

const getProgressPercentage = (program) => {
  if (!program.user_progress) return 0;
  const current = program.user_progress.current_stars || 0;
  const total = program.stars_required || 365;
  return Math.min(100, (current / total) * 100);
};

const getNextMilestone = (program) => {
  const milestones = [
    5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55, 65, 75, 90, 105, 120, 135, 165, 195, 215, 235, 255,
    285, 315, 330, 345, 365,
  ];
  const current = program.user_progress?.current_stars || 0;

  const next = milestones.find((m) => m > current);
  if (next) {
    return `${next} ⭐`;
  }
  return "Complete! 🎉";
};

onMounted(() => {
  loadTeamAffinity();
});
</script>

<style scoped>
.team-card {
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.team-card:hover {
  box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.division-section {
  animation: fadeIn 0.6s ease-in;
}

@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
