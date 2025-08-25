<script lang="ts" setup>
import { computed, reactive, ref } from "vue"
import { Button } from "@/components/ui/button" 
import GtButton from "./GtButton.vue"
import ConfigurableModal from "../ConfigurableModal.vue"
import { useForm } from "vee-validate"
import { ActionProps } from "../../../types/field"

// Reuse `form` section  

const props = defineProps<ActionProps>();
const isOpen = ref(false)
const modal = reactive(props.action.modal);
const fields = modal.fields || [];

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
</script>

<template>
    <ConfigurableModal v-model:open="isOpen" :title="action.confirm?.title || 'Confirmação'"
        :description="action.confirm?.description || 'Você tem certeza que deseja continuar?'">
        <template #trigger>
            <GtButton :action="action" @click="isOpen = true" />
        </template>
        <div>
            <ConfigurableForm :fields="fields" :inertia-form="form" />
        </div>
        <template #footer>
            <div class="flex justify-end">
                <Button @click="isOpen = false">Fechar</Button>
            </div>
        </template>
    </ConfigurableModal>
</template>