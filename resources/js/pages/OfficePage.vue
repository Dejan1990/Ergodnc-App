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
      <ButtonComponent class="mt-7">Book</ButtonComponent>
    </div>
  </div>
</template>

<script>
import ButtonComponent from '@/components/ButtonComponent.vue';

export default {
  components: {ButtonComponent},
  
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