<template>
  <div class="mt-8 flex relative lg:mt-0 lg:py-2 lg:pr-0 lg:pl-20">
    <i
      class="flex items-center justify-center w-8 h-8 text-gray-700 lg:absolute lg:top-1/2 lg:-left-[26px] lg:-translate-y-1/2 lg:border lg:border-gray-300 lg:bg-white lg:rounded-lg lg:w-[50px] lg:h-[50px]"
    >
      <slot name="icon"></slot>
    </i>
    <div class="flex-1 ml-4">
      <h3 class="text-xl font-medium mb-1.5 text-gray-900">
        <slot name="heading"></slot>
      </h3>
      <slot></slot>
    </div>
  </div>
</template>

<style scoped>
/* Timeline connectors using pseudo-elements */
@media (min-width: 1024px) {
  .lg\\:py-2:before {
    content: " ";
    @apply border-l border-gray-300 absolute left-0 bottom-[calc(50%+25px)] h-[calc(50%-25px)];
  }

  .lg\\:py-2:after {
    content: " ";
    @apply border-l border-gray-300 absolute left-0 top-[calc(50%+25px)] h-[calc(50%-25px)];
  }

  .lg\\:py-2:first-of-type:before {
    display: none;
  }

  .lg\\:py-2:last-of-type:after {
    display: none;
  }
}
</style>
