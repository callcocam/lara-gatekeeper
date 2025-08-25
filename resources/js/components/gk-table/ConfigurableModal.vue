<template>
  <Dialog :open="isOpen" @update:open="updateOpen">
    <DialogTrigger v-if="$slots.trigger" as-child>
      <slot name="trigger" />
    </DialogTrigger>
    
    <DialogContent 
      :class="cn(
        'bg-background data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 fixed top-[50%] left-[50%] z-50 grid translate-x-[-50%] translate-y-[-50%] gap-4 rounded-lg border p-0 shadow-lg duration-200',
        modalSizeClasses,
        contentClass
      )"
    >
      <!-- Header -->
      <DialogHeader class="px-6 pt-6">
        <DialogTitle v-if="title" :class="titleClass">
          {{ title }}
        </DialogTitle>
        <DialogDescription v-if="description" :class="descriptionClass">
          {{ description }}
        </DialogDescription>
      </DialogHeader>

      <!-- Content -->
      <div :class="cn('px-6', bodyClass)" :style="bodyStyle">
        <slot />
      </div>

      <!-- Footer -->
      <DialogFooter v-if="$slots.footer || showDefaultFooter" :class="cn('px-6 pb-6', footerClass)">
        <slot name="footer">
          <div v-if="showDefaultFooter" class="flex justify-end gap-2">
            <Button
              v-if="showCancelButton"
              variant="outline"
              @click="handleCancel"
              :disabled="loading"
            >
              {{ cancelText }}
            </Button>
            <Button
              v-if="showConfirmButton"
              :variant="confirmVariant"
              @click="handleConfirm"
              :disabled="loading || confirmDisabled"
              :loading="loading"
            >
              {{ confirmText }}
            </Button>
          </div>
        </slot>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { cn } from '@/lib/utils'
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'

export interface ConfigurableModalProps {
  // Estado do modal
  open?: boolean
  
  // Conteúdo
  title?: string
  description?: string
  
  // Tamanhos predefinidos
  size?: 'xs' | 'sm' | 'md' | 'lg' | 'xl' | '2xl' | '3xl' | '4xl' | '5xl' | 'full'
  
  // Dimensões customizadas
  width?: string
  height?: string
  maxWidth?: string
  maxHeight?: string
  
  // Classes customizadas
  contentClass?: string
  titleClass?: string
  descriptionClass?: string
  bodyClass?: string
  footerClass?: string
  
  // Footer padrão
  showDefaultFooter?: boolean
  showCancelButton?: boolean
  showConfirmButton?: boolean
  cancelText?: string
  confirmText?: string
  confirmVariant?: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link'
  confirmDisabled?: boolean
  loading?: boolean
}

const props = withDefaults(defineProps<ConfigurableModalProps>(), {
  size: 'md',
  showDefaultFooter: false,
  showCancelButton: true,
  showConfirmButton: true,
  cancelText: 'Cancelar',
  confirmText: 'Confirmar',
  confirmVariant: 'default',
  confirmDisabled: false,
  loading: false,
})

const emit = defineEmits<{
  'update:open': [value: boolean]
  'confirm': []
  'cancel': []
}>()

const isOpen = computed({
  get: () => props.open ?? false,
  set: (value) => emit('update:open', value)
})

const updateOpen = (value: boolean) => {
  emit('update:open', value)
}

const modalSizeClasses = computed(() => {
  if (props.width || props.height || props.maxWidth || props.maxHeight) {
    return '' // Será aplicado via style
  }

  const sizeMap = {
    xs: 'max-w-xs',
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-xl',
    '2xl': 'max-w-2xl',
    '3xl': 'max-w-3xl',
    '4xl': 'max-w-4xl',
    '5xl': 'max-w-5xl',
    full: 'max-w-full w-[calc(100vw-2rem)] h-[calc(100vh-2rem)]'
  }

  return sizeMap[props.size] || sizeMap.md
})

const bodyStyle = computed(() => {
  const styles: Record<string, string> = {}
  
  if (props.width) styles.width = props.width
  if (props.height) styles.height = props.height
  if (props.maxWidth) styles.maxWidth = props.maxWidth
  if (props.maxHeight) styles.maxHeight = props.maxHeight
  
  return styles
})

const handleCancel = () => {
  emit('cancel')
  emit('update:open', false)
}

const handleConfirm = () => {
  emit('confirm')
}
</script>
