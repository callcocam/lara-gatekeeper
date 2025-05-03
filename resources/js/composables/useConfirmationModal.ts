import { ref, readonly } from 'vue';

// Estado reativo interno
const isOpen = ref(false);
const title = ref('Confirmar Ação');
const message = ref<string | null>(null);
const confirmText = ref('Confirmar');
const cancelText = ref('Cancelar');

// Placeholders para as ações - serão substituídos ao abrir
let resolvePromise: (value: boolean) => void;

// Função para ABRIR o modal
const openModal = (options: {
    title?: string;
    message?: string;
    confirmText?: string;
    cancelText?: string;
}): Promise<boolean> => {
    title.value = options.title ?? 'Confirmar Ação';
    message.value = options.message ?? null;
    confirmText.value = options.confirmText ?? 'Confirmar';
    cancelText.value = options.cancelText ?? 'Cancelar';
    isOpen.value = true;

    // Retorna uma promessa que será resolvida quando o usuário clicar em um botão
    return new Promise<boolean>((resolve) => {
        resolvePromise = resolve;
    });
};

// Função chamada ao clicar em Confirmar
const confirmAction = () => {
    isOpen.value = false;
    if (resolvePromise) {
        resolvePromise(true); // Resolve a promessa como true (confirmado)
    }
};

// Função chamada ao clicar em Cancelar ou fechar o modal
const cancelAction = () => {
    isOpen.value = false;
    if (resolvePromise) {
        resolvePromise(false); // Resolve a promessa como false (cancelado)
    }
};

// Exportar o composable
export function useConfirmationModal() {
    return {
        isOpen: readonly(isOpen),
        title: readonly(title),
        message: readonly(message),
        confirmText: readonly(confirmText),
        cancelText: readonly(cancelText),
        confirmAction,
        cancelAction,
        openModal,
    };
} 