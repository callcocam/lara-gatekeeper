<script setup lang="ts">
import { computed, ref } from 'vue'
import { TagsInput, TagsInputInput, TagsInputItem, TagsInputItemDelete, TagsInputItemText } from '@/components/ui/tags-input'

const props = defineProps<{
  id: string;
  field: {
    name: string;
    label?: string;
    description?: string;
    disabled?: boolean;
    required?: boolean;
    placeholder?: string;
    [key: string]: any;
  };
  inputProps?: Record<string, any>;
  error?: string | null | undefined;
}>()

const model = defineModel<string[] | null>()

// Computed para lidar com valores null/undefined para as tags
const modelValueForTags = computed({
  get: () => model.value ?? [],
  set: (value) => {
    console.log(`[Gatekeeper/Tags:${props.field.name}] Setting value:`, value);
    model.value = value && value.length > 0 ? value : null;
  }
});

// Placeholder padrão
const placeholder = computed(() => props.field.placeholder || 'Digite uma tag e pressione Enter')
</script>

<template>
  <div class="space-y-2">
    <GtLabel :field="field" :error="error" :fieldId="props.id" />
    <TagsInput :id="props.id" v-model="modelValueForTags" :disabled="props.field.disabled" v-bind="props.inputProps"
      class="min-h-10">
      <div class="flex flex-wrap gap-2 items-center">
        <TagsInputItem v-for="item in modelValueForTags" :key="item" :value="item"
          class="bg-secondary text-secondary-foreground hover:bg-secondary/80 px-2 py-1 text-sm rounded-md flex items-center gap-1">
          <TagsInputItemText class="text-sm" />
          <TagsInputItemDelete class="ml-1 h-3 w-3 text-muted-foreground hover:text-foreground" />
        </TagsInputItem>
      </div>

      <TagsInputInput :placeholder="placeholder"
        class="flex-1 min-w-0 text-sm bg-transparent border-0 outline-none focus:ring-0 px-1" />
    </TagsInput>
    <GtDescription :description="field.description" :error="props.error" />
    <GtError :id="props.id" :error="props.error" />
  </div>
</template>