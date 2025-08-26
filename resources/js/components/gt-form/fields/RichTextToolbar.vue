<script setup lang="ts">
import type { Editor } from '@tiptap/vue-3'
import {
    Bold,
    Italic,
    List,
    ListOrdered,
    Heading2,
} from 'lucide-vue-next'
// TODO: Assumir Toggle como peer dependency de shadcn-vue ou copiar/recriar 
import { computed } from 'vue'

const props = defineProps<{
    editor: Editor | undefined
}>()

// Map command names to their corresponding `can()` and `action` methods
const commands = computed(() => {
    if (!props.editor) return [];
    return [
        {
            name: 'bold',
            icon: Bold,
            action: () => props.editor?.chain().focus().toggleBold().run(),
            isActive: () => props.editor?.isActive('bold') ?? false,
            can: () => props.editor?.can().toggleBold() ?? false,
        },
        {
            name: 'italic',
            icon: Italic,
            action: () => props.editor?.chain().focus().toggleItalic().run(),
            isActive: () => props.editor?.isActive('italic') ?? false,
            can: () => props.editor?.can().toggleItalic() ?? false,
        },
        {
            name: 'heading2',
            icon: Heading2,
            action: () => props.editor?.chain().focus().toggleHeading({ level: 2 }).run(),
            isActive: () => props.editor?.isActive('heading', { level: 2 }) ?? false,
            can: () => props.editor?.can().toggleHeading({ level: 2 }) ?? false,
        },
        {
            name: 'bulletList',
            icon: List,
            action: () => props.editor?.chain().focus().toggleBulletList().run(),
            isActive: () => props.editor?.isActive('bulletList') ?? false,
            can: () => props.editor?.can().toggleBulletList() ?? false,
        },
        {
            name: 'orderedList',
            icon: ListOrdered,
            action: () => props.editor?.chain().focus().toggleOrderedList().run(),
            isActive: () => props.editor?.isActive('orderedList') ?? false,
            can: () => props.editor?.can().toggleOrderedList() ?? false,
        },
    ];
});

</script>

<template>
    <div v-if="editor" class="toolbar flex flex-wrap gap-1 p-2 bg-muted/50 rounded-t-md">
        <Toggle
            v-for="command in commands"
            :key="command.name"
            size="sm"
            :pressed="command.isActive()"
            @update:pressed="command.action"
            :disabled="!command.can()"
            :aria-label="`Toggle ${command.name}`"
            variant="outline"
        >
             <component :is="command.icon" class="h-4 w-4" />
        </Toggle>
    </div>
</template>