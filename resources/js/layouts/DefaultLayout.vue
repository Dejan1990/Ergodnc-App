<template>
  <div>
    <div class="bg-white">
      <main class="shadow">
        <div class="max-w-screen-lg mx-auto flex flex-row justify-between items-center p-4 sm:p-7 relative z-10 space-x-2 sm:space-x-3">
          <router-link to="/" class="block">
            <h1 class="text-2xl font-black"><span class="text-purple-700">ergo</span>dnc
            </h1>
          </router-link>

          <div class="flex items-center text-sm font-semibold">
            <router-link to="/login" class="text-gray-700 hover:text-purple-700">
              Sign In
            </router-link>
            <router-link to="/register" class="text-gray-700 ml-7 border border-gray-300 hover:border-purple-400 hover:text-purple-700 rounded px-4 py-2">
              Create Account
            </router-link>
          </div>
        </div>
      </main>
    </div>

    <div class="max-w-screen-lg mx-auto p-4 sm:p-7 mt-10">
      <router-view></router-view>
    </div>
  </div>
</template>