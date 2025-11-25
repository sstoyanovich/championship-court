<template>
  <div
    class="bg-white border border-gray-300 rounded-xl p-5 cursor-pointer transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5"
    @click="$emit('click', program.id)"
  >
    <div class="flex justify-between items-center mb-3">
      <h3 class="text-2xl font-bold m-0 text-gray-800">{{ program.name }}</h3>
      <span
        class="px-3 py-1 rounded-full text-xs font-semibold uppercase"
        :class="program.type === 'xp' ? 'bg-blue-50 text-blue-700' : 'bg-orange-50 text-orange-600'"
      >
        {{ program.type === "xp" ? "XP Program" : "Star Program" }}
      </span>
    </div>

    <p class="text-gray-600 my-0 mb-4 leading-relaxed">{{ program.description }}</p>

    <div class="flex gap-5 mb-4">
      <div class="flex gap-2">
        <span class="text-gray-400 text-sm">Rewards:</span>
        <span class="text-gray-800 font-semibold text-sm">{{ program.rewards_count }}</span>
      </div>
      <div class="flex gap-2">
        <span class="text-gray-400 text-sm">Challenges:</span>
        <span class="text-gray-800 font-semibold text-sm">{{ program.challenges_count }}</span>
      </div>
    </div>

    <div v-if="program.user_progress" class="mt-4 pt-4 border-t border-gray-100">
      <div class="flex justify-between mb-2 text-sm text-gray-600">
        <span v-if="program.type === 'xp'">
          {{ formatNumber(program.user_progress.current_xp) }} /
          {{ formatNumber(program.total_xp_required) }} XP
        </span>
        <span v-else>
          {{ program.user_progress.current_stars }} / {{ program.stars_required }} Stars
        </span>
        <span class="font-semibold text-blue-700"
          >{{ Math.round(program.user_progress.progress_percentage) }}%</span
        >
      </div>
      <div class="w-full h-2 bg-gray-100 rounded overflow-hidden">
        <div
          class="h-full transition-all duration-300"
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
        class="mt-2 inline-block py-1 px-3 bg-green-500 text-white rounded-full text-xs font-semibold"
      >
        ✓ Completed
      </div>
    </div>
  </div>
</template>

<script setup>
import { defineProps, defineEmits } from "vue";

const props = defineProps({
  program: {
    type: Object,
    required: true,
  },
});

const emit = defineEmits(["click"]);

const formatNumber = (num) => {
  if (!num) return "0";
  return num.toLocaleString();
};
</script>

<style scoped>
/* No additional styles needed - all converted to Tailwind */
</style>
