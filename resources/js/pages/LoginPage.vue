<template>
  <div class="w-1/2 mx-auto bg-white p-5 rounded-lg">

    <ErrorsComponent class="mb-5" :errors="errors"></ErrorsComponent>

    <form autoComplete="off">
      <div>
        <LabelComponent>Email</LabelComponent>

        <InputComponent
          id="email"
          v-model="email"
          type="email"
          class="block mt-1 w-full"
          required
          auto-focus
          auto-complete="off"
        />
      </div>

      <div class="mt-4">
        <LabelComponent>Password</LabelComponent>

        <InputComponent
          id="password"
          v-model="password"
          type="password"
          class="block mt-1 w-full"
          required
          auto-complete="current-password"
        />
      </div>

      <div class="block mt-4">
        <label
          htmlFor="remember_me"
          class="inline-flex items-center">
          <input
            id="remember_me"
            v-model="remember"
            type="checkbox"
            name="remember"
            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
          />

          <span class="ml-2 text-gray-600">Remember me</span>
        </label>
      </div>

      <div class="flex items-center justify-end mt-4">
        <router-link to="/forgot-password" class="underline text-sm text-gray-600 hover:text-gray-900">
          Forgot your password?
        </router-link>

        <ButtonComponent 
          class="ml-3"
          @click="submitForm()"
        >
          Login
        </ButtonComponent>
      </div>
    </form>
  </div>
</template>

<script>
import ButtonComponent from '@/components/ButtonComponent.vue';
import ErrorsComponent from '@/components/ErrorsComponent.vue';
import LabelComponent from '@/components/LabelComponent.vue';
import InputComponent from '@/components/InputComponent.vue';

export default {
  components: {
    ButtonComponent, 
    ErrorsComponent,
    LabelComponent,
    InputComponent
  },

  data: () => ({
    errors: [],
    email: '',
    password: '',
    remember: false
  }),

  methods: {
    submitForm(event) {
      event.preventDefault()
    }
  }
}
</script>