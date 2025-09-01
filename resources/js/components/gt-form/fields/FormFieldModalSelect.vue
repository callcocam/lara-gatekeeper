<script setup lang="ts">
import { ref, computed, watch, shallowRef, defineAsyncComponent } from 'vue'
// TODO: Assumir dependências como peer dependencies ou copiar/recriar 
import { X } from 'lucide-vue-next'
import { cn } from '../../../lib/utils' 
import { Dialog, DialogTrigger, DialogContent, DialogHeader, DialogTitle, DialogClose, DialogFooter } from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'

// Define props
const props = defineProps<{
    id: string;
    // modelValue: string | number | null; // Removido, usa defineModel
    field: {
        name: string; // Adicionado name
        modalTitle?: string;
        selectionComponent: object; // Forçar passagem do componente importado
        componentProps?: Record<string, any>;
        valueKey?: string;
        labelKey?: string;
        initialLabel?: string;
        [key: string]: any;
    };
    inputProps?: { placeholder?: string; [key: string]: any };
    error?: string | null | undefined;
}>()

// Use defineModel for v-model binding
const model = defineModel<string | number | null>()

const open = ref(false)
// Prioritize initialLabel if provided, otherwise keep null initially
const selectedLabel = ref<string | null>(props.field.initialLabel || null)

const valueKey = computed(() => props.field.valueKey || 'id')
const labelKey = computed(() => props.field.labelKey || 'name')
const placeholder = computed(() => props.inputProps?.placeholder || 'Selecionar...')
const modalTitle = computed(() => props.field.modalTitle || 'Selecionar Item')

// Componente é passado diretamente como objeto
const ResolvedSelectionComponent = shallowRef(props.field.selectionComponent);

// Handler for when the internal component selects an item
const handleItemSelected = (item: Record<string, any> | null) => { // Permitir item nulo para deselecionar?
    if (item && item[valueKey.value] !== undefined) {
        const newValue = item[valueKey.value];
        const newLabel = item[labelKey.value] || `ID: ${newValue}`;
        console.log(`[Gatekeeper/ModalSelect:${props.field.name}] Item selected:`, item, `Value: ${newValue}, Label: ${newLabel}`);
        model.value = newValue;
        selectedLabel.value = newLabel;
        open.value = false; // Close the dialog
    } else if (item === null) {
        // Se o componente interno emitir null, limpar seleção
        console.log(`[Gatekeeper/ModalSelect:${props.field.name}] Null item selected, clearing selection.`);
        clearSelection();
        open.value = false;
    } else {
        console.warn(`[Gatekeeper/ModalSelect:${props.field.name}] Invalid item selected or missing value key:`, item);
    }
}

// Function to clear the selection
const clearSelection = () => {
    console.log(`[Gatekeeper/ModalSelect:${props.field.name}] Clearing selection.`);
    model.value = null;
    selectedLabel.value = null;
}

// Watch for external changes to modelValue to potentially update the label
watch(model, (newValue, oldValue) => {
    if (newValue === null && oldValue !== null) {
        // Apenas limpa o label se o valor for explicitamente setado para null
        console.log(`[Gatekeeper/ModalSelect:${props.field.name}] Model cleared externally.`);
        selectedLabel.value = null;
    }
    // Se o valor mudar (e não for null), e não tivermos label, usar initialLabel (se houver)
    // É menos confiável, pois não sabemos se initialLabel corresponde ao novo ID.
    // Idealmente, o label deve vir junto com a seleção ou initialLabel.
    else if (newValue !== null && newValue !== oldValue && !selectedLabel.value && props.field.initialLabel) {
        console.log(`[Gatekeeper/ModalSelect:${props.field.name}] Model changed externally to ${newValue}, using initialLabel.`);
         selectedLabel.value = props.field.initialLabel;
    }
    // Não faz nada se o valor não for null e já tivermos um label.
}, { immediate: false }); // Não precisa immediate, confia no initialLabel ou na primeira seleção

// Se initialLabel for fornecido e o valor inicial também, define o label
if (props.field.initialLabel && model.value !== null && !selectedLabel.value) {
    selectedLabel.value = props.field.initialLabel;
}

</script>

<template>
    <Dialog v-model:open="open">
        <div class="flex items-center gap-1">
            <DialogTrigger as-child>
                <Button
                    variant="outline"
                    role="combobox"
                    :aria-expanded="open"
                    :id="props.id"
                    class="flex-1 justify-between font-normal text-left h-auto min-h-[--radix-select-trigger-height] px-3 py-2" 
                    v-bind="props.inputProps"
                >
                    <span :class="cn('truncate', !selectedLabel && 'text-muted-foreground')">
                        {{ selectedLabel || placeholder }}
                    </span>
                    <!-- Ícone pode ser adicionado aqui se desejado -->
                </Button>
            </DialogTrigger>
            <Button 
                v-if="model !== null && !props.field.required" 
                variant="ghost" 
                size="icon" 
                @click="clearSelection" 
                class="text-muted-foreground hover:text-destructive h-8 w-8 shrink-0"
                aria-label="Limpar seleção"
            >
                <X class="h-4 w-4" />
            </Button>
        </div>
        <DialogContent class="sm:max-w-[80vw] md:max-w-[70vw] lg:max-w-[60vw] xl:max-w-[50vw] max-h-[85vh] flex flex-col p-0">
            <DialogHeader class="p-6 pb-4">
                <DialogTitle>{{ modalTitle }}</DialogTitle>
            </DialogHeader>

            <div class="flex-1 overflow-y-auto p-6 pt-0">
                 <component 
                     v-if="ResolvedSelectionComponent && typeof ResolvedSelectionComponent !== 'string'"
                     :is="ResolvedSelectionComponent" 
                     v-bind="field.componentProps"
                     @item-selected="handleItemSelected"
                 />
                 <div v-else class="text-destructive text-sm p-4 bg-destructive/10 rounded">
                     Erro: Componente de seleção inválido fornecido.
                 </div>
            </div>
            
            <DialogFooter class="p-6 pt-4 border-t">
                 <DialogClose as-child>
                    <Button type="button" variant="outline">Fechar</Button>
                 </DialogClose>
             </DialogFooter>
        </DialogContent>
    </Dialog>
</template> 