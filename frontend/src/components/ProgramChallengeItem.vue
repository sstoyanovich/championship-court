<template>
  <div
    class="bg-white border rounded-lg p-4 mb-3 transition-all duration-300"
    :class="challenge.completed ? 'bg-green-50 border-green-500' : 'border-gray-300'"
  >
    <div class="flex items-start gap-3 mb-3">
      <div
        class="w-10 h-10 flex items-center justify-center rounded-lg text-xl flex-shrink-0"
        :class="{
          'bg-blue-50': challenge.type === 'stat',
          'bg-purple-50': challenge.type === 'game_count',
          'bg-orange-50': challenge.type === 'pxp',
        }"
      >
        {{ getChallengeIcon(challenge.type) }}
      </div>
      <div class="flex-1">
        <h4 class="my-0 mb-1 text-base font-semibold text-gray-800">{{ challenge.description }}</h4>
        <div class="flex gap-2 flex-wrap">
          <span class="text-xs py-0.5 px-2 rounded-xl bg-gray-100 text-gray-600">{{
            getChallengeTypeLabel(challenge.type)
          }}</span>
          <span
            v-if="challenge.constraint_value"
            class="text-xs py-0.5 px-2 rounded-xl bg-green-50 text-green-700 font-semibold"
          >
            {{ challenge.constraint_value }}
          </span>
        </div>
      </div>
      <div class="flex-shrink-0">
        <span class="text-lg font-bold text-orange-600">
          {{ challenge.stars_reward ? `${challenge.stars_reward} ★` : `${challenge.xp_reward} XP` }}
        </span>
      </div>
    </div>

    <div class="mt-3">
      <div class="flex justify-between mb-1.5 text-sm">
        <span class="text-gray-600">
          {{ formatProgress(challenge.current_progress) }} /
          {{ formatProgress(challenge.target_value) }}
        </span>
        <span class="font-semibold text-blue-700">
          {{ Math.round(challenge.progress_percentage) }}%
        </span>
      </div>
      <div class="w-full h-1.5 bg-gray-100 rounded overflow-hidden">
        <div
          class="h-full transition-all duration-300"
          :class="
            challenge.completed
              ? 'bg-gradient-to-r from-green-500 to-green-400'
              : 'bg-gradient-to-r from-blue-700 to-blue-400'
          "
          :style="{ width: `${challenge.progress_percentage}%` }"
        ></div>
      </div>
    </div>

    <div
      v-if="challenge.completed"
      class="mt-2 inline-block py-1 px-3 bg-green-500 text-white rounded-2xl text-xs font-semibold"
    >
      ✓ Completed
    </div>
  </div>
</template>

<script setup>
import { defineProps } from "vue";

const props = defineProps({
  challenge: {
    type: Object,
    required: true,
  },
});

const getChallengeIcon = (type) => {
  const icons = {
    stat: "📊",
    game_count: "🎮",
    pxp: "⭐",
  };
  return icons[type] || "🏆";
};

const getChallengeTypeLabel = (type) => {
  const labels = {
    stat: "Stat Challenge",
    game_count: "Game Count",
    pxp: "Player XP",
  };
  return labels[type] || type;
};

const formatProgress = (value) => {
  if (!value && value !== 0) return "0";
  return value.toLocaleString();
};
</script>

<style scoped>
/* No additional styles needed - all converted to Tailwind */
</style>
