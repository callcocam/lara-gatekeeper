<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="show" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
                @click="handleBackdropClick">
                <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 shadow-xl transform transition-all"
                    @click.stop>
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">
                            {{ title }}
                        </h3>
                        <button @click="cancel" class="text-gray-400 hover:text-gray-600">
                            <Icon name="x" class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Content -->
                    <div class="mb-6">
                        <p class="text-gray-600">
                            {{ description }}
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3">
                        <Button @click="cancel" variant="outline" class="px-4 py-2">
                            {{ cancelText }}
                        </Button>
                        <Button @click="confirm" :class="[
                            'px-4 py-2',
                            variant === 'danger' ? 'bg-red-600 text-white hover:bg-red-700' :
                                variant === 'warning' ? 'bg-yellow-600 text-white hover:bg-yellow-700' :
                                    'bg-blue-600 text-white hover:bg-blue-700'
                        ]">
                            {{ confirmText }}
                        </Button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';

interface Props {
    show: boolean;
    title: string;
    description: string;
    confirmText?: string;
    cancelText?: string;
    variant?: 'danger' | 'warning' | 'default';
    closeOnBackdrop?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    confirmText: 'Confirmar',
    cancelText: 'Cancelar',
    variant: 'danger',
    closeOnBackdrop: true
});

const emit = defineEmits<{
    confirm: [];
    cancel: [];
}>();

const confirm = () => {
    emit('confirm');
};

const cancel = () => {
    emit('cancel');
};

const handleBackdropClick = () => {
    if (props.closeOnBackdrop) {
        cancel();
    }
};
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-active .bg-white,
.modal-leave-active .bg-white {
    transition: transform 0.3s ease;
}

.modal-enter-from .bg-white,
.modal-leave-to .bg-white {
    transform: scale(0.9);
}
</style>