<template>
    <div>
        <Button @click="handleClick" variant="ghost" class="flex items-center gap-2 !text-white border-none shadow-none"
            :style="{ background: 'linear-gradient(to right, #1e293b, #a8ff3e)', color: 'white' }">
            <template v-if="action.icon && action.iconPosition === 'left'">
                <Icon :name="action.icon" />
            </template>
            <span>{{ action.label }}</span>
            <template v-if="action.icon && action.iconPosition === 'right'">
                <Icon :name="action.icon" />
            </template>
        </Button>

        <!-- Modal de Confirmação -->
        <ConfirmModal :show="showConfirmModal" :title="action.confirm?.title || 'Ação Personalizada'"
            :description="action.confirm?.description || 'Você tem certeza que deseja realizar esta ação personalizada?'"
            confirm-text="Sim, fazer isso" cancel-text="Não, cancelar" variant="warning" @confirm="confirmAction"
            @cancel="cancelAction" />
    </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/vue3';
import ConfirmModal from './ConfirmModal.vue';

interface ActionProps {
    action: {
        id: string;
        label: string;
        icon?: string;
        color?: string;
        url: string;
        iconPosition?: string;
        confirm?: {
            title: string;
            description: string;
        };
    };
}

const props = defineProps<ActionProps>();

const showConfirmModal = ref(false);

const handleClick = () => {
    // Se a ação tem confirmação, mostra o modal
    if (props.action.confirm) {
        showConfirmModal.value = true;
    } else {
        // Navega diretamente
        router.visit(props.action.url);
    }
};

const confirmAction = () => {
    showConfirmModal.value = false;
    router.visit(props.action.url);
};

const cancelAction = () => {
    showConfirmModal.value = false;
};
</script>