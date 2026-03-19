<template>
  <div class="min-h-screen flex flex-col bg-gradient-to-br from-blue-500 via-purple-500 to-pink-500">
    <header class="bg-white shadow-md">
      <div class="container mx-auto px-4 py-5">
        <h1 class="text-3xl md:text-4xl font-bold text-gray-800 text-center flex items-center justify-center gap-3">
          <i class="fas fa-dumbbell text-blue-600"></i>
          <span>City Gymnasium Booking System</span>
        </h1>
      </div>
    </header>

    <main class="flex-1 flex items-center justify-center px-4 py-8">
      <div class="w-full max-w-lg">
        <router-view />
      </div>
    </main>

    <footer class="bg-white shadow-md">
      <div class="container mx-auto px-4 py-4 text-center text-gray-600 text-sm">
        <p>&copy; {{ year }} City Gymnasium. All rights reserved.</p>
      </div>
    </footer>
  </div>
</template>

<script setup>
const year = new Date().getFullYear()
</script>