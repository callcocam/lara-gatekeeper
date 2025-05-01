<script setup lang="ts">
import { useToast } from './toast'
import { TransitionGroup } from 'vue'
import { XIcon } from 'lucide-vue-next'
import { cn } from '@/lib/utils'

const { toasts, dismiss } = useToast()

// Função para determinar as classes de posição
function getPositionClasses(position: string | undefined) {
  switch (position) {
    case 'top-left':
      return 'top-4 left-4'
    case 'top-right':
      return 'top-4 right-4'
    case 'bottom-left':
      return 'bottom-4 left-4'
    case 'bottom-right':
      return 'bottom-4 right-4'
    default:
      return 'top-4 right-4' // padrão
  }
}

// Função para determinar as classes de tipo
function getTypeClasses(type: string | undefined) {
  switch (type) {
    case 'success':
      return 'bg-green-50 border-green-500 text-green-700'
    case 'error':
      return 'bg-red-50 border-red-500 text-red-700'
    case 'warning':
      return 'bg-yellow-50 border-yellow-500 text-yellow-700'
    case 'info':
      return 'bg-blue-50 border-blue-500 text-blue-700'
    default:
      return 'bg-gray-50 border-gray-500 text-gray-700'
  }
}
</script>

<template>
  <div class="fixed inset-0 pointer-events-none flex flex-col p-4 z-50">
    <TransitionGroup 
      name="toast"
      tag="div"
      class="relative w-full"
    >
      <div 
        v-for="toast in toasts" 
        :key="toast.id"
        :class="[
          'pointer-events-auto max-w-sm w-full absolute shadow-lg rounded-lg border p-4 mb-4 transform transition-all duration-300',
          getPositionClasses(toast.position),
          getTypeClasses(toast.type),
          toast.visible ? 'opacity-100' : 'opacity-0 scale-95'
        ]"
      >
        <div class="flex justify-between items-start">
          <div>
            <h3 v-if="toast.title" class="font-medium">{{ toast.title }}</h3>
            <p v-if="toast.description" class="text-sm">{{ toast.description }}</p>
          </div>
          <button 
            @click="dismiss(toast.id)" 
            class="ml-4 bg-transparent text-current p-1 rounded-full hover:bg-gray-200/50"
          >
            <XIcon class="h-4 w-4" />
          </button>
        </div>
        <div v-if="toast.action" class="mt-2">
          <button 
            @click="toast.action.onClick()" 
            class="text-sm font-medium underline hover:text-opacity-80"
          >
            {{ toast.action.label }}
          </button>
        </div>
      </div>
    </TransitionGroup>
  </div>
</template>

<style>
.toast-enter-active,
.toast-leave-active {
  transition: all 0.3s ease;
}

.toast-enter-from {
  opacity: 0;
  transform: translateY(-20px);
}

.toast-leave-to {
  opacity: 0;
  transform: scale(0.95);
}
</style> 