<template>
    <div>
        <Button @click="handleClick" class="px-3 py-1.5 bg-blue-600 text-white rounded hover:bg-blue-700">
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
import ConfirmModal from './ConfirmModal.vue';

interface ActionProps {
    action: {
        id: string;
        label: string;
        icon?: string;
        color?: string;
        routeName?: string;
        iconPosition?: string;
        confirm?: {
            title: string;
            description: string;
        };
    };
}

const props = defineProps<ActionProps>();
const emit = defineEmits<{
    click: [];
}>();

const showConfirmModal = ref(false);

const handleClick = () => {
    // Se a ação tem confirmação, mostra o modal
    if (props.action.confirm) {
        showConfirmModal.value = true;
    } else {
        // Executa a ação diretamente
        emit('click');
    }
};

const confirmAction = () => {
    showConfirmModal.value = false;
    emit('click');
};

const cancelAction = () => {
    showConfirmModal.value = false;
};
</script>