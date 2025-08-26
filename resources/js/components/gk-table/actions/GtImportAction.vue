<script lang="ts" setup>
import { computed, reactive, ref } from "vue"
import { Button } from "@/components/ui/button"
import GtButton from "./GtButton.vue"
import ConfigurableModal from "../ConfigurableModal.vue"
import { useForm } from "@inertiajs/vue3"
import { ActionProps } from "../../../types/field"

// Reuse `form` section  

const props = defineProps<ActionProps>();
const isOpen = ref(false)
const modal = ref(props.action.modal);
const fields = modal.value.fields || [];

const initialData = computed(() => {
    const data: Record<string, any> = {};
    if (Array.isArray(fields)) {
        fields.forEach(field => {
            data[field.name] = null;
        });
    }
    return data;
});
// --- Inicializar useForm do Inertia ---
const form = useForm({
    ...initialData.value,
});

const submitForm = () => {
    if (props.action.method && props.action.url) {
        form.submit((props.action.method?.toLowerCase() as 'get' | 'post' | 'put' | 'delete') || 'post', props.action.url);
    } else {
        console.error("[GtImportAction] Action method or route not defined.");
    }
};


// --- Método para atualizar campo do form via evento ---
const updateFormField = (fieldName: string, newValue: any) => {
    // Cast form to any for this assignment
    (form as any)[fieldName] = newValue;
};
</script>

<template>
    <ConfigurableModal v-model:open="isOpen" :title="modal?.modalHeading || 'Importar Dados'"
        :description="modal?.modalDescription || 'Você tem certeza que deseja continuar?'">
        <template #trigger>
            <GtButton :action="action" @click="isOpen = true" />
        </template>
        <div class="overflow-y-auto ">
            <ConfigurableForm :fields="fields" :inertia-form="form" @update-field="updateFormField" />
        </div>
        <template #footer>
            <div class="flex justify-end space-x-2">
                <Button @click="isOpen = false" :disabled="form?.processing" variant="destructive">
                    {{ modal?.cancelButtonText || 'Cancelar' }}
                </Button>
                <Button :variant="modal?.confirmButtonVariant || 'default'" @click="submitForm"
                    :disabled="form?.processing">
                    {{ modal?.confirmButtonText || 'Confirmar' }}
                </Button>
            </div>
        </template>
    </ConfigurableModal>
</template>