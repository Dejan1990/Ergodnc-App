<template>
  <div class="flex">
    <div class="w-1/2 h-80 relative overflow-hidden rounded-lg">
      <img :src="office.images[0].path" class="object-cover w-full h-full" />
    </div>

    <div class="w-full pl-14">
      <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">{{ office.title }}</h1>
        <span class="block font-semibold">${{ office.price_per_day / 100 }} per day</span>
      </div>
      <p class="leading-loose mb-5">
        {{ office.description }}
      </p>
      <button
            type="button"
            class="rounded inline-flex items-center px-4 py-2 bg-purple-700 border border-transparent font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-800 active:bg-purple-900 focus:outline-none focus:border-purple-900 focus:ring ring-purple-300 disabled:opacity-25 transition ease-in-out duration-150"
      >
        Book
      </button>
    </div>
  </div>
</template>

<script>

export default {
  data: () => ({
    office: {
      "images": [
        {
          "path": "https://via.placeholder.com/400x400.png?text=PLACEHOLDER",
        }
      ],
      "id": 1,
      "title": "Office One",
      "description": "Architecto assumenda cum eum. Voluptas qui dignissimos qui voluptate. Mollitia necessitatibus ut sit. Et saepe ea quo nulla.",
      "price_per_day": 1000,
    }
  }),
}
</script>