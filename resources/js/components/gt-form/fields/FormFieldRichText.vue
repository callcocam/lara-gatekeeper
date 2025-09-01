<script setup lang="ts">
import { watch, onBeforeUnmount, computed } from 'vue'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import { cn } from '../../../lib/utils'
import RichTextToolbar from './RichTextToolbar.vue'
import { FieldConfig } from '../../../types/field';

// Define props
const props = defineProps<{
    id: string;
    field: FieldConfig;
    inputProps?: { placeholder?: string; [key: string]: any };
    error?: string | null | undefined;
}>()

const model = defineModel<string | null>()

const placeholder = computed(() => props.inputProps?.placeholder || 'Digite seu texto aqui...');

const editor = useEditor({
    content: model.value || '',
    extensions: [
        StarterKit.configure({}),
    ],
    editorProps: {
        attributes: {
            class: cn(
                'min-h-[120px] max-h-[400px] overflow-y-auto w-full rounded-b-md border-t border-input bg-background px-3 py-2 text-sm ring-offset-background',
                'focus-visible:outline-none',
                'prose prose-sm max-w-none'
            ),
            placeholder: placeholder.value,
        },
    },
    onUpdate: ({ editor }) => {
        const html = editor.getHTML();
        const newModelValue = html === '<p></p>' ? null : html;
        if (model.value !== newModelValue) {
            console.log(`[Gatekeeper/RichText:${props.field.name}] Updating model from editor.`);
            model.value = newModelValue;
        }
    },
})

// Watch for external changes to modelValue and update editor
watch(model, (newValue) => {
    if (!editor.value) return;
    const currentContent = editor.value.getHTML();
    const newContent = newValue === '<p></p>' ? '' : newValue || '';
    
    if (currentContent !== newContent) {
        console.log(`[Gatekeeper/RichText:${props.field.name}] Updating editor from model.`);
        editor.value.chain().setContent(newContent, false).run();
    }
}, { immediate: false })

// Destroy the editor instance when the component is unmounted
onBeforeUnmount(() => {
    if (editor.value) {
      console.log(`[Gatekeeper/RichText:${props.field.name}] Destroying Tiptap editor.`);
      editor.value.destroy()
    }
})

</script>

<template>
    <div 
        v-if="editor"
        class="rounded-md border border-input focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2 bg-background"
    >
         <RichTextToolbar :editor="editor" :id="props.id" :error="props.error" :field="field" />
         <EditorContent :editor="editor" />
    </div>
    <div v-else class="text-destructive text-sm">Editor não inicializado.</div>
</template> 