<template>
    <fieldset class="mercadologico-selector border border-input rounded-md px-4 pb-2 dark:border-gray-700 relative">
        <legend class="text-lg font-semibold text-gray-800 dark:text-gray-200">
            {{ field.label }}
        </legend>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2" v-if="field.description">{{ field.description }}</p>
    </fieldset>
</template>
<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { FieldConfig } from '../../../../types/field';

const props = defineProps<{
    field: FieldConfig;
    error?: string;
    modelValue: any;
}>()

const safeModel = ref(props.modelValue);

onMounted(() => {
    console.log(props.field);
})
</script>