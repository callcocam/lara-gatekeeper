<script setup lang="ts">
import { ref, computed, watch, watchEffect } from 'vue';

// Import Vue FilePond
// @ts-ignore
import vueFilePond from 'vue-filepond';

// Import FilePond styles
import 'filepond/dist/filepond.min.css';

// Import FilePond plugins
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';
import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css';
// TODO: Add image edit plugin later
// import FilePondPluginImageEdit from 'filepond-plugin-image-edit';
// import 'filepond-plugin-image-edit/dist/filepond-plugin-image-edit.css';


// Define props
const props = defineProps<{
    id: string;
    field: {
        name: string;
        key: string;
        type: 'filepond'; // Specific type for this component
        accept?: string;
        multiple?: boolean;
        labelIdle?: string; // Custom label
        // Add other FilePond options via field if needed
        filepondOptions?: Record<string, any>;
        [key: string]: any;
    };
    inputProps?: { [key: string]: any }; // General input props, might not be very relevant here
}>();

// Use defineModel for v-model binding (can receive URL string or File object)
const model = defineModel<string | File | File[] | null>();


// Create FilePond component instance
const FilePond = vueFilePond(
    FilePondPluginFileValidateType,
    FilePondPluginImagePreview
    // FilePondPluginImageEdit // Register later
);

const pond = ref<any>(null); // Ref for FilePond instance

// --- FilePond Configuration ---

const initialFiles = computed(() => {
    const relativePath = model.value; // Assume model.value is the relative path from backend

    if (typeof relativePath === 'string' && relativePath) {
        console.log(`[FormFieldFilePond:${props.field.name}] Setting initial file from relative path:`, relativePath);
        // FilePond expects an object for existing files when using server.load
        return [{
            source: relativePath, // Use the relative path directly as the source ID for server.load
            options: { type: 'local' } // Explicitly mark as local file for server interaction
        }];
    }
    // console.log(`[FormFieldFilePond:${props.field.name}] No valid initial relative path provided or model is not a string.`);
    return [];
});

// DEBUG: Watch model and initialFiles
watchEffect(() => {
    console.log(`[FormFieldFilePond DEBUG - ${props.field.key}] model.value:`, model.value);
    console.log(`[FormFieldFilePond DEBUG - ${props.field.key}] initialFiles.value:`, JSON.stringify(initialFiles.value));
});
// --- Server Configuration for Loading Initial Files ---
const serverOptions = computed(() => ({
    load: (source: string, load: Function, error: Function, progress: Function, abort: Function, headers: Function) => {
        // source should be the relative path (e.g., 'avatars/...')
        console.log(`[FormFieldFilePond:${props.field.name}] server.load called with source:`, source);
        const request = new XMLHttpRequest();
        const loadUrl = `/filepond/load?path=${encodeURIComponent(source)}`; // Endpoint Laravel
        console.log('loadUrl', loadUrl);
        request.open('GET', loadUrl);
        request.responseType = 'blob';

        request.onload = function() {
            if (request.status >= 200 && request.status < 300) {
                console.log(`[FormFieldFilePond:${props.field.name}] File loaded successfully from server.`);
                load(request.response); // Success: pass blob to FilePond
            } else {
                console.error(`[FormFieldFilePond:${props.field.name}] Error loading file from server: ${request.status} ${request.statusText}`);
                error(`Erro ${request.status} ao carregar arquivo`);
            }
        };
        request.onerror = () => {
             console.error(`[FormFieldFilePond:${props.field.name}] Network error during server.load.`);
             error('Erro de rede ao carregar arquivo');
        }
        request.onabort = () => {
             console.log(`[FormFieldFilePond:${props.field.name}] server.load aborted.`);
             abort();
        }
        
        // TODO: Set CSRF token header if your endpoint is protected
        // const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        // if (csrfToken) {
        //    request.setRequestHeader('X-CSRF-TOKEN', csrfToken);
        // }
        
        request.send();

        // Return abort method
        return {
            abort: () => {
                request.abort();
            }
        };
    },
    // process: ..., // Define later for uploads
    // revert: ...,  // Define later for upload cancellation
    // restore: ..., // Define later
    // fetch: ...,   // Define later
}));

const filePondOptions = computed(() => {

    console.log(`[FormFieldFilePond:${props.field.key}] Initial files:`, initialFiles.value);
    const options = {
        labelIdle: props.field.labelIdle || 'Arraste e solte seu arquivo ou <span class="filepond--label-action">Procure</span>',
        allowMultiple: props.field.multiple || false,
        acceptedFileTypes: props.field.accept,
        files: initialFiles.value, // Use computed initial files with relative paths
        allowImagePreview: true, // Explicitly enable image preview
        // server: serverOptions.value, // Already handled by the separate :server prop
        ...(props.field.filepondOptions || {}) // Merge any custom options
    };
    // console.log(`[FormFieldFilePond:${props.field.name}] Computed FilePond options:`, options);
    return options;
});

// --- Event Handlers ---

function handleInit() {
    console.log(`[FormFieldFilePond:${props.field.key}] FilePond instance initialized.`);
    // You can access the FilePond instance here if needed: pond.value.filepond
}

function handleAddFile(error: any, file: any) {
    if (error) {
        console.error(`[FormFieldFilePond:${props.field.name}] Error adding file:`, error);
        return;
    }

    // Update model to File/Blob only if it's currently the initial relative path string
    // This prevents the loop caused by the initial server.load triggering an update
    if (typeof model.value === 'string') {
        console.log(`[FormFieldFilePond:${props.field.name}] Updating model from initial string to File/Blob:`, file.file.name);
        model.value = file.file; // Emit the File/Blob object
    } else {
         console.log(`[FormFieldFilePond:${props.field.name}] File added event ignored, model already updated. Origin: ${file.origin}, File:`, file.file?.name);
         // If allowMultiple=false, FilePond might fire addfile for replacement.
         // If the model needs updating *only* on user action (input/drop), more complex logic might be needed.
         // But let's see if this simple check breaks the loop for initial load.
    }
}

function handleRemoveFile(error: any, file: any) {
    if (error) {
        console.error(`[FormFieldFilePond:${props.field.key}] Error removing file:`, error);
        return;
    }
    // When a file is removed (could be the initial 'local' file or a newly added one)
    console.log(`[FormFieldFilePond:${props.field.key}] File removed:`, file.file?.name || file.filename); // filename for local
    model.value = null; // Emit null when file is removed
}

// Optional: Watch for external changes to model (e.g., form reset)
watch(model, (newValue) => {
    // If model is cleared externally (set to null or empty string) and FilePond has files
    if (!newValue && pond.value?.getFiles().length > 0) {
        console.log(`[FormFieldFilePond:${props.field.key}] Model cleared externally, removing files from FilePond.`);
        pond.value.removeFiles();
    }
    // If model is set to a URL externally and files array is empty or doesn't match
    else if (typeof newValue === 'string' && newValue && pond.value &&
        (!pond.value.getFiles().length ||
            JSON.stringify(initialFiles.value) !== JSON.stringify(pond.value.getFiles().map((f: any) => ({ source: f.source }))))) {
        console.log(`[FormFieldFilePond:${props.field.key}] Model set externally to URL, resetting FilePond files.`);
        // Force update das files para garantir que a visualização seja atualizada
        pond.value.removeFiles();
        setTimeout(() => {
            pond.value.addFiles(initialFiles.value);
        }, 100);
    }
});

// --- Download Logic (Placeholder) ---
const canDownload = computed(() => typeof model.value === 'string' && model.value);

function downloadInitialFile(event: Event) {
    event.stopPropagation(); // Prevent FilePond click event
    if (typeof model.value === 'string' && model.value) {
        console.log(`[FormFieldFilePond:${props.field.key}] Triggering download for:`, model.value);
        // Create a temporary link and click it
        const link = document.createElement('a');
        link.href = model.value;
        link.setAttribute('download', model.value.substring(model.value.lastIndexOf('/') + 1)); // Extract filename
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

</script>

<template>
    <div class="filepond-wrapper space-y-2">
        <FilePond
            ref="pond"
            :name="props.field.key"
            :server="serverOptions"           
            :files="filePondOptions.files"    
            :accepted-file-types="filePondOptions.acceptedFileTypes"
            :allow-multiple="filePondOptions.allowMultiple"
            :label-idle="filePondOptions.labelIdle"
            credits="false"  
            @init="handleInit"
            @addfile="handleAddFile"
            @removefile="handleRemoveFile"
        />
        <!-- Link de download para o arquivo inicial -->
         <div v-if="canDownload" class="text-sm text-right">
              <a 
                :href="typeof model === 'string' ? model : undefined" 
                download 
                target="_blank" 
                class="text-blue-600 hover:underline"
                @click="downloadInitialFile" 
                v-if="typeof model === 'string'" 
              >
                Baixar arquivo atual
              </a>
         </div>
    </div>
</template>

<style>
/* Ajustando o estilo para melhorar a visualização */
.filepond--panel-root {
    background-color: #f8f9fa;
}

.filepond--image-preview-wrapper {
    width: 100%;
    height: auto;
}

.filepond--image-preview {
    height: auto !important;
    max-height: 200px;
}
</style>