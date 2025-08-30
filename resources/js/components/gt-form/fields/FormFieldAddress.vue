<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Label } from '@/components/ui/label'
import { Input } from '@/components/ui/input'
import { Textarea } from '@/components/ui/textarea'
import { Select, SelectTrigger, SelectContent, SelectItem, SelectValue } from '@/components/ui/select'
import { Checkbox } from '@/components/ui/checkbox'

const props = defineProps<{
    id: string;
    field: {
        name: string;
        label?: string;
        description?: string;
        disabled?: boolean;
        required?: boolean;
        options?: {
            status_options?: Array<{ value: string; label: string }>;
            states?: Array<{ value: string; label: string }>;
            countries?: Array<{ value: string; label: string }>;
        };
        [key: string]: any;
    };
    inputProps?: Record<string, any>;
}>()

const model = defineModel<{
    name: string;
    zip_code: string;
    street: string;
    number: string;
    complement: string;
    reference: string;
    additional_information: string;
    district: string;
    city: string;
    country: string;
    state: string;
    is_default: boolean;
    status: string;
}>({
    default: () => ({
        name: '',
        zip_code: '',
        street: '',
        number: '',
        complement: '',
        reference: '',
        additional_information: '',
        district: '',
        city: '',
        country: 'Brasil',
        state: '',
        is_default: false,
        status: 'draft',
    })
})

// Watcher para garantir que model nunca seja null
watch(model, (newValue) => {
    if (newValue === null || newValue === undefined) {
        model.value = {
            name: '',
            zip_code: '',
            street: '',
            number: '',
            complement: '',
            reference: '',
            additional_information: '',
            district: '',
            city: '',
            country: 'Brasil',
            state: '',
            is_default: false,
            status: 'draft',
        }
    }
}, { immediate: true })

// Computed seguro para uso no template
const safeModel = computed(() => model.value || {
    name: '',
    zip_code: '',
    street: '',
    number: '',
    complement: '',
    reference: '',
    additional_information: '',
    district: '',
    city: '',
    country: 'Brasil',
    state: '',
    is_default: false,
    status: 'draft',
})

// Computed com getter/setter para uso seguro no v-model
const safeName = computed({
    get: () => safeModel.value.name,
    set: (value) => {
        if (model.value) model.value.name = value
    }
})

const safeZipCode = computed({
    get: () => safeModel.value.zip_code,
    set: (value) => {
        if (model.value) model.value.zip_code = value
    }
})

const safeStreet = computed({
    get: () => safeModel.value.street,
    set: (value) => {
        if (model.value) model.value.street = value
    }
})

const safeNumber = computed({
    get: () => safeModel.value.number,
    set: (value) => {
        if (model.value) model.value.number = value
    }
})

const safeComplement = computed({
    get: () => safeModel.value.complement,
    set: (value) => {
        if (model.value) model.value.complement = value
    }
})

const safeReference = computed({
    get: () => safeModel.value.reference,
    set: (value) => {
        if (model.value) model.value.reference = value
    }
})

const safeAdditionalInformation = computed({
    get: () => safeModel.value.additional_information,
    set: (value) => {
        if (model.value) model.value.additional_information = value
    }
})

const safeDistrict = computed({
    get: () => safeModel.value.district,
    set: (value) => {
        if (model.value) model.value.district = value
    }
})

const safeCity = computed({
    get: () => safeModel.value.city,
    set: (value) => {
        if (model.value) model.value.city = value
    }
})

const safeCountry = computed({
    get: () => safeModel.value.country,
    set: (value) => {
        if (model.value) model.value.country = value
    }
})

const safeState = computed({
    get: () => safeModel.value.state,
    set: (value) => {
        if (model.value) model.value.state = value
    }
})

const safeIsDefault = computed({
    get: () => safeModel.value.is_default,
    set: (value) => {
        if (model.value) model.value.is_default = value
    }
})

const safeStatus = computed({
    get: () => safeModel.value.status,
    set: (value) => {
        if (model.value) model.value.status = value
    }
})

// Opções padrão para selects
const defaultStatusOptions = [
    { value: 'draft', label: 'Rascunho' },
    { value: 'published', label: 'Publicado' },
]

const defaultStates = [
    { value: 'AC', label: 'Acre' },
    { value: 'AL', label: 'Alagoas' },
    { value: 'AP', label: 'Amapá' },
    { value: 'AM', label: 'Amazonas' },
    { value: 'BA', label: 'Bahia' },
    { value: 'CE', label: 'Ceará' },
    { value: 'DF', label: 'Distrito Federal' },
    { value: 'ES', label: 'Espírito Santo' },
    { value: 'GO', label: 'Goiás' },
    { value: 'MA', label: 'Maranhão' },
    { value: 'MT', label: 'Mato Grosso' },
    { value: 'MS', label: 'Mato Grosso do Sul' },
    { value: 'MG', label: 'Minas Gerais' },
    { value: 'PA', label: 'Pará' },
    { value: 'PB', label: 'Paraíba' },
    { value: 'PR', label: 'Paraná' },
    { value: 'PE', label: 'Pernambuco' },
    { value: 'PI', label: 'Piauí' },
    { value: 'RJ', label: 'Rio de Janeiro' },
    { value: 'RN', label: 'Rio Grande do Norte' },
    { value: 'RS', label: 'Rio Grande do Sul' },
    { value: 'RO', label: 'Rondônia' },
    { value: 'RR', label: 'Roraima' },
    { value: 'SC', label: 'Santa Catarina' },
    { value: 'SP', label: 'São Paulo' },
    { value: 'SE', label: 'Sergipe' },
    { value: 'TO', label: 'Tocantins' },
]

const defaultCountries = [
    { value: 'Brasil', label: 'Brasil' },
    { value: 'Argentina', label: 'Argentina' },
    { value: 'Chile', label: 'Chile' },
    { value: 'Paraguai', label: 'Paraguai' },
    { value: 'Uruguai', label: 'Uruguai' },
    { value: 'Estados Unidos', label: 'Estados Unidos' },
    { value: 'México', label: 'México' },
    { value: 'Canadá', label: 'Canadá' },
]

// Computed para opções dos selects
const statusOptions = computed(() => props.field.options?.status_options || defaultStatusOptions)
const stateOptions = computed(() => props.field.options?.states || defaultStates)
const countryOptions = computed(() => props.field.options?.countries || defaultCountries)

// Estados para busca de CEP
const isLoadingCep = ref(false)
const cepError = ref('')

// Validação básica
const errors = ref<Record<string, string>>({})

const validateField = (fieldName: string, value: string) => {
    errors.value[fieldName] = ''

    if (props.field.required && !value.trim()) {
        errors.value[fieldName] = 'Campo obrigatório'
        return false
    }

    // Validações específicas para endereço
    if (fieldName === 'zip_code' && value.trim() && !value.match(/^\d{5}-?\d{3}$/)) {
        errors.value[fieldName] = 'CEP deve ter formato 00000-000'
        return false
    }

    if (fieldName === 'number' && value.trim() && !value.match(/^[0-9a-zA-Z\s,-]+$/)) {
        errors.value[fieldName] = 'Número deve conter apenas números, letras, espaços, vírgulas e hífens'
        return false
    }

    return true
}

// Função para buscar endereço via CEP
const searchCep = async (cep: string) => {
    // Remove formatação e valida
    const cleanCep = cep.replace(/\D/g, '')
    if (cleanCep.length !== 8) return

    isLoadingCep.value = true
    cepError.value = ''

    try {
        const response = await fetch(`https://viacep.com.br/ws/${cleanCep}/json/`)
        const data = await response.json()

        if (data.erro) {
            cepError.value = 'CEP não encontrado'
            return
        }

        // Preenche automaticamente os campos
        if (model.value) {
            if (data.logradouro) model.value.street = data.logradouro
            if (data.bairro) model.value.district = data.bairro
            if (data.localidade) model.value.city = data.localidade
            if (data.uf) model.value.state = data.uf
        }

        // Limpa erros dos campos preenchidos
        if (data.logradouro) errors.value.street = ''
        if (data.bairro) errors.value.district = ''
        if (data.localidade) errors.value.city = ''
        if (data.uf) errors.value.state = ''

    } catch (error) {
        console.error('Erro ao buscar CEP:', error)
        cepError.value = 'Erro ao consultar CEP. Tente novamente.'
    } finally {
        isLoadingCep.value = false
    }
}

// Computed para verificar se há erros
const hasErrors = computed(() => {
    return Object.values(errors.value).some(error => error !== '')
})

// Função para formatar CEP automaticamente
const formatZipCode = (value: string) => {
    const numbers = value.replace(/\D/g, '')
    if (numbers.length <= 5) {
        return numbers
    }
    return `${numbers.slice(0, 5)}-${numbers.slice(5, 8)}`
}

// Watcher para formatação automática do CEP e busca
watch(() => safeModel.value.zip_code, (newValue, oldValue) => {
    if (newValue && model.value) {
        // Formatação automática
        const formatted = formatZipCode(newValue)
        if (formatted !== newValue) {
            model.value.zip_code = formatted
        }

        // Busca automática quando CEP está completo
        const cleanCep = newValue.replace(/\D/g, '')
        const oldCleanCep = oldValue?.replace(/\D/g, '') || ''
        
        // Só busca se o CEP mudou e tem 8 dígitos
        if (cleanCep.length === 8 && cleanCep !== oldCleanCep) {
            searchCep(newValue)
        }

        // Limpa erro do CEP quando o usuário digita
        if (cepError.value) {
            cepError.value = ''
        }
    }
})
</script>

<template>
    <fieldset
        class="address-data border border-input rounded-md px-4 pb-2">
        <legend class="text-lg font-semibold text-gray-800 dark:text-gray-200">{{ field.label }}</legend>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">{{ field.description }}</p>

        <div :id="props.id" class="space-y-4 my-2">
            <!-- Identificação -->
            <div class="space-y-2">
                <!-- <h4 class="text-md font-medium text-gray-700 dark:text-gray-300">Identificação</h4> -->
                <p class="text-xs text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-gray-700/50 border dark:border-blue-500/30 p-2 rounded-md">
                    💡 <strong>Dica:</strong> Digite o CEP completo para preencher automaticamente o logradouro, bairro, cidade e estado.
                </p>
                <div class="grid grid-cols-2 gap-4">
                    <!-- Nome do Endereço -->
                    <div class="flex flex-col gap-2">
                        <Label :for="`${props.id}-name`">Nome do Endereço</Label>
                        <Input :id="`${props.id}-name`" v-model="safeName" type="text"
                            placeholder="Ex: Casa, Trabalho, Matriz..." :disabled="props.field.disabled"
                            :class="{ 'border-red-500 dark:border-red-400': errors.name }"
                            class="dark:bg-input/30"
                            @blur="validateField('name', safeName)" />
                        <span v-if="errors.name" class="text-xs text-red-500 dark:text-red-400 ml-2">{{ errors.name }}</span>
                    </div>

                    <!-- CEP -->
                    <div class="flex flex-col gap-2">
                        <Label :for="`${props.id}-zip_code`">CEP</Label>
                        <div class="relative">
                            <Input :id="`${props.id}-zip_code`" v-model="safeZipCode" type="text"
                                placeholder="00000-000" maxlength="9" :disabled="props.field.disabled || isLoadingCep"
                                :class="{ 
                                    'border-red-500 dark:border-red-400': errors.zip_code || cepError,
                                    'border-green-500 dark:border-green-400': safeModel.street && !errors.zip_code && !cepError,
                                    'pr-8': isLoadingCep 
                                }"
                                class="dark:bg-input/30"
                                @blur="validateField('zip_code', safeZipCode)" />
                            <!-- Loading spinner -->
                            <div v-if="isLoadingCep" class="absolute right-2 top-1/2 transform -translate-y-1/2">
                                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 dark:border-blue-400"></div>
                            </div>
                        </div>
                        <span v-if="errors.zip_code" class="text-xs text-red-500 dark:text-red-400 ml-2">{{ errors.zip_code }}</span>
                        <span v-if="cepError" class="text-xs text-red-500 dark:text-red-400 ml-2">{{ cepError }}</span>
                        <span v-if="!errors.zip_code && !cepError && safeModel.street" class="text-xs text-green-600 ml-2">
                            ✓ Endereço encontrado automaticamente
                        </span>
                    </div>
                </div>
            </div>

            <!-- Logradouro -->
            <div class="space-y-2">
                <!-- <h4 class="text-md font-medium text-gray-700 dark:text-gray-300">Logradouro</h4> -->
                <div class="grid grid-cols-3 gap-4">
                    <!-- Endereço -->
                    <div class="col-span-2 flex flex-col gap-2">
                        <Label :for="`${props.id}-street`">Rua/Avenida/Logradouro</Label>
                        <Input :id="`${props.id}-street`" v-model="safeStreet" type="text"
                            placeholder="Rua, Avenida, Travessa... (preenchido automaticamente via CEP)" 
                            :disabled="props.field.disabled"
                            :class="{ 'border-red-500 dark:border-red-400': errors.street }"
                            class="dark:bg-input/30"
                            @blur="validateField('street', safeStreet)" />
                        <span v-if="errors.street" class="text-xs text-red-500 dark:text-red-400 ml-2">{{ errors.street }}</span>
                    </div>

                    <!-- Número -->
                    <div class="flex flex-col gap-2">
                        <Label :for="`${props.id}-number`">Número</Label>
                        <Input :id="`${props.id}-number`" v-model="safeNumber" type="text"
                            placeholder="123, S/N, KM 45..." :disabled="props.field.disabled"
                            :class="{ 'border-red-500 dark:border-red-400': errors.number }"
                            class="dark:bg-input/30"
                            @blur="validateField('number', safeNumber)" />
                        <span v-if="errors.number" class="text-xs text-red-500 dark:text-red-400 ml-2">{{ errors.number }}</span>
                    </div>
                </div>

                <!-- Complemento -->
                <div class="grid grid-cols-1 gap-4">
                    <div class="flex flex-col gap-2">
                        <Label :for="`${props.id}-complement`">Complemento</Label>
                        <Input :id="`${props.id}-complement`" v-model="safeComplement" type="text"
                            placeholder="Apartamento, Sala, Bloco, Andar..." :disabled="props.field.disabled"
                            :class="{ 'border-red-500 dark:border-red-400': errors.complement }" 
                            class="dark:bg-input/30" />
                        <span v-if="errors.complement" class="text-xs text-red-500 dark:text-red-400 ml-2">{{ errors.complement }}</span>
                    </div>
                </div>
            </div>

            <!-- Localização -->
            <div class="space-y-2">
                <!-- <h4 class="text-md font-medium text-gray-700 dark:text-gray-300">Localização</h4> -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- Bairro -->
                    <div class="flex flex-col gap-2">
                        <Label :for="`${props.id}-district`">Bairro</Label>
                        <Input :id="`${props.id}-district`" v-model="safeDistrict" type="text"
                            placeholder="Preenchido automaticamente via CEP..." :disabled="props.field.disabled"
                            :class="{ 'border-red-500 dark:border-red-400': errors.district }"
                            class="dark:bg-input/30"
                            @blur="validateField('district', safeDistrict)" />
                        <span v-if="errors.district" class="text-xs text-red-500 dark:text-red-400 ml-2">{{ errors.district }}</span>
                    </div>

                    <!-- Cidade -->
                    <div class="flex flex-col gap-2">
                        <Label :for="`${props.id}-city`">Cidade</Label>
                        <Input :id="`${props.id}-city`" v-model="safeCity" type="text"
                            placeholder="Preenchida automaticamente via CEP..." :disabled="props.field.disabled"
                            :class="{ 'border-red-500 dark:border-red-400': errors.city }"
                            class="dark:bg-input/30"
                            @blur="validateField('city', safeCity)" />
                        <span v-if="errors.city" class="text-xs text-red-500 dark:text-red-400 ml-2">{{ errors.city }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <!-- Estado -->
                    <div class="flex flex-col gap-2">
                        <Label :for="`${props.id}-state`">Estado</Label>
                        <Select v-model="safeState" :disabled="props.field.disabled">
                            <SelectTrigger :id="`${props.id}-state`" class="border rounded px-2 py-1 w-full dark:bg-input/30">
                                <SelectValue placeholder="Preenchido automaticamente via CEP..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="option in stateOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <span v-if="errors.state" class="text-xs text-red-500 dark:text-red-400 ml-2">{{ errors.state }}</span>
                    </div>

                    <!-- País -->
                    <div class="flex flex-col gap-2">
                        <Label :for="`${props.id}-country`">País</Label>
                        <Select v-model="safeCountry" :disabled="props.field.disabled">
                            <SelectTrigger :id="`${props.id}-country`" class="border rounded px-2 py-1 w-full dark:bg-input/30">
                                <SelectValue placeholder="Selecione o país..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="option in countryOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <span v-if="errors.country" class="text-xs text-red-500 dark:text-red-400 ml-2">{{ errors.country }}</span>
                    </div>
                </div>
            </div>

            <!-- Configurações -->
            <div class="space-y-2">
                <!-- <h4 class="text-md font-medium text-gray-700 dark:text-gray-300">Configurações</h4> -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- Endereço Padrão -->
                    <div class="flex items-center gap-2">
                        <Checkbox :id="`${props.id}-is_default`" v-model:checked="safeIsDefault" 
                            :disabled="props.field.disabled" />
                        <Label :for="`${props.id}-is_default`" class="text-sm">Endereço padrão</Label>
                        <span v-if="errors.is_default" class="text-xs text-red-500 dark:text-red-400 ml-2">{{ errors.is_default }}</span>
                    </div>

                    <!-- Status -->
                    <div class="flex flex-col gap-2">
                        <Label :for="`${props.id}-status`">Status</Label>
                        <Select v-model="safeStatus" :disabled="props.field.disabled">
                            <SelectTrigger :id="`${props.id}-status`" class="border rounded px-2 py-1 w-full dark:bg-input/30">
                                <SelectValue placeholder="Selecione o status..." />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="option in statusOptions" :key="option.value" :value="option.value">
                                    {{ option.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <span v-if="errors.status" class="text-xs text-red-500 dark:text-red-400 ml-2">{{ errors.status }}</span>
                    </div>
                </div>
            </div>

            <!-- Informações Adicionais -->
            <div class="space-y-2">
                <!-- <h4 class="text-md font-medium text-gray-700 dark:text-gray-300">Informações Adicionais</h4> -->
                <div class="grid grid-cols-1 gap-4">
                    <!-- Ponto de Referência -->
                    <div class="flex flex-col gap-2">
                        <Label :for="`${props.id}-reference`">Ponto de Referência</Label>
                        <Input :id="`${props.id}-reference`" v-model="safeReference" type="text"
                            placeholder="" :disabled="props.field.disabled"
                            :class="{ 'border-red-500 dark:border-red-400': errors.reference }" 
                            class="dark:bg-input/30" />
                        <span v-if="errors.reference" class="text-xs text-red-500 dark:text-red-400 ml-2">{{ errors.reference }}</span>
                    </div>

                    <!-- Informações Adicionais -->
                    <div class="flex flex-col gap-2">
                        <Label :for="`${props.id}-additional_information`">Observações</Label>
                        <Textarea :id="`${props.id}-additional_information`" v-model="safeAdditionalInformation"
                            placeholder="" class="min-h-20 dark:bg-input/30"
                            :disabled="props.field.disabled"
                            :class="{ 'border-red-500 dark:border-red-400': errors.additional_information }" />
                        <span v-if="errors.additional_information" class="text-xs text-red-500 dark:text-red-400 ml-2">{{
                            errors.additional_information }}</span>
                    </div>
                </div>
            </div>

            <!-- Resumo dos dados preenchidos -->
            <div v-if="Object.values(safeModel).some(value => value && value.toString().trim())"
                class="mt-4 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-md">
                <p class="text-sm text-gray-700 dark:text-gray-300 font-medium mb-2">Resumo do Endereço:</p>
                <div class="text-xs text-gray-600 dark:text-gray-400 space-y-1">
                    <div v-if="safeModel.name || safeModel.is_default" class="flex flex-wrap gap-2">
                        <span v-if="safeModel.name" class="inline-block bg-blue-100 dark:bg-blue-500/20 text-blue-800 dark:text-blue-300 px-2 py-1 rounded">
                            {{ safeModel.name }}
                        </span>
                        <span v-if="safeModel.is_default" class="inline-block bg-blue-100 dark:bg-blue-500/20 text-blue-800 dark:text-blue-300 px-2 py-1 rounded">
                            ⭐ Padrão
                        </span>
                        <span v-if="safeModel.status" class="inline-block bg-blue-100 dark:bg-blue-500/20 text-blue-800 dark:text-blue-300 px-2 py-1 rounded">
                            {{statusOptions.find(opt => opt.value === safeModel.status)?.label || safeModel.status}}
                        </span>
                    </div>
                    <div v-if="safeModel.street || safeModel.number" class="flex flex-wrap gap-2">
                        <span v-if="safeModel.street"
                            class="inline-block bg-green-100 dark:bg-green-500/20 text-green-800 dark:text-green-300 px-2 py-1 rounded">
                            {{ safeModel.street }}{{ safeModel.number ? ', ' + safeModel.number : '' }}
                        </span>
                        <span v-if="safeModel.complement"
                            class="inline-block bg-green-100 dark:bg-green-500/20 text-green-800 dark:text-green-300 px-2 py-1 rounded">
                            {{ safeModel.complement }}
                        </span>
                    </div>
                    <div v-if="safeModel.district || safeModel.city" class="flex flex-wrap gap-2">
                        <span v-if="safeModel.district"
                            class="inline-block bg-purple-100 dark:bg-purple-500/20 text-purple-800 dark:text-purple-300 px-2 py-1 rounded">
                            {{ safeModel.district }}
                        </span>
                        <span v-if="safeModel.city"
                            class="inline-block bg-purple-100 dark:bg-purple-500/20 text-purple-800 dark:text-purple-300 px-2 py-1 rounded">
                            {{ safeModel.city }}
                        </span>
                    </div>
                    <div v-if="safeModel.state || safeModel.zip_code" class="flex flex-wrap gap-2">
                        <span v-if="safeModel.state"
                            class="inline-block bg-orange-100 dark:bg-orange-500/20 text-orange-800 dark:text-orange-300 px-2 py-1 rounded">
                            {{stateOptions.find(opt => opt.value === safeModel.state)?.label || safeModel.state}}
                        </span>
                        <span v-if="safeModel.zip_code"
                            class="inline-block bg-orange-100 dark:bg-orange-500/20 text-orange-800 dark:text-orange-300 px-2 py-1 rounded">
                            CEP: {{ safeModel.zip_code }}
                        </span>
                        <span v-if="safeModel.country"
                            class="inline-block bg-orange-100 dark:bg-orange-500/20 text-orange-800 dark:text-orange-300 px-2 py-1 rounded">
                            {{ safeModel.country }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </fieldset>
</template>