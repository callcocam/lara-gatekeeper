<script setup lang="ts">
import { computed, ref, onMounted } from 'vue';
// import type { ConcreteComponent } from 'vue'; // Ajustar import se necessário

// Import vueFilePond
import vueFilePond from 'vue-filepond';

// Import FilePond styles
import 'filepond/dist/filepond.min.css';

// Import FilePond plugins
// Please install these plugins with npm i filepond-plugin-image-preview -S
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type'; // Exemplo: Adicionando validação de tipo
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';
import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css';

// Create FilePond component
const FilePond = vueFilePond(FilePondPluginFileValidateType, FilePondPluginImagePreview);

interface Props {
    field: { 
        key: string;
        label?: string;
        help?: string;
        multiple?: boolean;
        acceptedFileTypes?: string[] | null;
        [key: string]: any;
    };
    modelValue: string | string[] | null;
}

const props = defineProps<Props>();

const emit = defineEmits(['update:modelValue']);

const pond = ref<any>(null); // Ref para a instância do FilePond
const csrfToken = ref<string|null>(null);

// Pega o token CSRF do meta tag (padrão Laravel)
onMounted(() => {
    const tokenElement = document.head.querySelector('meta[name="csrf-token"]');
    if (tokenElement) {
        csrfToken.value = tokenElement.getAttribute('content');
    } else {
        console.warn('CSRF token not found. File uploads might fail.');
    }
});

// Configuração do servidor FilePond
const serverOptions = computed(() => {
    if (!csrfToken.value) return null; // Não configurar se não houver token
    return {
        url: '/api', // URL base, endpoints específicos abaixo
        process: {
            url: '/uploads/process',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken.value
            },
            onload: (response: any) => {
                // response é o path retornado pelo UploadController 
                // O FilePond armazena isso internamente, não precisamos emitir aqui diretamente
                // Mas podemos usar o @processfile para pegar o ID
                emit('update:modelValue', response);
                return response; 
            },
            onerror: (response: any) => {
                console.error('File process error:', response);
                // TODO: Tratar erro de upload (ex: mostrar mensagem)
                return null; 
            },
        },
        revert: {
            url: '/uploads/revert',
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken.value,
                'Content-Type': 'text/plain' // O controller espera o path no corpo
            },
            onload: (response: any) => {
                console.log('File revert success:', response);
                // Atualizar o modelValue se necessário (removendo o ID)
                // FilePond lida com isso bem ao remover o arquivo da UI
            },
            onerror: (response: any) => {
                console.error('File revert error:', response);
                // TODO: Tratar erro de revert
                return null;
            },
        },
        // load: { // Adicionado endpoint de restore
        //     url: '/uploads/load/', // Reutiliza o endpoint de load para buscar o arquivo pelo ID/path
        //     method: 'GET',
        //      headers: { 
        //          // ... headers se necessário ...
        //      },
        //      onerror: (response: any) => {
        //         console.error('File restore error:', response);
        //     },
        // },
    }
});

// Computa os arquivos iniciais
const initialFiles = computed(() => { 
    let relativePath: string | null = null;

    if (typeof props.modelValue === 'string' && props.modelValue) {
        if (props.modelValue.startsWith('http')) {
            try {
                const url = new URL(props.modelValue);
                const storagePrefix = '/storage/'; 
                if (url.pathname.startsWith(storagePrefix)) {
                    relativePath = url.pathname.substring(storagePrefix.length); 
                } else {
                     console.warn('[initialFiles] Could not extract relative path from URL:', props.modelValue);
                }
            } catch (e) {
                console.error('[initialFiles] Invalid URL:', props.modelValue, e);
            }
        } else {
            relativePath = props.modelValue; 
        }
    }

    // Se conseguimos um path relativo, passamos como source
    if (relativePath) { 
        return [
            {
                source: relativePath, 
                options: { 
                    type: 'local',
                    file: { 
                        name: relativePath.split('/').pop() || 'arquivo-existente',
                        size: 12345, 
                    },
                }
            }
        ];
    }

    return []; 
});

// Handler para quando a lista interna de arquivos do FilePond é atualizada
const handleUpdateFiles = (fileItems: any[]) => { 
    
    // Filtra para pegar apenas arquivos processados com sucesso pelo servidor
    const processedFiles = fileItems.filter(item => item.status === 5 && item.serverId); 

    if (props.field.multiple) {
        const serverIds = processedFiles.map(item => item.serverId); 
        emit('update:modelValue', serverIds);
    } else {
        // Se não for múltiplo, pega o último arquivo processado (ou null)
        const latestServerId = processedFiles.length > 0 ? processedFiles[processedFiles.length - 1].serverId : null; 
        emit('update:modelValue', latestServerId);
    }
};

// Computa a URL pública visualizável do arquivo atual (para upload único)
const viewableFileUrl = computed(() => {
    // Verifica se não é múltiplo e se modelValue contém um path relativo válido
    if (!props.field.multiple && typeof props.modelValue === 'string' && props.modelValue && !props.modelValue.startsWith('http')) {
        try {
            // Assume que a URL base do storage é /storage/
            // Pode precisar buscar de uma config global se for diferente
            const baseUrl = window.location.origin;
            const storagePath = '/storage/'; 
            const fullUrl = `${baseUrl}${storagePath}${props.modelValue}`; 
            return fullUrl;
        } catch (e) {
            console.error('Error generating viewable URL:', e);
            return null;
        }
    }
    return null;
});

// TODO: Configurar FilePond options baseado em props.field (outras opções além do server)

</script>

<template>
    <div>
        <!-- <label v-if="field.label" :for="field.key" class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">{{ field.label }}</label> --> 
        <FilePond 
            v-if="serverOptions" 
            ref="pond" 
            :name="field.key"
            label-idle="Arraste e solte seus arquivos aqui ou <span class='filepond--label-action'> Navegue </span>"
            :allow-multiple="field.multiple ?? false"
            :accepted-file-types="field.acceptedFileTypes ?? null"
            :server="serverOptions"
            :files="initialFiles"
            :credits="null"
            labelFileProcessingError="Erro during processing"
            @updatefiles="handleUpdateFiles"
        />
        <div v-else>
            Carregando configuração do uploader... (ou erro de CSRF)
        </div>

        <!-- Link para Visualizar -->
        <div v-if="viewableFileUrl" class="mt-2">
            <a 
                :href="viewableFileUrl" 
                target="_blank" 
                rel="noopener noreferrer"
                class="text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-200"
            >
                Visualizar Imagem Atual
            </a>
        </div>

        <p v-if="field.help" class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ field.help }}</p>
        <!-- TODO: Lógica para exibir erros (ex: field.errors) pode ser adicionada aqui -->
    </div>
</template>

<style scoped>
/* Estilos específicos podem ser adicionados aqui */
/* Você pode precisar ajustar estilos para que o FilePond se integre bem visualmente */
</style> 