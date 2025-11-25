<template>
  <div class="bg-brand-gradient rounded-2xl p-6 text-white shadow-[0_10px_30px_rgba(0,0,0,0.2)]">
    <div
      class="flex justify-between items-center mb-6 pb-4 border-b-2 border-white/20 max-sm:flex-col max-sm:gap-3 max-sm:items-start"
    >
      <h3 class="text-2xl font-bold m-0">{{ playerName }}</h3>
      <span class="bg-white/20 py-2 px-4 rounded-full text-sm font-semibold"
        >{{ gamesPlayed }} Games</span
      >
    </div>

    <div class="flex flex-col gap-5">
      <!-- Averages -->
      <div class="bg-white/10 rounded-xl p-4 backdrop-blur-[10px]">
        <h4 class="text-sm font-semibold uppercase tracking-wider m-0 mb-3 opacity-80">
          Per Game Averages
        </h4>
        <div class="grid grid-cols-3 gap-4">
          <div class="flex flex-col items-center text-center">
            <span class="text-xs font-semibold uppercase opacity-70 mb-1">PPG</span>
            <span class="text-[1.75rem] font-bold text-yellow-300 max-sm:text-2xl">{{ ppg }}</span>
          </div>
          <div class="flex flex-col items-center text-center">
            <span class="text-xs font-semibold uppercase opacity-70 mb-1">RPG</span>
            <span class="text-2xl font-bold max-sm:text-xl">{{ rpg }}</span>
          </div>
          <div class="flex flex-col items-center text-center">
            <span class="text-xs font-semibold uppercase opacity-70 mb-1">APG</span>
            <span class="text-2xl font-bold max-sm:text-xl">{{ apg }}</span>
          </div>
        </div>
      </div>

      <!-- Shooting -->
      <div class="bg-white/10 rounded-xl p-4 backdrop-blur-[10px]">
        <h4 class="text-sm font-semibold uppercase tracking-wider m-0 mb-3 opacity-80">Shooting</h4>
        <div class="grid grid-cols-3 gap-4 max-sm:gap-3">
          <div class="flex flex-col items-center text-center">
            <span class="text-xs font-semibold uppercase opacity-70 mb-1">FG%</span>
            <span class="text-2xl font-bold max-sm:text-xl">{{ fgPercentage }}%</span>
          </div>
          <div class="flex flex-col items-center text-center">
            <span class="text-xs font-semibold uppercase opacity-70 mb-1">3P%</span>
            <span class="text-2xl font-bold max-sm:text-xl">{{ tpPercentage }}%</span>
          </div>
          <div class="flex flex-col items-center text-center">
            <span class="text-xs font-semibold uppercase opacity-70 mb-1">FGM-A</span>
            <span class="text-lg font-bold">{{ totalFgm }}-{{ totalFga }}</span>
          </div>
        </div>
      </div>

      <!-- Other Stats -->
      <div class="bg-white/10 rounded-xl p-4 backdrop-blur-[10px]">
        <h4 class="text-sm font-semibold uppercase tracking-wider m-0 mb-3 opacity-80">Other</h4>
        <div class="grid grid-cols-3 gap-4 max-sm:gap-3">
          <div class="flex flex-col items-center text-center">
            <span class="text-xs font-semibold uppercase opacity-70 mb-1">STL</span>
            <span class="text-2xl font-bold max-sm:text-xl">{{ totalSteals }}</span>
          </div>
          <div class="flex flex-col items-center text-center">
            <span class="text-xs font-semibold uppercase opacity-70 mb-1">BLK</span>
            <span class="text-2xl font-bold max-sm:text-xl">{{ totalBlocks }}</span>
          </div>
          <div class="flex flex-col items-center text-center">
            <span class="text-xs font-semibold uppercase opacity-70 mb-1">TO</span>
            <span class="text-2xl font-bold max-sm:text-xl">{{ totalTurnovers }}</span>
          </div>
        </div>
      </div>

      <!-- Totals (Collapsed/Expandable) -->
      <div v-if="showTotals" class="bg-white/10 rounded-xl p-4 backdrop-blur-[10px]">
        <h4 class="text-sm font-semibold uppercase tracking-wider m-0 mb-3 opacity-80">
          Career Totals
        </h4>
        <div class="grid grid-cols-2 gap-3 max-sm:grid-cols-1">
          <div class="flex justify-between items-center bg-white/10 py-2 px-3 rounded-lg">
            <span class="text-sm font-medium opacity-80">Points</span>
            <span class="text-lg font-bold">{{ totalPoints }}</span>
          </div>
          <div class="flex justify-between items-center bg-white/10 py-2 px-3 rounded-lg">
            <span class="text-sm font-medium opacity-80">Rebounds</span>
            <span class="text-lg font-bold">{{ totalRebounds }}</span>
          </div>
          <div class="flex justify-between items-center bg-white/10 py-2 px-3 rounded-lg">
            <span class="text-sm font-medium opacity-80">Assists</span>
            <span class="text-lg font-bold">{{ totalAssists }}</span>
          </div>
          <div class="flex justify-between items-center bg-white/10 py-2 px-3 rounded-lg">
            <span class="text-sm font-medium opacity-80">Minutes</span>
            <span class="text-lg font-bold">{{ totalMinutes }}</span>
          </div>
        </div>
      </div>
    </div>

    <button
      v-if="!showTotals"
      @click="showTotals = true"
      class="w-full mt-4 py-3 bg-white/20 text-white border-2 border-white/30 rounded-xl text-sm font-semibold cursor-pointer transition-all duration-200 hover:bg-white/30 hover:-translate-y-0.5"
    >
      Show Career Totals
    </button>
  </div>
</template>

<script setup>
import { ref, computed } from "vue";

const props = defineProps({
  playerName: {
    type: String,
    required: true,
  },
  gamesPlayed: {
    type: Number,
    default: 0,
  },
  totalMinutes: {
    type: Number,
    default: 0,
  },
  totalPoints: {
    type: Number,
    default: 0,
  },
  totalRebounds: {
    type: Number,
    default: 0,
  },
  totalAssists: {
    type: Number,
    default: 0,
  },
  totalSteals: {
    type: Number,
    default: 0,
  },
  totalBlocks: {
    type: Number,
    default: 0,
  },
  totalTurnovers: {
    type: Number,
    default: 0,
  },
  totalFgm: {
    type: Number,
    default: 0,
  },
  totalFga: {
    type: Number,
    default: 0,
  },
  total3pm: {
    type: Number,
    default: 0,
  },
  total3pa: {
    type: Number,
    default: 0,
  },
});

const showTotals = ref(false);

const ppg = computed(() => {
  if (props.gamesPlayed === 0) return "0.0";
  return (props.totalPoints / props.gamesPlayed).toFixed(1);
});

const rpg = computed(() => {
  if (props.gamesPlayed === 0) return "0.0";
  return (props.totalRebounds / props.gamesPlayed).toFixed(1);
});

const apg = computed(() => {
  if (props.gamesPlayed === 0) return "0.0";
  return (props.totalAssists / props.gamesPlayed).toFixed(1);
});

const fgPercentage = computed(() => {
  if (props.totalFga === 0) return "0.0";
  return ((props.totalFgm / props.totalFga) * 100).toFixed(1);
});

const tpPercentage = computed(() => {
  if (props.total3pa === 0) return "0.0";
  return ((props.total3pm / props.total3pa) * 100).toFixed(1);
});
</script>

<style scoped>
/* No additional styles needed - all converted to Tailwind */
</style>
