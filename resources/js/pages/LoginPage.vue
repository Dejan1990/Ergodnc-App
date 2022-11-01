<template>
  <div class="w-1/2 mx-auto bg-white p-5 rounded-lg">
    <div v-if="errors.length">
        <div class="font-medium text-red-600">
            Whoops! Something went wrong.
        </div>

        <ul class="mt-3 list-disc list-inside text-sm text-red-600">
            <li v-for="(error, i) in errors" :key="i">{error}</li>
        </ul>
  </div>

    <form autoComplete="off">
      <div>
        <label
            class="block text-gray-700">
            Email
        </label>

        <input
          id="email"
          v-model="email"
          type="email"
          class="block mt-1 w-full outline-none border rounded border-gray-200 h-10 px-2"
          required
          auto-focus
          auto-complete="off"
        />
      </div>

      <div class="mt-4">
        <label
            class="block text-gray-700">
            Password
        </label>

        <input
          id="password"
          v-model="password"
          type="password"
          class="block mt-1 w-full outline-none border rounded border-gray-200 h-10 px-2"
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

        <button
            type="button"
            class="rounded inline-flex items-center px-4 py-2 bg-purple-700 border border-transparent font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-800 active:bg-purple-900 focus:outline-none focus:border-purple-900 focus:ring ring-purple-300 disabled:opacity-25 transition ease-in-out duration-150"
            @click="submitform()"
      >
        Login
      </button>
      </div>
    </form>
  </div>
</template>

<script>
export default {
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