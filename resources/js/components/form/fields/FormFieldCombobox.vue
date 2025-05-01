<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { Check, ChevronsUpDown } from 'lucide-vue-next'

// TODO: Assumir dependências como peer dependencies ou copiar/recriar
import { cn } from '../../../lib/utils' // Path relativo correto é um nível acima 

interface ApiOption {
    [key: string]: any;
}

// Define props expected from FormFieldWrapper + specific Combobox props via field config
const props = defineProps<{
    id: string;
    // modelValue: string | number | null; // Removido, usa defineModel
    field: {
        name: string; // Adicionado name
        apiEndpoint: string; 
        searchParam?: string;
        valueKey?: string;
        labelKey?: string;
        initialLabel?: string;
        [key: string]: any;
    };
    inputProps?: { placeholder?: string; [key: string]: any };
}>()

// Use defineModel for v-model binding
const model = defineModel<string | number | null>()

const open = ref(false)
const isLoading = ref(false)
const searchTerm = ref('')
const options = ref<ApiOption[]>([])
const selectedLabel = ref<string | null>(props.field.initialLabel || null)

const valueKey = computed(() => props.field.valueKey || 'id')
const labelKey = computed(() => props.field.labelKey || 'name')
const searchParam = computed(() => props.field.searchParam || 'search')
const placeholder = computed(() => props.inputProps?.placeholder || 'Selecione ou digite para buscar...')

// --- Debounce Logic ---
let debounceTimer: ReturnType<typeof setTimeout> | null = null;
const debounce = (func: Function, delay: number) => {
    return (...args: any[]) => {
        if (debounceTimer) clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            func(...args);
        }, delay);
    };
};

// --- API Fetching ---
const fetchOptions = async (query: string) => {
    if (!props.field.apiEndpoint) return;
    isLoading.value = true;
    console.log(`[Gatekeeper/Combobox:${props.field.name}] Fetching options for query: "${query}"`);
    try {
        // Tenta construir URL absoluta se não for, senão usa como está
        let apiUrl = props.field.apiEndpoint;
        try {
            new URL(apiUrl); // Verifica se já é absoluta
        } catch (_) {
            apiUrl = new URL(apiUrl, window.location.origin).toString(); // Tenta tornar absoluta
        }
        
        const url = new URL(apiUrl);
        if (query) {
            url.searchParams.set(searchParam.value, query);
        }
        const response = await fetch(url.toString());
        if (!response.ok) {
            console.error(`[Gatekeeper/Combobox:${props.field.name}] Network response error: ${response.statusText}`);
            throw new Error('Network response was not ok');
        }
        const data = await response.json();
        
        // Tenta encontrar os dados (pode estar em data.data)
        const results = Array.isArray(data) ? data : (data?.data || []);
        if (!Array.isArray(results)) {
            console.error(`[Gatekeeper/Combobox:${props.field.name}] API response is not an array:`, results);
            options.value = [];
        } else {
            options.value = results;
        }
        console.log(`[Gatekeeper/Combobox:${props.field.name}] Options received:`, options.value.length);
        
        // Tenta encontrar label do valor atual (se não tiver ainda)
        if (model.value !== null && model.value !== undefined && !selectedLabel.value) {
             updateSelectedLabel(model.value);
        }

    } catch (error) {
        console.error(`[Gatekeeper/Combobox:${props.field.name}] Failed to fetch options:`, error);
        options.value = []; 
    } finally {
        isLoading.value = false;
    }
};

const debouncedFetchOptions = debounce(fetchOptions, 300);

// --- Watchers ---
watch(searchTerm, (newSearchTerm) => {
    debouncedFetchOptions(newSearchTerm);
});

watch(open, (isOpen) => {
    if (isOpen && options.value.length === 0 && !searchTerm.value) {
        fetchOptions('');
    } else if (isOpen && model.value !== null && !selectedLabel.value) {
        fetchOptions('');
    }
});

// --- Label Management ---
const getLabel = (value: string | number | null): string | null => {
    if (value === null || value === undefined) return null;
    const selectedOption = options.value.find(option => option[valueKey.value] == value); // Use == for loose comparison (string vs number)
    return selectedOption ? selectedOption[labelKey.value] : null;
}

const updateSelectedLabel = (currentValue: string | number | null) => {
     const valueToCompare = currentValue === undefined ? null : currentValue;
     const foundLabel = getLabel(valueToCompare);
     if (foundLabel) {
        selectedLabel.value = foundLabel;
        console.log(`[Gatekeeper/Combobox:${props.field.name}] Found label for value ${valueToCompare}: "${foundLabel}"`);
     } else if (valueToCompare === null) {
        selectedLabel.value = null; 
        console.log(`[Gatekeeper/Combobox:${props.field.name}] Clearing label as value is null.`);
     } else {
         console.log(`[Gatekeeper/Combobox:${props.field.name}] Label not found for value ${valueToCompare} in current options.`);
         // Manter initialLabel se existir e valor corresponder?
         if (props.field.initialLabel && model.value == props.field.initialValue) { // Comparação frouxa
            selectedLabel.value = props.field.initialLabel;
         } else if (!options.value.length) { // Se não houver opções carregadas, mantenha o que estava
            // Não limpa o label se as opções ainda não carregaram
         } else {
             selectedLabel.value = null; // Limpa se não encontrou E as opções carregaram
         }
     }
}

watch(model, (newValue, oldValue) => {
   console.log(`[Gatekeeper/Combobox:${props.field.name}] Model changed from ${oldValue} to ${newValue}`);
   updateSelectedLabel(newValue === undefined ? null : newValue);
}, { immediate: true });

watch(options, () => {
    if (model.value !== null && model.value !== undefined && !selectedLabel.value) {
        console.log(`[Gatekeeper/Combobox:${props.field.name}] Options updated, trying to find label for value ${model.value}`);
        updateSelectedLabel(model.value);
    }
}, { deep: true }); // Deep watch pode ser pesado se options for muito grande

// --- Event Handlers ---
const handleSelect = (option: ApiOption) => {
    const newValue = option[valueKey.value];
    console.log(`[Gatekeeper/Combobox:${props.field.name}] Option selected:`, option, `New value: ${newValue}`);
    model.value = newValue; 
    // selectedLabel será atualizado pelo watcher do model
    open.value = false;
    searchTerm.value = '';
}

</script>

<template>
    <Popover v-model:open="open">
        <PopoverTrigger as-child>
            <Button
                variant="outline"
                role="combobox"
                :aria-expanded="open"
                :id="props.id"
                class="w-full justify-between font-normal"
                :disabled="isLoading"
                v-bind="props.inputProps"
            >
                <span class="truncate">{{ selectedLabel || placeholder }}</span>
                <ChevronsUpDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-[--radix-popover-trigger-width] p-0">
            <Command should-filter="false">
                <CommandInput
                    v-model="searchTerm"
                    :placeholder="placeholder"
                    :disabled="isLoading"
                    class="h-9"
                 />
                 <CommandList>
                    <CommandEmpty>{{ isLoading ? 'Carregando...' : 'Nenhum resultado encontrado.' }}</CommandEmpty>
                    <CommandGroup>
                        <CommandItem
                            v-for="option in options"
                            :key="option[valueKey]"
                            :value="option[labelKey]"
                            @select="() => handleSelect(option)"
                        >
                            <Check
                                :class="cn(
                                    'mr-2 h-4 w-4',
                                    model == option[valueKey] ? 'opacity-100' : 'opacity-0'
                                )"
                            />
                            <span>{{ option[labelKey] }}</span>
                        </CommandItem>
                    </CommandGroup>
                </CommandList>
            </Command>
        </PopoverContent>
    </Popover>
</template> 