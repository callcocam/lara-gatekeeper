<script lang="ts" setup>
import { computed, reactive, ref } from "vue"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import GtButton from "./GtButton.vue"
import ConfigurableModal from "../ConfigurableModal.vue"
import { useForm } from "vee-validate"

// Reuse `form` section  

// DEFINE and EXPORT types locally (ou mover para um arquivo types.ts dentro do pacote)
export interface FieldConfig {
    name: string;
    label: string;
    type: string;
    required?: boolean;
    description?: string;
    options?: Record<string, string>;
    row?: number;
    colSpan?: number;
    // Allow any other props needed by specific field components
    [key: string]: any;
}

interface ActionProps {
    action: {
        id: string;
        label: string;
        icon?: string;
        color?: string;
        routeName?: string;
        iconPosition?: string;
        size: "default" | "sm" | "lg" | "icon" | null | undefined;
        variant?: "default" | "destructive" | "outline" | "secondary" | "ghost" | "link";
        confirm?: {
            title: string;
            description: string;
        };
        modal: {
            fields: Array<FieldConfig>;
        };
    };
}
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