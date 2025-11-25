<script setup lang="ts">
defineProps<{
  msg: string;
}>();
</script>

<template>
  <div>
    <h1 class="font-medium text-[2.6rem] relative -top-2.5 text-center lg:text-left text-green-600">
      {{ msg }}
    </h1>
    <h3 class="text-xl text-center lg:text-left">
      You've successfully created a project with
      <a href="https://vite.dev/" target="_blank" rel="noopener">Vite</a> +
      <a href="https://vuejs.org/" target="_blank" rel="noopener">Vue 3</a>. What's next?
    </h3>
  </div>
</template>

<style scoped>
/* No additional styles needed - all converted to Tailwind */
</style>
