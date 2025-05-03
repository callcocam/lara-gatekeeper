<script setup lang="ts">
 
import { useConfirmationModal } from '../composables/useConfirmationModal';

const { isOpen, title, message, confirmText, cancelText, confirmAction, cancelAction } = useConfirmationModal();

</script>

<template>
    <AlertDialog :open="isOpen" @update:open="(value: boolean) => !value && cancelAction()"> // Adicionar tipo
        <AlertDialogContent>
            <AlertDialogHeader>
                <AlertDialogTitle>{{ title }}</AlertDialogTitle>
                <AlertDialogDescription v-if="message">
                    {{ message }}
                </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
                <AlertDialogCancel @click="cancelAction">
                    {{ cancelText }}
                </AlertDialogCancel>
                <AlertDialogAction @click="confirmAction">
                    {{ confirmText }}
                </AlertDialogAction>
            </AlertDialogFooter>
        </AlertDialogContent>
    </AlertDialog>
</template> 