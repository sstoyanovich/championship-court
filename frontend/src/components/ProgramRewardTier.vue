<template>
  <div
    class="bg-white border-2 rounded-xl p-4 mb-4 transition-all duration-300"
    :class="{
      'border-orange-400 bg-orange-50': rewardStatus === 'unlocked',
      'border-green-500 bg-green-50 opacity-85': rewardStatus === 'claimed',
      'border-gray-300 opacity-60': rewardStatus === 'locked',
    }"
  >
    <div class="mb-3" v-if="reward.xp_threshold">
      <span class="inline-block py-1 px-3 bg-blue-700 text-white rounded-full text-sm font-semibold"
        >{{ formatNumber(reward.xp_threshold) }} XP</span
      >
    </div>
    <div class="mb-3" v-if="reward.stars_threshold">
      <span
        class="inline-block py-1 px-3 bg-gradient-to-r from-orange-400 to-orange-500 text-white rounded-full text-sm font-semibold"
        >⭐ {{ formatNumber(reward.stars_threshold) }} Stars</span
      >
    </div>

    <!-- Player Card Reward -->
    <div v-if="reward.reward_type === 'player' && reward.player">
      <div class="mb-3 flex justify-center">
        <PlayerCard :player="reward.player" :revealed="true" />
      </div>
      <div class="flex justify-center">
        <div
          class="py-1.5 px-3 rounded-full text-sm font-semibold whitespace-nowrap"
          :class="{
            'bg-green-500 text-white': rewardStatus === 'claimed',
            'bg-orange-400 text-white': rewardStatus === 'unlocked',
            'bg-gray-300 text-gray-400': rewardStatus === 'locked',
          }"
        >
          <span v-if="reward.claimed">✓ Claimed</span>
          <span v-else-if="isUnlocked">🎁 Available</span>
          <span v-else>🔒 Locked</span>
        </div>
      </div>
    </div>

    <!-- Non-Player Rewards -->
    <div v-else class="flex items-center gap-4">
      <div
        class="w-[50px] h-[50px] flex items-center justify-center rounded-xl text-[1.75rem] flex-shrink-0"
        :class="{
          'bg-orange-50': reward.reward_type === 'stubs',
          'bg-blue-50': reward.reward_type === 'pack',
          'bg-purple-50': reward.reward_type === 'player',
        }"
      >
        {{ getRewardIcon(reward.reward_type) }}
      </div>

      <div class="flex-1">
        <div class="text-xs text-gray-400 uppercase font-semibold mb-1">
          {{ getRewardTypeLabel(reward.reward_type) }}
        </div>
        <div class="text-base font-semibold text-gray-800 mb-1">{{ reward.description }}</div>
        <div class="text-sm text-gray-600" v-if="reward.reward_amount > 1">
          {{ formatNumber(reward.reward_amount) }}x
        </div>
      </div>

      <div
        class="py-1.5 px-3 rounded-full text-sm font-semibold whitespace-nowrap"
        :class="{
          'bg-green-500 text-white': rewardStatus === 'claimed',
          'bg-orange-400 text-white': rewardStatus === 'unlocked',
          'bg-gray-300 text-gray-400': rewardStatus === 'locked',
        }"
      >
        <span v-if="reward.claimed">✓ Claimed</span>
        <span v-else-if="isUnlocked">🎁 Available</span>
        <span v-else>🔒 Locked</span>
      </div>
    </div>

    <button
      v-if="isUnlocked && !reward.claimed"
      class="mt-3 w-full py-3 bg-gradient-to-br from-orange-400 to-orange-500 text-white border-none rounded-lg text-base font-semibold cursor-pointer transition-all duration-300 hover:-translate-y-0.5 hover:shadow-[0_4px_12px_rgba(255,152,0,0.4)] active:translate-y-0"
      @click="$emit('claim', reward.id)"
    >
      Claim Reward
    </button>
  </div>
</template>

<script setup>
import { defineProps, defineEmits, computed } from "vue";
import PlayerCard from "./PlayerCard.vue";

const props = defineProps({
  reward: {
    type: Object,
    required: true,
  },
  currentProgress: {
    type: Number,
    default: 0,
  },
  programType: {
    type: String,
    default: "xp",
  },
  currentStars: {
    type: Number,
    default: 0,
  },
  starsRequired: {
    type: Number,
    default: 50,
  },
});

const emit = defineEmits(["claim"]);

const isUnlocked = computed(() => {
  if (props.programType === "star") {
    // Star programs - check stars threshold
    const requiredStars = props.reward.stars_threshold ?? props.starsRequired;
    return props.currentStars >= requiredStars;
  }
  // XP program rewards - check XP threshold
  if (props.reward.xp_threshold === null) {
    return true;
  }
  return props.currentProgress >= props.reward.xp_threshold;
});

const rewardStatus = computed(() => {
  if (props.reward.claimed) return "claimed";
  if (isUnlocked.value) return "unlocked";
  return "locked";
});

const getRewardIcon = (type) => {
  const icons = {
    stubs: "💰",
    pack: "🎁",
    player: "🏀",
  };
  return icons[type] || "🏆";
};

const getRewardTypeLabel = (type) => {
  const labels = {
    stubs: "Stubs",
    pack: "Pack",
    player: "Player Card",
  };
  return labels[type] || type;
};

const formatNumber = (num) => {
  if (!num) return "0";
  return num.toLocaleString();
};
</script>

<style scoped>
/* No additional styles needed - all converted to Tailwind */
</style>
