<template>
  <footer class="bg-white border-t border-gray-200 px-6 py-4">
    <div class="flex flex-col md:flex-row justify-between items-center text-sm text-gray-600">
      
      <p>
        &copy; {{ currentYear }} City Gymnasium. All rights reserved.
      </p>

      <div class="flex space-x-4 mt-2 md:mt-0">
        <a
          v-for="link in links"
          :key="link.label"
          :href="link.href"
          class="hover:text-blue-600 transition-colors"
        >
          {{ link.label }}
        </a>
      </div>

    </div>
  </footer>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
  links: {
    type: Array,
    default: () => [
      { label: 'Privacy Policy', href: '#' },
      { label: 'Terms of Service', href: '#' },
      { label: 'Support', href: '#' }
    ]
  }
})

const currentYear = computed(() => new Date().getFullYear())
</script>