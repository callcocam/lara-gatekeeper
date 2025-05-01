<script setup lang="ts">
import { ref, computed, watch, onBeforeUnmount } from 'vue'
import { useDropZone } from '@vueuse/core'
import { cn } from '../../lib/utils'
import { Button } from 'shadcn-vue'
import { UploadCloud, X, File as FileIcon, Image as ImageIcon } from 'lucide-vue-next'

// Define props
const props = defineProps<{
    id: string;
    field: {
        name: string;
        type: 'file' | 'image';
        accept?: string;
        multiple?: boolean;
        [key: string]: any;
    };
    inputProps?: { placeholder?: string; [key: string]: any };
}>()

// Use defineModel for v-model binding
const model = defineModel<File[] | File | null>()

const dropZoneRef = ref<HTMLDivElement>()
const fileInputRef = ref<HTMLInputElement>()
const selectedFiles = ref<File[]>([])
const previewUrls = ref<Record<string, string>>({})

const placeholder = computed(() => props.inputProps?.placeholder || (props.field.multiple ? 'Arraste arquivos ou clique para selecionar' : 'Arraste um arquivo ou clique para selecionar'));
const acceptAttr = computed(() => props.field.accept || (props.field.type === 'image' ? 'image/*' : undefined))
const isImageType = computed(() => props.field.type === 'image')

// --- File Handling Logic ---
function addFiles(filesToAdd: File[]) {
    const validFiles = filesToAdd.filter(file => {
        if (acceptAttr.value) {
            const acceptedTypes = acceptAttr.value.split(',').map(t => t.trim().toLowerCase());
            const fileType = file.type.toLowerCase();
            const fileExtension = '.' + file.name.split('.').pop()?.toLowerCase();

            const match = acceptedTypes.some(acceptedType => {
                if (acceptedType.startsWith('.')) {
                    return fileExtension === acceptedType;
                } else if (acceptedType.endsWith('/*')) {
                    return fileType.startsWith(acceptedType.slice(0, -2));
                } else {
                    return fileType === acceptedType;
                }
            });
            if (!match) {
                console.warn(`[Gatekeeper/File:${props.field.name}] Arquivo "${file.name}" ignorado: tipo não permitido.`);
                return false;
            }
        }
        return true;
    });

    if (props.field.multiple) {
        const newFilesToAdd = validFiles.filter(vf => !selectedFiles.value.some(sf => sf.name === vf.name && sf.size === vf.size));
        selectedFiles.value = [...selectedFiles.value, ...newFilesToAdd];
        console.log(`[Gatekeeper/File:${props.field.name}] Added ${newFilesToAdd.length} valid files (multiple). Total: ${selectedFiles.value.length}`);
    } else {
        selectedFiles.value = validFiles.slice(0, 1);
        console.log(`[Gatekeeper/File:${props.field.name}] Added ${validFiles.length > 0 ? 1 : 0} valid file (single).`);
    }
    updateModel();
    generatePreviews();
}

function removeFile(index: number) {
    const removedFile = selectedFiles.value[index];
    if (!removedFile) return;

    console.log(`[Gatekeeper/File:${props.field.name}] Removing file: "${removedFile.name}"`);
    if (previewUrls.value[removedFile.name]) {
        URL.revokeObjectURL(previewUrls.value[removedFile.name]);
        delete previewUrls.value[removedFile.name];
    }
    selectedFiles.value.splice(index, 1);
    updateModel();
}

function updateModel() {
    if (!props.field.multiple) {
        const fileToEmit = selectedFiles.value[0] || null;
        if (model.value !== fileToEmit) {
            console.log(`[Gatekeeper/File:${props.field.name}] Updating model (single):`, fileToEmit?.name ?? null);
            model.value = fileToEmit;
        }
    } else {
        const filesToEmit = selectedFiles.value.length > 0 ? selectedFiles.value : null;
        if (JSON.stringify(model.value) !== JSON.stringify(filesToEmit)) {
            console.log(`[Gatekeeper/File:${props.field.name}] Updating model (multiple):`, filesToEmit?.map(f => f.name) ?? null);
            model.value = filesToEmit;
        }
    }
}

// --- Preview Generation ---
function generatePreviews() {
    if (!isImageType.value) return;

    Object.keys(previewUrls.value).forEach(filename => {
        if (!selectedFiles.value.some(file => file.name === filename)) {
            console.log(`[Gatekeeper/File:${props.field.name}] Revoking preview URL for: ${filename}`);
            URL.revokeObjectURL(previewUrls.value[filename]);
            delete previewUrls.value[filename];
        }
    });

    selectedFiles.value.forEach(file => {
        if (file.type.startsWith('image/') && !previewUrls.value[file.name]) {
            previewUrls.value[file.name] = URL.createObjectURL(file);
            console.log(`[Gatekeeper/File:${props.field.name}] Created preview URL for: ${file.name}`);
        }
    });
}

// Revoke all URLs on unmount
onBeforeUnmount(() => {
    console.log(`[Gatekeeper/File:${props.field.name}] Unmounting, revoking all preview URLs.`);
    Object.values(previewUrls.value).forEach(url => URL.revokeObjectURL(url));
    previewUrls.value = {};
});

// --- Drop Zone Setup ---
function onDrop(droppedFiles: File[] | null) {
    if (droppedFiles) {
        console.log(`[Gatekeeper/File:${props.field.name}] Files dropped: ${droppedFiles.length}`);
        addFiles(droppedFiles);
    }
}

const { isOverDropZone } = useDropZone(dropZoneRef, { onDrop, dataTypes: ['Files'] })

// --- Input Click Handling ---
function openFileInput() {
    fileInputRef.value?.click();
}

function onFileInputChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files) {
        console.log(`[Gatekeeper/File:${props.field.name}] Files selected via input: ${target.files.length}`);
        addFiles(Array.from(target.files));
    }
    target.value = '';
}

// --- Sync with external model changes ---
watch(model, (newModelValue, oldModelValue) => {
    if ((newModelValue === null || newModelValue === undefined) && selectedFiles.value.length > 0) {
        console.log(`[Gatekeeper/File:${props.field.name}] Model cleared externally, clearing selected files.`);
        selectedFiles.value = [];
        Object.values(previewUrls.value).forEach(url => URL.revokeObjectURL(url));
        previewUrls.value = {};
        return;
    }
    
    let incomingFiles: File[] = [];
    if (Array.isArray(newModelValue)) {
        incomingFiles = newModelValue;
    } else if (newModelValue instanceof File) {
        incomingFiles = [newModelValue];
    }

    const currentFileIds = selectedFiles.value.map(f => `${f.name}-${f.size}`).sort().join(',');
    const incomingFileIds = incomingFiles.map(f => `${f.name}-${f.size}`).sort().join(',');

    if (currentFileIds !== incomingFileIds) {
        console.log(`[Gatekeeper/File:${props.field.name}] Model changed externally, attempting to sync selectedFiles.`);
        selectedFiles.value = incomingFiles;
        generatePreviews();
    }

}, { deep: true });

</script>

<template>
    <div class="space-y-3">
        <div
            ref="dropZoneRef"
            @click="openFileInput"
            @dragenter.prevent
            @dragover.prevent
            :class="cn(
                'flex flex-col items-center justify-center w-full min-h-[150px] border-2 border-dashed rounded-lg cursor-pointer transition-colors',
                'border-input hover:border-primary/50',
                isOverDropZone ? 'border-primary bg-primary/10' : 'bg-muted/25 hover:bg-muted/50'
            )"
            role="button"
            tabindex="0"
            @keydown.enter.space="openFileInput"
        >
            <input
                ref="fileInputRef"
                type="file"
                :accept="acceptAttr"
                :multiple="props.field.multiple"
                @change="onFileInputChange"
                class="hidden"
                :id="props.id"
            />
            <div class="flex flex-col items-center justify-center pt-5 pb-6 text-center px-4 pointer-events-none">
                <UploadCloud :class="cn('w-10 h-10 mb-4', isOverDropZone ? 'text-primary' : 'text-muted-foreground')" />
                <p :class="cn('mb-2 text-sm', isOverDropZone ? 'text-primary' : 'text-muted-foreground')">
                    <span class="font-semibold">{{ placeholder }}</span>
                </p>
                <p v-if="acceptAttr" class="text-xs text-muted-foreground">Tipos aceitos: {{ acceptAttr }}</p>
            </div>
        </div>

        <!-- Selected Files List / Previews -->
        <div v-if="selectedFiles.length > 0" class="space-y-2">
            <p class="text-sm font-medium">Arquivos Selecionados:</p>
            <ul class="space-y-2">
                <li
                    v-for="(file, index) in selectedFiles"
                    :key="file.name + '-' + index + '-' + file.lastModified" 
                    class="flex items-center justify-between p-2 border rounded-md bg-muted/50 text-sm hover:bg-muted/75 transition-colors"
                >
                    <div class="flex items-center gap-2 overflow-hidden mr-2">
                        <img
                            v-if="isImageType && previewUrls[file.name]"
                            :src="previewUrls[file.name]"
                            :alt="`Preview ${file.name}`"
                            class="h-10 w-10 rounded object-cover shrink-0 border"
                        />
                        <ImageIcon v-else-if="isImageType" class="h-6 w-6 text-muted-foreground shrink-0" />
                        <FileIcon v-else class="h-6 w-6 text-muted-foreground shrink-0" />
                        
                        <span class="truncate flex-1" :title="file.name">{{ file.name }}</span>
                        <span class="text-xs text-muted-foreground whitespace-nowrap">({{ (file.size / 1024).toFixed(1) }} KB)</span>
                    </div>
                    <Button variant="ghost" size="icon" @click.stop="removeFile(index)" class="h-7 w-7 shrink-0">
                        <X class="h-4 w-4" />
                        <span class="sr-only">Remover {{ file.name }}</span>
                    </Button>
                </li>
            </ul>
        </div>
    </div>
</template> 