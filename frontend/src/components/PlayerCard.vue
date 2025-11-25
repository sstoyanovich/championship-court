<script setup>
import { computed, ref, watch } from "vue";

const props = defineProps({
  player: {
    type: Object,
    required: true,
  },
  revealed: {
    type: Boolean,
    default: true,
  },
  greyed: {
    type: Boolean,
    default: false,
  },
  owned: {
    type: Boolean,
    default: false,
  },
  locked: {
    type: Boolean,
    default: false,
  },
  userCardId: {
    type: Number,
    default: null,
  },
  showStats: {
    type: Boolean,
    default: false,
  },
  playerXp: {
    type: Number,
    default: 0,
  },
  gamesPlayed: {
    type: Number,
    default: 0,
  },
  count: {
    type: Number,
    default: 1,
  },
  sellPrice: {
    type: Number,
    default: null,
  },
  buyPrice: {
    type: Number,
    default: null,
  },
  isTradeable: {
    type: Boolean,
    default: true,
  },
  showLockButton: {
    type: Boolean,
    default: true,
  },
});

const emit = defineEmits(["lock", "unlock", "view-stats", "sell", "buy"]);

// Debug logging for count changes
watch(
  () => props.count,
  (newCount) => {
    if (props.owned) {
      console.log(
        `${props.player.name}: count = ${newCount}, owned = ${props.owned}, locked = ${props.locked}`
      );
    }
  },
  { immediate: true }
);

const imageError = ref(false);
const logoError = ref(false);

const tierColors = {
  common: {
    bg: "linear-gradient(135deg, #6B7280 0%, #4B5563 100%)",
    shadow: "rgba(107, 114, 128, 0.4)",
    accent: "#6B7280",
    primary: "#6B7280",
    secondary: "#4B5563",
  },
  bronze: {
    bg: "linear-gradient(135deg, #CD7F32 0%, #A0522D 100%)",
    shadow: "rgba(205, 127, 50, 0.4)",
    accent: "#CD7F32",
    primary: "#CD7F32",
    secondary: "#A0522D",
  },
  silver: {
    bg: "linear-gradient(135deg, #C0C0C0 0%, #A8A8A8 100%)",
    shadow: "rgba(192, 192, 192, 0.4)",
    accent: "#C0C0C0",
    primary: "#C0C0C0",
    secondary: "#A8A8A8",
  },
  gold: {
    bg: "linear-gradient(135deg, #FFD700 0%, #FFA500 100%)",
    shadow: "rgba(255, 215, 0, 0.4)",
    accent: "#FFD700",
    primary: "#FFD700",
    secondary: "#FFA500",
  },
  emerald: {
    bg: "linear-gradient(135deg, #50C878 0%, #3EA964 100%)",
    shadow: "rgba(80, 200, 120, 0.4)",
    accent: "#50C878",
    primary: "#50C878",
    secondary: "#3EA964",
  },
  sapphire: {
    bg: "linear-gradient(135deg, #0F52BA 0%, #0A3D8F 100%)",
    shadow: "rgba(15, 82, 186, 0.4)",
    accent: "#0F52BA",
    primary: "#0F52BA",
    secondary: "#0A3D8F",
  },
  ruby: {
    bg: "linear-gradient(135deg, #E0115F 0%, #9B111E 100%)",
    shadow: "rgba(224, 17, 95, 0.4)",
    accent: "#E0115F",
    primary: "#E0115F",
    secondary: "#9B111E",
  },
  amethyst: {
    bg: "linear-gradient(135deg, #9966CC 0%, #7851A9 100%)",
    shadow: "rgba(153, 102, 204, 0.4)",
    accent: "#9966CC",
    primary: "#9966CC",
    secondary: "#7851A9",
  },
  diamond: {
    bg: "linear-gradient(135deg, #B9F2FF 0%, #87CEEB 100%)",
    shadow: "rgba(185, 242, 255, 0.4)",
    accent: "#B9F2FF",
    primary: "#B9F2FF",
    secondary: "#87CEEB",
  },
  pink_diamond: {
    bg: "linear-gradient(135deg, #FF69B4 0%, #FF1493 100%)",
    shadow: "rgba(255, 105, 180, 0.4)",
    accent: "#FF69B4",
    primary: "#FF69B4",
    secondary: "#FF1493",
  },
  galaxy_opal: {
    bg: "linear-gradient(135deg, #9D00FF 0%, #4B0082 100%)",
    shadow: "rgba(157, 0, 255, 0.4)",
    accent: "#9D00FF",
    primary: "#9D00FF",
    secondary: "#4B0082",
  },
};

const cardStyle = computed(() => {
  const tier = props.player.card_tier;
  return tierColors[tier] || tierColors.common;
});

const tierLabel = computed(() => {
  return props.player.card_tier.replace("_", " ").toUpperCase();
});

// Parallel levels configuration - vibrant colors like MLB cards
const parallelLevels = [
  { level: 0, threshold: 0, numeral: "", color: "#FFFFFF", name: "Base" },
  { level: 1, threshold: 300, numeral: "I", color: "#00FF41", name: "Green" }, // Bright neon green
  { level: 2, threshold: 600, numeral: "II", color: "#FF6B00", name: "Orange" }, // Vibrant orange
  { level: 3, threshold: 1000, numeral: "III", color: "#C026D3", name: "Purple" }, // Bright purple/magenta
  { level: 4, threshold: 1500, numeral: "IV", color: "#FF0000", name: "Red" }, // Pure red
  { level: 5, threshold: 3000, numeral: "V", color: "#00F5FF", name: "Teal" }, // Bright cyan/teal like MLB
  { level: 6, threshold: 5000, numeral: "VI", color: "#FFD700", name: "Yellow" }, // Gold
  { level: 7, threshold: 8000, numeral: "VII", color: "#000000", name: "Black" },
];

// Get current parallel level based on player XP
const currentParallel = computed(() => {
  const pxp = props.playerXp || 0;

  for (let i = parallelLevels.length - 1; i >= 0; i--) {
    if (pxp >= parallelLevels[i].threshold) {
      return parallelLevels[i];
    }
  }

  return parallelLevels[0];
});

// Determine if card has a parallel (level > 0)
const hasParallel = computed(() => {
  return currentParallel.value.level > 0;
});

// Get border color (parallel color if has parallel, otherwise white)
const borderColor = computed(() => {
  return hasParallel.value ? currentParallel.value.color : "#FFFFFF";
});

// Get glow effect for parallel - much more prominent like MLB cards
const parallelGlow = computed(() => {
  if (!hasParallel.value) return "";

  const color = currentParallel.value.color;
  // Create a very vibrant multi-layered glow
  return `0 0 40px ${color}FF, 0 0 60px ${color}AA, 0 0 80px ${color}66, 0 0 100px ${color}44, 0 8px 32px ${cardStyle.value.shadow}`;
});

// Get border gradient for parallel - vibrant animated gradient
const borderGradient = computed(() => {
  if (!hasParallel.value) return "#FFFFFF";

  const color = currentParallel.value.color;
  const isDarkColor = currentParallel.value.level === 7; // Black

  if (isDarkColor) {
    return `linear-gradient(135deg, ${color}, #555555, ${color})`;
  } else {
    // Create a vibrant multi-stop gradient
    return `linear-gradient(135deg, ${color}FF, ${color}AA, ${color}FF)`;
  }
});

// Dynamic font size based on name length
const nameFontSize = computed(() => {
  const nameLength = props.player.name.length;
  if (nameLength <= 12) return "1rem"; // 16px - base
  if (nameLength <= 16) return "0.9rem"; // 14.4px
  if (nameLength <= 20) return "0.8rem"; // 12.8px
  if (nameLength <= 24) return "0.7rem"; // 11.2px
  return "0.65rem"; // 10.4px - very long names
});

// Image URL - use direct URL since img tags don't have CORS issues
const displayImageUrl = computed(() => {
  return props.player.image_url || null;
});

// Check if player has custom card art
const hasCardArt = computed(() => {
  return props.player.card_art && props.player.card_art.trim() !== "";
});

// Get properly formatted card art URL
const cardArtUrl = computed(() => {
  if (!props.player.card_art) return null;

  const cardArt = props.player.card_art.trim();

  // If it's already a full URL (starts with http:// or https://), use it as-is
  if (cardArt.startsWith("http://") || cardArt.startsWith("https://")) {
    return cardArt;
  }

  const baseUrl = "http://localhost:8000";

  // If the path already starts with 'images/', use it as-is
  if (cardArt.startsWith("images/")) {
    return `${baseUrl}/${cardArt}`;
  }

  // If it starts with a slash, use it as-is
  if (cardArt.startsWith("/")) {
    return `${baseUrl}${cardArt}`;
  }

  // Otherwise, assume it's a relative path within images/card_art/
  return `${baseUrl}/images/card_art/${cardArt}`;
});

const handleLockToggle = () => {
  if (props.locked) {
    emit("unlock", props.userCardId);
  } else {
    emit("lock", props.userCardId);
  }
};

const handleImageError = (event) => {
  console.warn(`Failed to load player image for ${props.player.name}:`, props.player.image_url);
  imageError.value = true;
};

// Get team logo URL from 2kratings
const getTeamLogoUrl = (teamName) => {
  // Convert team name to the format used in 2kratings URLs
  const formatted = teamName.replace(/ /g, "-");
  return `https://www.2kratings.com/wp-content/uploads/${formatted}-Current-Logo.svg`;
};

// Get team abbreviation as fallback
const getTeamAbbr = (teamName) => {
  const abbr = {
    "Atlanta Hawks": "ATL",
    "Boston Celtics": "BOS",
    "Brooklyn Nets": "BKN",
    "Charlotte Hornets": "CHA",
    "Chicago Bulls": "CHI",
    "Cleveland Cavaliers": "CLE",
    "Dallas Mavericks": "DAL",
    "Denver Nuggets": "DEN",
    "Detroit Pistons": "DET",
    "Golden State Warriors": "GSW",
    "Houston Rockets": "HOU",
    "Indiana Pacers": "IND",
    "Los Angeles Clippers": "LAC",
    "Los Angeles Lakers": "LAL",
    "Memphis Grizzlies": "MEM",
    "Miami Heat": "MIA",
    "Milwaukee Bucks": "MIL",
    "Minnesota Timberwolves": "MIN",
    "New Orleans Pelicans": "NOP",
    "New York Knicks": "NYK",
    "Oklahoma City Thunder": "OKC",
    "Orlando Magic": "ORL",
    "Philadelphia 76ers": "PHI",
    "Phoenix Suns": "PHX",
    "Portland Trail Blazers": "POR",
    "Sacramento Kings": "SAC",
    "San Antonio Spurs": "SAS",
    "Toronto Raptors": "TOR",
    "Utah Jazz": "UTA",
    "Washington Wizards": "WAS",
    "Free Agent": "FA",
  };
  return abbr[teamName] || "NBA";
};
</script>

<template>
  <div
    class="relative rounded-2xl p-1 text-white overflow-hidden transition-all duration-300 h-[420px] w-full max-w-[320px] flex flex-col shadow-[0_8px_24px_rgba(0,0,0,0.3)] hover:-translate-y-2 hover:scale-[1.02] hover:shadow-[0_12px_32px_rgba(0,0,0,0.4)]"
    :class="{
      'opacity-50 grayscale-[70%] hover:-translate-y-1': greyed,
      'parallel-card': hasParallel,
    }"
    :style="{
      border: hasParallel ? '10px solid' : '6px solid white',
      borderImage: hasParallel ? `${borderGradient} 1` : 'none',
      boxShadow: hasParallel ? parallelGlow : `0 8px 24px ${cardStyle.shadow}`,
    }"
  >
    <!-- Diagonal Stripe Overlay for Parallels -->
    <div
      v-if="hasParallel"
      class="parallel-stripes"
      :style="{ '--parallel-color': currentParallel.color }"
    ></div>

    <!-- Custom Card Art Layout -->
    <div v-if="hasCardArt" class="relative h-full w-full rounded-xl overflow-hidden bg-gray-800">
      <!-- Card Art Image -->
      <img
        :src="cardArtUrl"
        :alt="player.name"
        class="absolute inset-0 w-full h-full object-cover"
        @load="() => console.log('Card art loaded successfully:', cardArtUrl)"
        @error="
          (e) => {
            console.error('Card art failed to load:', cardArtUrl);
            console.error('Raw card_art value:', player.card_art);
            e.target.style.display = 'none';
          }
        "
      />

      <!-- Count Badge -->
      <div
        v-if="owned && count > 1"
        class="absolute top-2 left-2 z-10 bg-gradient-to-br from-yellow-400 to-orange-500 text-white px-3 py-1.5 rounded-full font-bold text-sm shadow-[0_4px_12px_rgba(0,0,0,0.4)] border-2 border-white"
      >
        x{{ count }}
      </div>

      <!-- Parallel Badge -->
      <div
        v-if="hasParallel && owned"
        class="absolute left-2 z-10 px-3 py-1.5 rounded-lg font-black text-lg shadow-[0_6px_16px_rgba(0,0,0,0.8)] border-3 border-white parallel-badge"
        :class="owned && count > 1 ? 'top-14' : 'top-2'"
        :style="{
          backgroundColor: currentParallel.color,
          color: '#FFFFFF',
          textShadow: '0 2px 8px rgba(0,0,0,0.8)',
          boxShadow: `0 0 20px ${currentParallel.color}, 0 6px 16px rgba(0,0,0,0.8)`,
        }"
      >
        {{ currentParallel.numeral }}
      </div>

      <!-- Rating Badge for Custom Card Art -->
      <div class="absolute top-2 right-2 z-10">
        <svg
          width="65"
          height="78"
          viewBox="0 0 100 120"
          class="drop-shadow-[0_4px_8px_rgba(0,0,0,0.3)] transition-transform duration-300 hover:scale-105"
        >
          <defs>
            <linearGradient :id="`gradient-${player.id}`" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" :style="{ stopColor: cardStyle.primary, stopOpacity: 1 }" />
              <stop offset="100%" :style="{ stopColor: cardStyle.secondary, stopOpacity: 1 }" />
            </linearGradient>
            <filter :id="`shadow-${player.id}`" x="-50%" y="-50%" width="200%" height="200%">
              <feDropShadow dx="0" dy="4" stdDeviation="4" flood-opacity="0.4" />
            </filter>
          </defs>

          <!-- Shield/Badge Shape -->
          <path
            d="M 50 5 L 85 20 L 85 70 Q 85 90, 50 110 Q 15 90, 15 70 L 15 20 Z"
            :fill="`url(#gradient-${player.id})`"
            :filter="`url(#shadow-${player.id})`"
            stroke="rgba(255,255,255,0.4)"
            stroke-width="2"
          />

          <!-- Inner glow -->
          <path
            d="M 50 10 L 80 23 L 80 68 Q 80 85, 50 103 Q 20 85, 20 68 L 20 23 Z"
            fill="rgba(255,255,255,0.15)"
          />

          <!-- Rating Number -->
          <text
            x="50"
            y="65"
            text-anchor="middle"
            fill="white"
            font-size="32"
            font-weight="900"
            style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5)"
          >
            {{ player.overall_rating }}
          </text>
        </svg>
      </div>
    </div>

    <!-- Default Card Layout -->
    <div
      v-else
      class="relative h-full w-full rounded-xl overflow-hidden flex flex-col"
      :style="{
        background: cardStyle.bg,
      }"
    >
      <!-- Count Badge for Default Layout -->
      <div
        v-if="owned && count > 1"
        class="absolute top-2 left-2 z-10 bg-gradient-to-br from-yellow-400 to-orange-500 text-white px-3 py-1.5 rounded-full font-bold text-sm shadow-[0_4px_12px_rgba(0,0,0,0.4)] border-2 border-white"
      >
        x{{ count }}
      </div>

      <!-- Parallel Badge for Default Layout -->
      <div
        v-if="hasParallel && owned"
        class="absolute left-2 z-10 px-3 py-1.5 rounded-lg font-black text-lg shadow-[0_6px_16px_rgba(0,0,0,0.8)] border-3 border-white parallel-badge"
        :class="owned && count > 1 ? 'top-14' : 'top-2'"
        :style="{
          backgroundColor: currentParallel.color,
          color: '#FFFFFF',
          textShadow: '0 2px 8px rgba(0,0,0,0.8)',
          boxShadow: `0 0 20px ${currentParallel.color}, 0 6px 16px rgba(0,0,0,0.8)`,
        }"
      >
        {{ currentParallel.numeral }}
      </div>

      <!-- Rating Badge for Default Layout -->
      <div class="absolute top-2 right-2 z-10">
        <svg
          width="65"
          height="78"
          viewBox="0 0 100 120"
          class="drop-shadow-[0_4px_8px_rgba(0,0,0,0.3)] transition-transform duration-300 hover:scale-105"
        >
          <defs>
            <linearGradient :id="`gradient-${player.id}`" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" :style="{ stopColor: cardStyle.primary, stopOpacity: 1 }" />
              <stop offset="100%" :style="{ stopColor: cardStyle.secondary, stopOpacity: 1 }" />
            </linearGradient>
            <filter :id="`shadow-${player.id}`" x="-50%" y="-50%" width="200%" height="200%">
              <feDropShadow dx="0" dy="4" stdDeviation="4" flood-opacity="0.4" />
            </filter>
          </defs>

          <!-- Shield/Badge Shape -->
          <path
            d="M 50 5 L 85 20 L 85 70 Q 85 90, 50 110 Q 15 90, 15 70 L 15 20 Z"
            :fill="`url(#gradient-${player.id})`"
            :filter="`url(#shadow-${player.id})`"
            stroke="rgba(255,255,255,0.4)"
            stroke-width="2"
          />

          <!-- Inner glow -->
          <path
            d="M 50 10 L 80 23 L 80 68 Q 80 85, 50 103 Q 20 85, 20 68 L 20 23 Z"
            fill="rgba(255,255,255,0.15)"
          />

          <!-- Rating Number -->
          <text
            x="50"
            y="65"
            text-anchor="middle"
            fill="white"
            font-size="32"
            font-weight="900"
            style="text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5)"
          >
            {{ player.overall_rating }}
          </text>
        </svg>
      </div>

      <!-- Player Image - Center -->
      <div class="flex-1 flex items-end justify-center pt-4 px-2 relative overflow-visible">
        <div class="w-full h-full max-h-[290px] relative flex items-end justify-center">
          <img
            v-if="displayImageUrl && !imageError"
            :src="displayImageUrl"
            :alt="player.name"
            class="w-full h-auto max-h-full object-contain object-bottom drop-shadow-[0_8px_16px_rgba(0,0,0,0.5)] relative z-[2]"
            @error="handleImageError"
          />
          <div
            v-else
            class="w-[250px] h-[250px] bg-white/15 backdrop-blur-[10px] rounded-full flex items-center justify-center text-[6rem] font-black shadow-[3px_3px_8px_rgba(0,0,0,0.5)] border-4 border-white/30"
          >
            {{
              player.name
                .split(" ")
                .map((n) => n[0])
                .join("")
            }}
          </div>
        </div>
      </div>

      <!-- Bottom Section -->
      <div
        class="grid grid-cols-[60px_1fr_60px] items-end py-3 px-3 bg-black/40 backdrop-blur-[10px] border-t-2 border-white/20 relative z-[3] min-h-[100px]"
      >
        <!-- Team Logo - Bottom Left -->
        <div
          class="flex items-center justify-center w-[50px] h-[50px] bg-white/95 rounded-full border-[3px] border-white/40 shadow-[0_4px_12px_rgba(0,0,0,0.3)] p-1.5 relative mb-1"
        >
          <img
            v-if="!logoError"
            :src="getTeamLogoUrl(player.team)"
            :alt="player.team"
            class="w-full h-full object-contain"
            @error="logoError = true"
          />
          <span v-else class="text-[0.75rem] font-black text-[#1a1a1a] tracking-wide">{{
            getTeamAbbr(player.team)
          }}</span>
        </div>

        <!-- Player Name - Bottom Center -->
        <div class="flex flex-col items-center gap-1 px-2 mb-1">
          <div
            class="font-extrabold text-center shadow-[2px_2px_6px_rgba(0,0,0,0.8)] tracking-wide leading-tight uppercase w-full"
            :style="{ fontSize: nameFontSize }"
          >
            {{ player.name }}
          </div>
          <!-- PXP Badge -->
          <div
            v-if="owned && playerXp > 0"
            class="inline-block py-0.5 px-2 bg-yellow-400/30 backdrop-blur-[10px] rounded-xl text-[0.65rem] font-bold shadow-[1px_1px_2px_rgba(0,0,0,0.5)] border border-yellow-400/50 text-center whitespace-nowrap"
          >
            ⭐ {{ playerXp.toLocaleString() }} PXP
          </div>
          <div
            v-if="owned && gamesPlayed > 0"
            class="inline-block py-0.5 px-2 bg-blue-400/30 backdrop-blur-[10px] rounded-xl text-[0.65rem] font-bold shadow-[1px_1px_2px_rgba(0,0,0,0.5)] border border-blue-400/50 text-center whitespace-nowrap"
          >
            🎮 {{ gamesPlayed }} {{ gamesPlayed === 1 ? "Game" : "Games" }}
          </div>
        </div>

        <!-- Position Badge - Bottom Right -->
        <div
          class="flex items-center justify-center w-[50px] h-[50px] rounded-full border-[3px] border-white/40 shadow-[0_4px_12px_rgba(0,0,0,0.3)] relative mb-1"
          :style="{
            background: cardStyle.bg,
          }"
        >
          <span class="text-sm font-black tracking-wide">{{ player.position }}</span>
        </div>
      </div>
    </div>

    <!-- Action Buttons for Owned Cards (shared between both layouts) -->
    <div
      v-if="owned"
      class="absolute bottom-[105px] left-1/2 -translate-x-1/2 z-20 w-[calc(100%-1.5rem)] flex flex-col gap-2"
    >
      <!-- Top Row: Stats and Lock/Unlock -->
      <div class="flex gap-2">
        <!-- Stats Button -->
        <button
          v-if="showStats && userCardId"
          @click="emit('view-stats', userCardId)"
          class="py-2 px-3 text-xs font-bold tracking-wide border-none rounded-lg cursor-pointer transition-all duration-300 text-white backdrop-blur-[10px] bg-brand-gradient shadow-[0_4px_12px_rgba(102,126,234,0.5)] hover:bg-gradient-to-br hover:from-[#5a67d8] hover:to-[#6b46c1] hover:-translate-y-0.5 hover:shadow-[0_6px_16px_rgba(102,126,234,0.7)]"
          :class="showLockButton ? 'flex-1' : 'w-full'"
        >
          📊 STATS
        </button>

        <!-- Lock/Unlock Button -->
        <button
          v-if="showLockButton"
          @click="handleLockToggle"
          class="flex-1 py-2 px-3 text-xs font-bold tracking-wide border-none rounded-lg cursor-pointer transition-all duration-300 text-white shadow-[1px_1px_2px_rgba(0,0,0,0.3)] backdrop-blur-[10px]"
          :class="
            locked
              ? 'bg-gradient-to-br from-orange-400 to-orange-500 shadow-[0_4px_12px_rgba(246,173,85,0.5)] hover:from-orange-500 hover:to-orange-600 hover:-translate-y-0.5 hover:shadow-[0_6px_16px_rgba(246,173,85,0.7)]'
              : 'bg-gradient-to-br from-green-500 to-green-600 shadow-[0_4px_12px_rgba(72,187,120,0.5)] hover:from-green-600 hover:to-green-700 hover:-translate-y-0.5 hover:shadow-[0_6px_16px_rgba(72,187,120,0.7)]'
          "
        >
          <span v-if="locked">🔒 LOCKED</span>
          <span v-else>🔓 LOCK</span>
        </button>
      </div>

      <!-- Bottom Row: Sell Button (shown if unlocked OR if locked but have multiple copies) -->
      <button
        v-if="isTradeable && sellPrice !== null && (!locked || count > 1)"
        @click="emit('sell', userCardId)"
        class="w-full py-2 px-3 text-xs font-bold tracking-wide border-none rounded-lg cursor-pointer transition-all duration-300 text-white backdrop-blur-[10px] bg-gradient-to-br from-red-500 to-red-600 shadow-[0_4px_12px_rgba(239,68,68,0.5)] hover:from-red-600 hover:to-red-700 hover:-translate-y-0.5 hover:shadow-[0_6px_16px_rgba(239,68,68,0.7)]"
      >
        💰 SELL - {{ sellPrice.toLocaleString() }} STUBS
        <span v-if="locked && count > 1" class="text-[0.6rem] opacity-80 block">
          ({{ count - 1 }} available to sell)
        </span>
      </button>

      <!-- Non-tradeable Badge -->
      <div
        v-if="!isTradeable"
        class="w-full py-2 px-3 text-xs font-bold tracking-wide border-none rounded-lg text-white backdrop-blur-[10px] bg-gradient-to-br from-purple-500 to-purple-600 shadow-[0_4px_12px_rgba(168,85,247,0.5)] text-center"
      >
        🏆 REWARD CARD
      </div>
    </div>

    <!-- Buy Button for Unowned Tradeable Cards (shared between both layouts) -->
    <div
      v-if="!owned && isTradeable && buyPrice !== null"
      class="absolute bottom-[105px] left-1/2 -translate-x-1/2 z-20 w-[calc(100%-1.5rem)]"
    >
      <button
        @click="emit('buy', player.id)"
        class="w-full py-2 px-3 text-xs font-bold tracking-wide border-none rounded-lg cursor-pointer transition-all duration-300 text-white backdrop-blur-[10px] bg-gradient-to-br from-green-500 to-green-600 shadow-[0_4px_12px_rgba(34,197,94,0.5)] hover:from-green-600 hover:to-green-700 hover:-translate-y-0.5 hover:shadow-[0_6px_16px_rgba(34,197,94,0.7)]"
      >
        💳 BUY - {{ buyPrice.toLocaleString() }} STUBS
      </button>
    </div>

    <!-- Card Shine Effect (shared between both layouts) -->
    <div class="card-shine"></div>
  </div>
</template>

<style scoped>
/* Card Shine Effect */
.card-shine {
  position: absolute;
  top: -50%;
  left: -50%;
  width: 200%;
  height: 200%;
  background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.1), transparent);
  animation: shine 3s infinite;
  pointer-events: none;
  z-index: 1;
}

@keyframes shine {
  0% {
    transform: translateX(-100%) translateY(-100%) rotate(45deg);
  }
  100% {
    transform: translateX(100%) translateY(100%) rotate(45deg);
  }
}

/* Parallel Card Diagonal Stripes Overlay */
.parallel-stripes {
  position: absolute;
  inset: 0;
  pointer-events: none;
  z-index: 1;
  opacity: 0.15;
  background: repeating-linear-gradient(
    45deg,
    transparent,
    transparent 10px,
    var(--parallel-color) 10px,
    var(--parallel-color) 20px
  );
  border-radius: 1rem;
}

/* Parallel Card Animation */
.parallel-card {
  animation: parallelPulse 2s ease-in-out infinite;
}

@keyframes parallelPulse {
  0%,
  100% {
    filter: brightness(1);
  }
  50% {
    filter: brightness(1.1);
  }
}

/* Parallel Badge Glow Animation */
.parallel-badge {
  animation: badgeGlow 1.5s ease-in-out infinite;
}

@keyframes badgeGlow {
  0%,
  100% {
    transform: scale(1);
  }
  50% {
    transform: scale(1.05);
  }
}
</style>
