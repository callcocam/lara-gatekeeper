<template>
    <GtConfirmModal :action="action" :callback="handleClick" class="p-0" />
</template>

<script setup lang="ts">
import { ActionProps } from '../../../types/field';
import GtConfirmModal from './GtConfirmModal.vue';

defineProps<ActionProps>();

const handleClick = (action: any) => {
    window.location.href = action?.url;
}; 
</script>