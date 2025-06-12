<script setup lang="ts">
import { computed, ref, watch, onMounted, nextTick } from 'vue'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Calendar, Clock, ArrowRight, Info, Plus, Trash2 } from 'lucide-vue-next'

interface WorkflowTemplate {
    id: number;
    name: string;
    description?: string;
    category: string;
    estimated_duration_days: number;
    color?: string;
    icon?: string;
    suggested_order?: number;
}

interface StepData {
    template_id: number | null;
    expected_date: string | null;
    completed_date: string | null;
    estimated_duration_days: number;
    step_order: number;
}

const props = defineProps<{
    id: string;
    modelValue?: StepData[];
    field: {
        name: string;
        label?: string;
        description?: string;
        disabled?: boolean;
        required?: boolean;
        placeholder?: string;
        // Configurações específicas do workflow
        templatesApiUrl?: string;
        minSteps?: number;
        maxSteps?: number;
        addButtonLabel?: string;
        [key: string]: any;
    };
    inputProps?: Record<string, any>;
}>()

const emit = defineEmits<{
    'update:modelValue': [value: StepData[]];
    fieldAction: [action: string, data: any];
}>()

const model = defineModel<StepData[]>({
    default: () => []
})

// Computed para garantir que sempre temos um array de StepData válido
const stepsData = computed({
    get: (): StepData[] => {
        // Priorizar props.modelValue se disponível
        const value = props.modelValue || model.value
        if (!Array.isArray(value)) {
            return []
        }
        return value
    },
    set: (value: StepData[]) => {
        model.value = value
        emit('update:modelValue', value)
    }
})

// Estados internos
const loading = ref(false)
const templates = ref<WorkflowTemplate[]>([])

// Computed para resumo do workflow
const workflowSummary = computed(() => {
    const steps = stepsData.value.map((step, index) => ({
        order: index + 1,
        hasTemplate: step.template_id ? true : false,
        hasExpectedDate: step.expected_date ? true : false,
        hasCompletedDate: step.completed_date ? true : false,
        templateName: getTemplateName(step.template_id)
    }))

    const totalSteps = steps.length
    const configuredSteps = steps.filter(step => step.hasTemplate).length
    const stepsWithDates = steps.filter(step => step.hasExpectedDate).length

    return {
        totalSteps,
        configuredSteps,
        stepsWithDates,
        steps
    }
})

// Computed para verificar limites
const canAddStep = computed(() => {
    if (props.field.maxSteps) {
        return stepsData.value.length < props.field.maxSteps
    }
    return true
})

const canRemoveStep = computed(() => {
    if (props.field.minSteps) {
        return stepsData.value.length > props.field.minSteps
    }
    return stepsData.value.length > 0
})

// Função para obter nome do template
const getTemplateName = (templateId: number | null): string | null => {
    if (!templateId) return null
    const template = templates.value.find(t => t.id === templateId)
    return template?.name || null
}

// Função para obter template por ID
const getTemplate = (templateId: number | null): WorkflowTemplate | null => {
    if (!templateId) return null
    return templates.value.find(t => t.id === templateId) || null
}

// Função para verificar se é a primeira etapa
const isFirstStep = (index: number): boolean => {
    return index === 0
}

// Função para obter dados da etapa anterior
const getPreviousStepData = (index: number): StepData | null => {
    if (index === 0) return null
    return stepsData.value[index - 1] || null
}

// Função para verificar se data esperada é readonly
const isExpectedDateReadonly = (index: number): boolean => {
    const previousStep = getPreviousStepData(index)
    return !isFirstStep(index) && !!previousStep?.completed_date
}

// Refs reativos para os selects
const selectValues = ref<Record<number, string>>({})

// Função para criar um computed v-model para cada select
const createSelectModel = (stepIndex: number) => {
    return computed({
        get: () => {
            return selectValues.value[stepIndex] || ''
        },
        set: (value: string) => {
            setSelectValue(stepIndex, value)
        }
    })
}

// Função para definir valor do select
const setSelectValue = (stepIndex: number, value: any) => {
    const stringValue = value?.toString() || ''
    console.log(`[WorkflowStepCalculator] Setting select value for step ${stepIndex}:`, stringValue)
    // Forçar reatividade
    selectValues.value = { ...selectValues.value, [stepIndex]: stringValue }

    // Usar nextTick para garantir que a UI seja atualizada
    nextTick(() => {
        handleTemplateChange(stepIndex, stringValue)
    })
}

// Carregar templates via API
const loadTemplates = async () => {
    if (!props.field.templatesApiUrl) {
        console.warn('[WorkflowStepCalculator] No templates API URL provided')
        return
    }

    loading.value = true
    try {
        const response = await fetch(props.field.templatesApiUrl, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            }
        })

        if (!response.ok) throw new Error('Erro ao carregar templates')

        const data = await response.json()
        templates.value = data.data || data || []

    } catch (error) {
        console.error('[WorkflowStepCalculator] API Error:', error)
        templates.value = []
    } finally {
        loading.value = false
    }
}

// Calcular data de conclusão baseada na data esperada e duração
const calculateCompletedDate = (expectedDate: string, durationDays: number): string => {
    const date = new Date(expectedDate)
    date.setDate(date.getDate() + durationDays)
    return date.toISOString().split('T')[0] // Formato YYYY-MM-DD
}

// Calcular data esperada baseada na data de conclusão da etapa anterior
const calculateExpectedDate = (previousCompletedDate: string): string => {
    const date = new Date(previousCompletedDate)
    date.setDate(date.getDate() + 1) // Próximo dia útil
    return date.toISOString().split('T')[0]
}

// Função para adicionar nova etapa
const addStep = () => {
    if (!canAddStep.value) return

    const newStep: StepData = {
        template_id: null,
        expected_date: null,
        completed_date: null,
        estimated_duration_days: 0,
        step_order: stepsData.value.length + 1
    }

    stepsData.value = [...stepsData.value, newStep]
}

// Função para remover etapa
const removeStep = (index: number) => {
    if (!canRemoveStep.value) return

    const newSteps = stepsData.value.filter((_, i) => i !== index)
    // Reordenar step_order
    newSteps.forEach((step, i) => {
        step.step_order = i + 1
    })

    stepsData.value = newSteps
}

// Handler para mudança de template
const handleTemplateChange = (stepIndex: number, templateId: any) => {
    if (!templateId) return
    const template = templates.value.find(t => t.id.toString() === templateId.toString())
    if (!template) {
        console.warn(`[WorkflowStepCalculator] Template not found:`, templateId)
        return
    }


    // Atualizar etapa de forma mais direta
    const updatedSteps = [...stepsData.value]
    const currentStep = updatedSteps[stepIndex]

    // Atualizar propriedades individualmente
    currentStep.template_id = template.id
    currentStep.estimated_duration_days = Number(template.estimated_duration_days) || 1


    // Se não é a primeira etapa e temos dados da etapa anterior
    if (!isFirstStep(stepIndex)) {
        const previousStep = getPreviousStepData(stepIndex)
        if (previousStep?.completed_date) {
            const expectedDate = calculateExpectedDate(previousStep.completed_date)
            currentStep.expected_date = expectedDate

            // Calcular data de conclusão
            if (expectedDate && currentStep.estimated_duration_days > 0) {
                currentStep.completed_date = calculateCompletedDate(expectedDate, currentStep.estimated_duration_days)
            }
        }
    } else {
        // Para a primeira etapa, definir data esperada como hoje se não tiver
        if (!currentStep.expected_date) {
            const today = new Date().toISOString().split('T')[0]
            currentStep.expected_date = today
        }

        // Calcular data de conclusão se temos duração
        if (currentStep.expected_date && currentStep.estimated_duration_days > 0) {
            currentStep.completed_date = calculateCompletedDate(currentStep.expected_date, currentStep.estimated_duration_days)
        }
    }

    // Forçar reatividade completa
    stepsData.value = [...updatedSteps]



    recalculateSubsequentSteps(stepIndex)
}

// Handler para mudança de data esperada
const handleExpectedDateChange = (stepIndex: number, date: string | number) => {
    const dateStr = date.toString()
    const updatedSteps = [...stepsData.value]
    updatedSteps[stepIndex].expected_date = dateStr

    // Calcular data de conclusão se temos duração estimada
    if (dateStr && updatedSteps[stepIndex].estimated_duration_days > 0) {
        updatedSteps[stepIndex].completed_date = calculateCompletedDate(dateStr, updatedSteps[stepIndex].estimated_duration_days)
    }

    stepsData.value = updatedSteps
    recalculateSubsequentSteps(stepIndex)
}

// Handler para mudança de duração estimada
const handleDurationChange = (stepIndex: number, duration: string | number) => {
    const durationNum = typeof duration === 'string' ? parseInt(duration) : duration
    const updatedSteps = [...stepsData.value]
    updatedSteps[stepIndex].estimated_duration_days = durationNum

    // Recalcular data de conclusão se temos data esperada
    if (updatedSteps[stepIndex].expected_date && durationNum > 0) {
        updatedSteps[stepIndex].completed_date = calculateCompletedDate(updatedSteps[stepIndex].expected_date!, durationNum)
    }

    stepsData.value = updatedSteps
    recalculateSubsequentSteps(stepIndex)
}

// Função para recalcular etapas subsequentes
const recalculateSubsequentSteps = (fromIndex: number) => {
    const updatedSteps = [...stepsData.value]

    for (let i = fromIndex + 1; i < updatedSteps.length; i++) {
        const currentStep = updatedSteps[i]
        const previousStep = updatedSteps[i - 1]

        if (currentStep.template_id && previousStep.completed_date) {
            const expectedDate = calculateExpectedDate(previousStep.completed_date)
            currentStep.expected_date = expectedDate

            if (currentStep.estimated_duration_days > 0) {
                currentStep.completed_date = calculateCompletedDate(expectedDate, currentStep.estimated_duration_days)
            }
        }
    }

    stepsData.value = updatedSteps
}

// Formatação de data para exibição
const formatDate = (dateString: string | null | any): string => {
    if (!dateString) return ''

    // Se for um objeto, tentar extrair a string de data
    if (typeof dateString === 'object') {
        // Tentar diferentes propriedades comuns
        if (dateString.completed_date) return formatDate(dateString.completed_date)
        if (dateString.date) return formatDate(dateString.date)
        if (dateString.value) return formatDate(dateString.value)
        return ''
    }

    try {
        // Evitar problemas de timezone usando formatação direta da string
        const dateStr = dateString.toString()

        // Se está no formato YYYY-MM-DD, formatar diretamente
        if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
            const [year, month, day] = dateStr.split('-')
            return `${day}/${month}/${year}`
        }

        // Fallback para outros formatos usando Date com timezone local
        const date = new Date(dateStr + 'T12:00:00') // Adicionar horário para evitar timezone issues

        if (isNaN(date.getTime())) {
            console.warn('[WorkflowStepCalculator] Invalid date:', dateString)
            return ''
        }

        return date.toLocaleDateString('pt-BR')
    } catch (error) {
        console.error('[WorkflowStepCalculator] Error formatting date:', error, dateString)
        return ''
    }
}

// Lifecycle
onMounted(() => {
    if (props.field.templatesApiUrl) {
        loadTemplates()
    }

    // Inicializar com pelo menos uma etapa se estiver vazio
    if (stepsData.value.length === 0) {
        addStep()
    }
})

// Watcher para sincronizar mudanças entre etapas
watch(() => stepsData.value, (newSteps) => {
    // Garantir que step_order está correto
    newSteps.forEach((step, index) => {
        step.step_order = index + 1
    })

}, { deep: true })

// Debug computed para verificar templates
const debugInfo = computed(() => {
    return {
        stepsCount: stepsData.value.length,
        templatesCount: templates.value.length,
        steps: stepsData.value.map((step, index) => ({
            index,
            template_id: step.template_id,
            template_name: getTemplate(step.template_id)?.name || 'Not found'
        }))
    }
})



// Watcher para sincronizar quando props.modelValue mudar (APENAS inicialização)
watch(() => props.modelValue, (newValue) => {
    if (newValue && Array.isArray(newValue)) {
        // Sincronizar selectValues APENAS se estiver vazio (inicialização)
        newValue.forEach((step, index) => {
            const currentValue = step.template_id?.toString() || ''
            if (!selectValues.value[index] && currentValue) {
                selectValues.value[index] = currentValue
            }
        })
    }
}, { deep: true, immediate: true })

// Watcher para sincronizar selectValues com stepsData (APENAS quando vem de fora)
watch(() => stepsData.value, (newSteps) => {
    newSteps.forEach((step, index) => {
        const currentValue = step.template_id?.toString() || ''
        // APENAS sincronizar se o selectValues estiver vazio (inicialização)
        if (!selectValues.value[index] && currentValue) {
            selectValues.value[index] = currentValue
        }
    })
}, { deep: true, immediate: true })
</script>

<template>
    <div class="space-y-4">
        <!-- Timeline Header - Resumo Elegante do Workflow -->
        <div v-if="workflowSummary.totalSteps > 0"
            class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-xl p-6 shadow-sm">

            <!-- Cabeçalho do Timeline -->
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Timeline do Workflow</h3>
                        <p class="text-sm text-gray-600">Acompanhe o progresso das etapas</p>
                    </div>
                </div>

                <!-- Progress Ring -->
                <div class="relative w-16 h-16">
                    <svg class="w-16 h-16 transform -rotate-90" viewBox="0 0 64 64">
                        <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="4" fill="none"
                            class="text-gray-200" />
                        <circle cx="32" cy="32" r="28" stroke="currentColor" stroke-width="4" fill="none"
                            class="text-blue-500"
                            :stroke-dasharray="`${(workflowSummary.configuredSteps / workflowSummary.totalSteps) * 175.93} 175.93`" />
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-sm font-bold text-gray-700">
                            {{ Math.round((workflowSummary.configuredSteps / workflowSummary.totalSteps) * 100) }}%
                        </span>
                    </div>
                </div>
            </div>

            <!-- Estatísticas -->
            <div class="grid grid-cols-3 gap-4 mb-4">
                <div class="bg-white rounded-lg p-3 text-center shadow-sm border border-blue-100">
                    <div class="text-2xl font-bold text-blue-600">{{ workflowSummary.totalSteps }}</div>
                    <div class="text-xs text-gray-600 font-medium">Etapas Criadas</div>
                </div>
                <div class="bg-white rounded-lg p-3 text-center shadow-sm border border-green-100">
                    <div class="text-2xl font-bold text-green-600">{{ workflowSummary.configuredSteps }}</div>
                    <div class="text-xs text-gray-600 font-medium">Configuradas</div>
                </div>
                <div class="bg-white rounded-lg p-3 text-center shadow-sm border border-purple-100">
                    <div class="text-2xl font-bold text-purple-600">{{ workflowSummary.stepsWithDates }}</div>
                    <div class="text-xs text-gray-600 font-medium">Com Datas</div>
                </div>
            </div>

            <!-- Timeline Visual das Etapas -->
            <div class="flex items-center gap-2 overflow-x-auto pb-2">
                <div v-for="(step, index) in workflowSummary.steps" :key="step.order" class="flex items-center">
                    <!-- Círculo da etapa -->
                    <div class="flex flex-col items-center">
                        <div :class="[
                            'w-8 h-8 rounded-full border-2 flex items-center justify-center text-xs font-bold transition-all',
                            step.hasTemplate
                                ? 'bg-green-500 border-green-500 text-white shadow-lg'
                                : 'bg-white border-gray-300 text-gray-400'
                        ]">
                            <span v-if="step.hasTemplate">✓</span>
                            <span v-else>{{ step.order }}</span>
                        </div>
                        <span class="text-xs text-gray-600 mt-1 font-medium">{{ step.order }}</span>
                    </div>

                    <!-- Linha conectora -->
                    <div v-if="index < workflowSummary.steps.length - 1" :class="[
                        'w-8 h-0.5 mx-1',
                        step.hasTemplate ? 'bg-green-300' : 'bg-gray-300'
                    ]">
                    </div>
                </div>
            </div>
        </div>

        <!-- Etapas do Workflow -->
        <div v-for="(step, stepIndex) in stepsData" :key="`step-${stepIndex}`" class="relative">
            <!-- Timeline connector (linha vertical) -->
            <div v-if="!isFirstStep(stepIndex)"
                class="absolute -top-4 left-6 w-0.5 h-4 bg-gradient-to-b from-blue-300 to-blue-500"></div>

            <!-- Card principal da etapa -->
            <div class="relative bg-white border-2 rounded-xl shadow-sm hover:shadow-md transition-all duration-200"
                :class="[
                    step.template_id ? 'border-blue-200 bg-blue-50/30' : 'border-gray-200',
                    step.completed_date ? 'border-green-200 bg-green-50/30' : ''
                ]">

                <!-- Indicador circular da etapa -->
                <div class="absolute -left-3 top-6 w-6 h-6 rounded-full border-2 bg-white flex items-center justify-center text-xs font-bold"
                    :class="[
                        step.template_id ? 'border-blue-500 text-blue-600' : 'border-gray-300 text-gray-400',
                        step.completed_date ? 'border-green-500 text-green-600 bg-green-50' : ''
                    ]">
                    {{ stepIndex + 1 }}
                </div>

                <!-- Conteúdo da etapa -->
                <div class="p-4 pl-8">
                    <!-- Cabeçalho da etapa -->
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <h3 class="font-semibold text-gray-900">
                                Etapa {{ stepIndex + 1 }}
                            </h3>
                            <div v-if="getTemplate(step.template_id)" class="flex items-center gap-2">
                                <Badge v-if="getTemplate(step.template_id)?.color"
                                    :variant="getTemplate(step.template_id)?.color as any"
                                    class="w-2 h-2 rounded-full p-0" />
                                <span class="text-sm text-gray-600">{{ getTemplate(step.template_id)?.name }}</span>
                            </div>
                        </div>

                        <!-- Status badges e botão remover -->
                        <div class="flex items-center gap-2">
                            <Badge v-if="step.template_id" variant="secondary" class="text-xs">
                                Configurada
                            </Badge>
                            <Badge v-if="step.completed_date" variant="default" class="text-xs bg-green-600">
                                Calculada
                            </Badge>
                            <Badge v-if="!isFirstStep(stepIndex)" variant="outline" class="text-xs">
                                Auto
                            </Badge>

                            <!-- Botão de remover etapa -->
                            <Button v-if="canRemoveStep" variant="ghost" size="icon" @click="removeStep(stepIndex)"
                                class="h-6 w-6 text-muted-foreground hover:bg-destructive/10 hover:text-destructive"
                                :aria-label="`Remover etapa ${stepIndex + 1}`" type="button">
                                <Trash2 class="h-4 w-4" />
                            </Button>
                        </div>
                    </div>

                    <!-- Informações da etapa anterior -->
                    <div v-if="getPreviousStepData(stepIndex)"
                        class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <div class="flex items-center gap-2 text-sm">
                            <div class="w-4 h-4 rounded-full bg-blue-500 flex items-center justify-center">
                                <ArrowRight class="h-2 w-2 text-white" />
                            </div>
                            <span class="text-blue-700 font-medium">Baseado na etapa anterior:</span>
                            <span class="text-blue-900 font-semibold">{{
                                formatDate(getPreviousStepData(stepIndex)?.completed_date) }}</span>
                        </div>
                    </div>



                    <!-- Campos do workflow em grid elegante -->
                    <div v-if="step.template_id || selectValues[stepIndex]" class="grid grid-cols-12 gap-4">
                        <!-- Template da Etapa -->
                        <div class="col-span-4 space-y-2">
                            <label class="text-sm font-medium text-gray-700">Template da Etapa</label>
                            <Select :key="`select-${stepIndex}-${selectValues[stepIndex] || 'empty'}`"
                                v-model="createSelectModel(stepIndex).value" :disabled="loading">
                                <!-- Debug: {{ step.template_id }} -->
                                <SelectTrigger class="w-full border-gray-300 focus:border-blue-500 mt-1">
                                    <SelectValue placeholder="Selecione um template de etapa">
                                        <div v-if="getTemplate(step.template_id)" class="flex items-center gap-2">
                                            <Badge v-if="getTemplate(step.template_id)?.color"
                                                :variant="getTemplate(step.template_id)?.color as any"
                                                class="w-3 h-3 rounded-full p-0" />
                                            <span>{{ getTemplate(step.template_id)?.name }}</span>
                                        </div>
                                    </SelectValue>
                                </SelectTrigger>

                                <SelectContent>
                                    <div v-if="loading" class="flex items-center justify-center py-4">
                                        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary"></div>
                                        <span class="ml-2 text-sm text-muted-foreground">Carregando templates...</span>
                                    </div>

                                    <SelectItem v-for="template in templates" :key="template.id"
                                        :value="template.id.toString()">
                                        <div class="flex items-center gap-2 w-full">
                                            <Badge v-if="template.color" :variant="template.color as any"
                                                class="w-3 h-3 rounded-full p-0" />
                                            <div class="flex-1">
                                                <div class="font-medium">{{ template.name }}</div>
                                                <div class="text-xs text-muted-foreground">                                                    
                                                    {{ template.description }} • {{ template.estimated_duration_days
                                                        }} dia(s) • {{ template.suggested_order }}ª etapa
                                                </div>
                                            </div>
                                        </div>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Data Esperada -->
                        <div class="col-span-3 space-y-2">
                            <label class="text-sm font-medium text-gray-700 flex items-center gap-2">
                                <Calendar class="h-4 w-4 text-blue-500" />
                                Data Esperada
                            </label>
                            <Input type="date" :model-value="step.expected_date || ''"
                                @update:model-value="(value) => handleExpectedDateChange(stepIndex, value)"
                                :readonly="isExpectedDateReadonly(stepIndex)"
                                :disabled="isExpectedDateReadonly(stepIndex)"
                                class="w-full border-gray-300 focus:border-blue-500" />
                            <p v-if="isExpectedDateReadonly(stepIndex)" class="text-xs text-blue-600 font-medium">
                                ✨ Auto-calculada
                            </p>
                        </div>

                        <!-- Duração Estimada -->
                        <div class="col-span-2 space-y-2">
                            <label class="text-sm font-medium text-gray-700 flex items-center gap-2">
                                <Clock class="h-4 w-4 text-orange-500" />
                                Duração
                            </label>
                            <div class="relative">
                                <Input type="number" min="1" :model-value="step.estimated_duration_days || ''"
                                    @update:model-value="(value) => handleDurationChange(stepIndex, value)"
                                    placeholder="Dias" class="w-full border-gray-300 focus:border-blue-500 pr-12" />
                                <span
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-xs text-gray-500">dias</span>
                            </div>
                        </div>



                        <!-- Data de Conclusão Calculada -->
                        <div v-if="step.completed_date" class="col-span-3 space-y-2">
                            <label class="text-sm font-medium text-gray-700 flex items-center gap-2">
                                <Calendar class="h-4 w-4 text-green-500" />
                                Conclusão Prevista
                            </label>
                            <div
                                class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 p-2 rounded-lg">
                                <div class="text-sm font-semibold text-green-800 flex items-center gap-2">
                                    <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                    {{ formatDate(step.completed_date) }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Fallback quando não há template selecionado -->
                    <div v-else class="space-y-3">
                        <div class="text-center py-8 border-2 border-dashed border-gray-300 rounded-lg bg-gray-50">
                            <div
                                class="w-12 h-12 mx-auto mb-3 bg-blue-100 rounded-full flex items-center justify-center">
                                <Calendar class="h-6 w-6 text-blue-600" />
                            </div>
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Selecione um Template</h4>
                            <p class="text-xs text-gray-500 mb-4">Escolha um template para configurar esta etapa do
                                workflow</p>

                            <div class="max-w-xs mx-auto">
                                <Select :key="`select-fallback-${stepIndex}-${selectValues[stepIndex] || 'empty'}`"
                                    v-model="createSelectModel(stepIndex).value" :disabled="loading">
                                    <SelectTrigger class="w-full border-gray-300 focus:border-blue-500">
                                        <SelectValue placeholder="Selecione um template de etapa" />
                                    </SelectTrigger>

                                    <SelectContent>
                                        <div v-if="loading" class="flex items-center justify-center py-4">
                                            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-primary">
                                            </div>
                                            <span class="ml-2 text-sm text-muted-foreground">Carregando
                                                templates...</span>
                                        </div>

                                        <SelectItem v-for="template in templates" :key="template.id"
                                            :value="template.id.toString()">
                                            <div class="flex items-center gap-2 w-full">
                                                <Badge v-if="template.color" :variant="template.color as any"
                                                    class="w-3 h-3 rounded-full p-0" />
                                                <div class="flex-1">
                                                    <div class="font-medium">{{ template.name }}</div>
                                                    <div class="text-xs text-muted-foreground">
                                                        {{ template.description }} • {{ template.estimated_duration_days
                                                        }} dia(s) • {{ template.suggested_order }}ª etapa
                                                    </div>
                                                </div>
                                            </div>
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botão de adicionar nova etapa -->
        <div v-if="canAddStep" class="flex justify-center mt-6">
            <Button type="button" variant="default" size="sm" @click="addStep" class="flex items-center">
                <Plus class="h-4 w-4 mr-2" />
                <span>{{ field.addButtonLabel || 'Adicionar Etapa' }}</span>
            </Button>
        </div>

        <!-- Informação sobre limites -->
        <div v-if="field.minSteps || field.maxSteps" class="text-xs text-muted-foreground text-center">
            <span v-if="field.minSteps">Mínimo: {{ field.minSteps }} etapas</span>
            <span v-if="field.minSteps && field.maxSteps"> • </span>
            <span v-if="field.maxSteps">Máximo: {{ field.maxSteps }} etapas</span>
        </div>
    </div>
</template>