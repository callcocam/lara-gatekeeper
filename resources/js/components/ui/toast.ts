import { ref, watch } from 'vue'

interface ToastOptions {
  title?: string
  description?: string
  type?: 'default' | 'success' | 'error' | 'warning' | 'info'
  position?: 'top-right' | 'top-left' | 'bottom-right' | 'bottom-left'
  duration?: number
  action?: {
    label: string
    onClick: () => void
  }
}

interface Toast extends ToastOptions {
  id: number
  visible: boolean
}

const toasts = ref<Toast[]>([])
let idCounter = 0

// Configurações padrão
const defaultOptions: Partial<ToastOptions> = {
  type: 'default',
  position: 'top-right',
  duration: 5000,
}

// Função principal para criar o toast
export function toast(options: ToastOptions) {
  const id = ++idCounter
  const newToast: Toast = {
    ...defaultOptions,
    ...options,
    id,
    visible: true,
  }

  toasts.value.push(newToast)

  // Auto-fechamento após a duração definida
  if (newToast.duration) {
    setTimeout(() => {
      dismiss(id)
    }, newToast.duration)
  }

  return id
}

// Função para fechar um toast específico
export function dismiss(id: number) {
  const index = toasts.value.findIndex(toast => toast.id === id)
  if (index !== -1) {
    toasts.value[index].visible = false
    setTimeout(() => {
      toasts.value = toasts.value.filter(toast => toast.id !== id)
    }, 300) // espera a animação de saída acabar
  }
}

// Função para fechar todos os toasts
export function dismissAll() {
  toasts.value.forEach(toast => {
    toast.visible = false
  })
  setTimeout(() => {
    toasts.value = []
  }, 300)
}

export function useToast() {
  return {
    toasts,
    toast,
    dismiss,
    dismissAll
  }
}

export default useToast 