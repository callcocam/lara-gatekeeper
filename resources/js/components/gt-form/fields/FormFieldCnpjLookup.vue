<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button'; 
import { Search, LoaderCircle } from 'lucide-vue-next';
import { mask } from 'vue-the-mask';

const vMask = mask;

const props = defineProps<{
    id: string;
    label?: string;
    field: any;
    inputProps?: Record<string, any>;
    modelValue: any;
    error?: string | null | undefined;
}>();

const emit = defineEmits(['update:form-values', 'update:modelValue']);

const cnpj = ref(props.modelValue || '');
const isLoading = ref(false);
const error = ref('');

const safeModel = computed({
    get: () => props.modelValue || '',
    set: (value) => {
        emit('update:modelValue', value);
    }
});

const searchCnpj = async () => {
    if (!safeModel.value || safeModel.value.length < 18) { // 18 chars com a máscara
        error.value = 'Por favor, insira um CNPJ válido.';
        return;
    }

    isLoading.value = true;
    error.value = '';

    try {
        const plainCnpj = safeModel.value.replace(/\D/g, '');
        const response = await fetch(`https://brasilapi.com.br/api/cnpj/v1/${plainCnpj}`);

        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.message || 'Erro ao buscar o CNPJ.');
        }

        const data = await response.json();
        console.log(data);
        const mappedData: Record<string, any> = {};
        const mappings = props.field.fieldMappings || {};

        for (const [apiField, formField] of Object.entries(mappings)) {
            // Verifica se o valor do mapeamento é um objeto (para o endereço)
            if (typeof formField === 'object' && formField !== null) {
                const subObject: Record<string, any> = {};
                // Itera sobre o sub-mapeamento (ex: { logradouro: 'street', ... })
                for (const [subApiField, subFormField] of Object.entries(formField as Record<string, string>)) {
                    if (data[subApiField] !== undefined) {
                        subObject[subFormField] = data[subApiField];
                    }
                }
                mappedData[apiField] = subObject; // Mapeia o objeto construído (ex: mappedData['address'] = { street: '...', ... })
            } else {
                // Mapeamento simples (ex: razao_social -> name)
                if (data[apiField] !== undefined) {
                    mappedData[formField as string] = data[apiField];
                }
            }
        }

        emit('update:form-values', mappedData);

    } catch (e: any) {
        error.value = e.message || 'Não foi possível consultar o CNPJ.';
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <div class="space-y-2">
        <GtLabel :field="field" :error="error" :fieldId="props.id" />
        <div class="flex items-center space-x-2">
            <Input v-model="safeModel" v-mask="'##.###.###/####-##'" placeholder="Digite o CNPJ"
                @keydown.enter="searchCnpj" />
            <Button @click="searchCnpj" :disabled="isLoading" type="button">
                <LoaderCircle v-if="isLoading" class="w-4 h-4 animate-spin" />
                <Search v-else class="w-4 h-4" />
            </Button>
        </div>
        <GtDescription :description="field.description" :error="props.error" />
        <GtError :id="props.id" :error="props.error" />
    </div>
</template>
