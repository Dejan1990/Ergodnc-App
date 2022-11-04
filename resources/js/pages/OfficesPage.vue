<template>
    <div>
        <div 
            v-for="(office, index) in offices" 
            :key="office.id"
            :class="`flex ${index + 1 == offices.length ? '' : 'pb-10 mb-10 border-b'}`"
        >
            <div class="w-1/3 h-56 relative overflow-hidden rounded-lg">
                <img :src="office.images[0].path" class="object-cover w-full h-full">
            </div>

            <div class="w-full pl-14">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold">{{ office.title }}</h1>
                    <span class="block font-semibold">${{ office.price_per_day }} per day</span>
                </div>
                <p class="leading-loose mb-5">
                    {{ office.description }}
                </p>
                <router-link 
                    :to="{name: 'Office', params: {id: office.id} }"
                    class="text-purple-600 font-bold"
                >
                    More details...
                </router-link>
            </div>
        </div>
    </div>
</template>
<script>
export default {
    data() {
        return {
            offices: []
        }
    },
    
    created() {
        this.fetch()
    },

    methods: {
        fetch () {
            axios.get('/offices')
                .then(res => {
                    this.offices = res.data.data
                })
                .catch(err => {
                    console.log(err)
                })
        }
    },
}
</script>