<template>
    <Label v-if="!props.field?.hideLabel" :for="fieldId" :class="cn(props.error && 'text-destructive')">
        {{ field.label }}
        <span v-if="field.required" class="text-destructive"> *</span>
    </Label>
</template>
<script setup lang="ts">
import { Label } from '@/components/ui/label';
import { FieldConfig } from '../../types/field';
import { cn } from '../../lib/utils';

const props = defineProps<{
    field: FieldConfig;
    error?: string;
    fieldId: string;
}>()
</script>