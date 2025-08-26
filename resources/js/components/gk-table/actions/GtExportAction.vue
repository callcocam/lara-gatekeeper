<template>
    <GtConfirmModal :action="action" @confirm="handleClick" />
</template>

<script setup lang="ts">
import { ActionProps } from '../../../types/field';
import GtConfirmModal from './GtConfirmModal.vue';

defineProps<ActionProps>();
const emit = defineEmits<{
    click: [];
}>();

const handleClick = () => {
    emit('click');
};
console.log('Export action clicked');
</script>