<script setup lang="ts">
import { computed } from 'vue'
import { useFieldArray } from 'vee-validate'
import FormFieldWrapper from '../FormFieldWrapper.vue' 
import { Trash2 } from 'lucide-vue-next'
import type { FieldConfig } from '../DynamicForm.vue'

// Define props
const props = defineProps<{
    field: FieldConfig & {
        subFields: FieldConfig[];
        addButtonLabel?: string;
    };
}>()

const fieldName = computed(() => props.field.name);

// Setup useFieldArray
const { fields, remove, push } = useFieldArray<Record<string, any>>(fieldName);

// Compute default values for a new item
const defaultItemValues = computed(() => {
    const defaults: Record<string, any> = {};
    props.field.subFields.forEach(subField => {
        defaults[subField.name] = subField.defaultValue ?? 
                                  (subField.type === 'number' ? 0 : 
                                  (subField.type === 'boolean' ? false : ''));
    }); 
    return defaults;
});

const addButtonLabel = computed(() => props.field.addButtonLabel || 'Adicionar Item');

const handlePush = () => {
    const newItem = defaultItemValues.value; 
    push(newItem);
}

const handleRemove = (index: number) => { 
     remove(index);
}

</script>

<template>
    <div class="space-y-4">
        <div
            v-for="(itemField, index) in fields"
            :key="itemField.key"
            class="border p-4 rounded-md space-y-3 relative bg-background shadow-sm"
        >
            <Button
                variant="ghost"
                size="icon"
                @click="handleRemove(index)"
                class="absolute top-1 right-1 h-6 w-6 text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                :aria-label="`Remover item ${index + 1}`"
                type="button"
            >
                <Trash2 class="h-4 w-4" />
            </Button>

            <p v-if="props.field.subFields.length > 1" class="text-sm font-medium mb-3">Item {{ index + 1 }}</p>

            <div class="grid grid-cols-12 gap-x-4 gap-y-2 items-start">
                <FormFieldWrapper
                    v-for="subField in props.field.subFields"
                    :key="`${itemField.key}-${subField.name}`"
                    :field="{
                        ...subField,
                        name: `${fieldName}[${index}].${subField.name}`
                    }"
                    :model-value="undefined" 
                    :class="[
                        'col-span-12',
                        subField.colSpan ? `md:col-span-${subField.colSpan}` : 'md:col-span-12'
                    ]"
                 >
                </FormFieldWrapper>
            </div>
        </div>

        <Button
            type="button"
            variant="outline"
            size="sm"
            @click="handlePush"
            class="mt-2"
        >
            {{ addButtonLabel }}
        </Button>
    </div>
</template> 