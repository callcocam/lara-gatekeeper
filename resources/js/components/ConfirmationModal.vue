<script setup lang="ts">
 
import { useConfirmationModal } from '../composables/useConfirmationModal';
import { AlertDialog, AlertDialogContent, AlertDialogHeader, AlertDialogTitle, AlertDialogDescription, AlertDialogFooter, AlertDialogCancel, AlertDialogAction } from '@/components/ui/alert-dialog';

const { isOpen, title, message, confirmText, cancelText, confirmAction, cancelAction } = useConfirmationModal();

</script>

<template>
    <AlertDialog :open="isOpen">
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