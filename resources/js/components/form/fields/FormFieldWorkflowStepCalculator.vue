<script setup lang="ts">
import { computed, ref, watch, onMounted, nextTick } from 'vue'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Input } from '@/components/ui/input'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover'
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'
import { Textarea } from '@/components/ui/textarea'
import { Checkbox } from '@/components/ui/checkbox'
import { Calendar, Clock, ArrowRight, Info, Plus, Trash2, User, ChevronDown, Check, Pencil, Save, X, AlertCircle, FileText } from 'lucide-vue-next'

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

interface User {
    id: number;
    name: string;
    email?: string;
    avatar?: string;
}

interface StepData {
    template_id: number | null;
    responsible_user_id: number | null;
    expected_date: string | null;
    completed_date: string | null;
    estimated_duration_days: number;
    step_order: number;
    // Campos adicionais baseados na migration
    name?: string;
    description?: string;
    status?: 'pending' | 'in_progress' | 'completed' | 'cancelled';
    notes?: string;
    is_required?: boolean;
    metadata?: Record<string, any>;
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
        templatesApiUrl?: string | {
            templates: string;
            responsible_users: string;
        };
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
const loadingUsers = ref(false)
const templates = ref<WorkflowTemplate[]>([])
const users = ref<User[]>([])
const openPopovers = ref<Record<number, boolean>>({})
const userSearchQuery = ref<Record<number, string>>({})

// Estados da modal de edição
const editModalOpen = ref(false)
const editingStepIndex = ref<number | null>(null)
const editingStepData = ref<StepData | null>(null)

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
        const templatesUrl = typeof props.field.templatesApiUrl === 'object'
            ? props.field.templatesApiUrl.templates
            : props.field.templatesApiUrl

        const response = await fetch(templatesUrl, {
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

// Carregar usuários via API
const loadUsers = async () => {
    if (!props.field.templatesApiUrl || typeof props.field.templatesApiUrl !== 'object') {
        console.warn('[WorkflowStepCalculator] No users API URL provided')
        return
    }

    loadingUsers.value = true
    try {
        const response = await fetch(props.field.templatesApiUrl.responsible_users, {
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            }
        })

        if (!response.ok) throw new Error('Erro ao carregar usuários')

        const data = await response.json()
        users.value = data.data || data || []

    } catch (error) {
        console.error('[WorkflowStepCalculator] Users API Error:', error)
        users.value = []
    } finally {
        loadingUsers.value = false
    }
}

// Função para obter usuário por ID
const getUser = (userId: number | null): User | null => {
    if (!userId) return null
    return users.value.find(u => u.id === userId) || null
}

// Computed para filtrar usuários baseado na busca
const getFilteredUsers = (stepIndex: number) => {
    const query = userSearchQuery.value[stepIndex]?.toLowerCase() || ''
    if (!query) return users.value

    return users.value.filter(user =>
        user.name.toLowerCase().includes(query) ||
        (user.email && user.email.toLowerCase().includes(query))
    )
}

// Função para alternar popover
const togglePopover = (stepIndex: number) => {
    openPopovers.value = {
        ...openPopovers.value,
        [stepIndex]: !openPopovers.value[stepIndex]
    }

    // Carregar usuários se ainda não foram carregados
    if (!users.value.length && !loadingUsers.value) {
        loadUsers()
    }
}

// Função para fechar popover
const closePopover = (stepIndex: number) => {
    openPopovers.value = {
        ...openPopovers.value,
        [stepIndex]: false
    }
    // Limpar busca ao fechar
    userSearchQuery.value = {
        ...userSearchQuery.value,
        [stepIndex]: ''
    }
}

// Função para selecionar usuário
const selectUser = (stepIndex: number, userId: number) => {
    const updatedSteps = [...stepsData.value]
    updatedSteps[stepIndex].responsible_user_id = userId
    stepsData.value = updatedSteps
    closePopover(stepIndex)
}

// Função para limpar usuário responsável
const clearUser = (stepIndex: number) => {
    const updatedSteps = [...stepsData.value]
    updatedSteps[stepIndex].responsible_user_id = null
    stepsData.value = updatedSteps
    closePopover(stepIndex)
}

// Funções da modal de edição
const openEditModal = (stepIndex: number) => {
    editingStepIndex.value = stepIndex
    const currentStep = stepsData.value[stepIndex]
    
    // Criar cópia com valores padrão para campos opcionais
    editingStepData.value = {
        ...currentStep,
        // Garantir valores padrão para campos opcionais
        name: currentStep.name || getTemplateName(currentStep.template_id) || '',
        description: currentStep.description || '',
        status: currentStep.status || 'pending',
        notes: currentStep.notes || '',
        is_required: currentStep.is_required !== undefined ? currentStep.is_required : true,
        metadata: currentStep.metadata || {}
    }
    
    editModalOpen.value = true
    
    // Carregar usuários se ainda não foram carregados
    if (!users.value.length && !loadingUsers.value) {
        loadUsers()
    }
}

const closeEditModal = () => {
    editModalOpen.value = false
    editingStepIndex.value = null
    editingStepData.value = null
}

const saveEditedStep = () => {
    if (editingStepIndex.value === null || !editingStepData.value) return
    
    const stepIndex = editingStepIndex.value
    const editedData = editingStepData.value
    
    // Validações básicas
    if (!editedData.template_id) {
        alert('Por favor, selecione um template para a etapa.')
        return
    }
    
    if (!editedData.expected_date) {
        alert('Por favor, defina uma data esperada para a etapa.')
        return
    }
    
    // Aplicar dados do template se foi alterado
    const template = getTemplate(editedData.template_id)
    if (template) {
        // Garantir que a duração seja do template se não foi alterada manualmente
        if (!editedData.estimated_duration_days || editedData.estimated_duration_days === 0) {
            editedData.estimated_duration_days = Number(template.estimated_duration_days) || 1
        }
        
        // Definir nome padrão se não foi personalizado
        if (!editedData.name) {
            editedData.name = template.name
        }
        
        // Definir descrição padrão se não foi personalizada
        if (!editedData.description) {
            editedData.description = template.description
        }
        
        // Definir status padrão se não foi definido
        if (!editedData.status) {
            editedData.status = 'pending'
        }
        
        // Definir como obrigatória por padrão se não foi definido
        if (editedData.is_required === undefined) {
            editedData.is_required = true
        }
    }
    
    // Calcular data de conclusão
    if (editedData.expected_date && editedData.estimated_duration_days) {
        editedData.completed_date = calculateCompletedDate(
            editedData.expected_date, 
            editedData.estimated_duration_days
        )
    }
    
    // Atualizar etapa
    const updatedSteps = [...stepsData.value]
    updatedSteps[stepIndex] = { ...editedData }
    stepsData.value = updatedSteps
    
    // Atualizar selectValues para manter sincronização
    selectValues.value = {
        ...selectValues.value,
        [stepIndex]: editedData.template_id?.toString() || ''
    }
    
    // Recalcular etapas subsequentes se necessário
    recalculateSubsequentSteps(stepIndex)
    
    closeEditModal()
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
        responsible_user_id: null,
        expected_date: null,
        completed_date: null,
        estimated_duration_days: 0,
        step_order: stepsData.value.length + 1,
        // Campos extras com valores padrão
        name: '',
        description: '',
        status: 'pending',
        notes: '',
        is_required: true,
        metadata: {}
    }

    // Garantir que o novo índice não tenha valor no selectValues
    const newIndex = stepsData.value.length
    if (selectValues.value[newIndex]) {
        delete selectValues.value[newIndex]
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

    // Limpar e reindexar selectValues
    const newSelectValues: Record<number, string> = {}
    newSteps.forEach((step, i) => {
        if (step.template_id) {
            newSelectValues[i] = step.template_id.toString()
        }
    })
    selectValues.value = newSelectValues

    // Limpar popovers abertos para índices que não existem mais
    const newOpenPopovers: Record<number, boolean> = {}
    newSteps.forEach((_, i) => {
        if (openPopovers.value[i]) {
            newOpenPopovers[i] = openPopovers.value[i]
        }
    })
    openPopovers.value = newOpenPopovers

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
        // Carregar usuários se a URL for um objeto com responsible_users
        if (typeof props.field.templatesApiUrl === 'object') {
            loadUsers()
        }
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

    // Sincronizar selectValues quando stepsData muda
    syncSelectValues()
}, { deep: true })

// Computed para verificar templates já utilizados
const usedTemplateIds = computed(() => {
    return stepsData.value
        .map(step => step.template_id)
        .filter(id => id !== null && id !== undefined)
})

// Função para verificar se um template já está sendo usado em outra etapa
const isTemplateUsed = (templateId: string | number, currentStepIndex: number): boolean => {
    return stepsData.value.some((step, index) => {
        // Permitir que a etapa atual mantenha seu próprio template
        if (index === currentStepIndex) return false
        // Verificar se o template está sendo usado em outra etapa
        return step.template_id?.toString() === templateId.toString()
    })
}

// Função para sincronizar selectValues com stepsData
const syncSelectValues = () => {
    const newSelectValues: Record<number, string> = {}
    stepsData.value.forEach((step, index) => {
        if (step.template_id) {
            newSelectValues[index] = step.template_id.toString()
        }
    })
    selectValues.value = newSelectValues
}





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

                            <!-- Badge do usuário responsável -->
                            <Badge v-if="step.responsible_user_id" variant="outline"
                                class="text-xs bg-purple-50 text-purple-700 border-purple-200">
                                <User class="h-3 w-3 mr-1" />
                                {{ getUser(step.responsible_user_id)?.name }}
                            </Badge>
                                                         <!-- Botão de editar etapa -->
                             <Button variant="ghost" size="icon" title="Editar etapa"
                                 class="h-6 w-6 text-muted-foreground hover:bg-blue-50 hover:text-blue-700"
                                 :aria-label="`Editar etapa ${stepIndex + 1}`" type="button"
                                 @click="openEditModal(stepIndex)">
                                 <Pencil class="h-4 w-4" />
                             </Button>
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
                        <div class="col-span-3 space-y-2">
                            <label class="text-sm font-medium text-gray-700">Template da Etapa</label>
                            <Select :key="`select-${stepIndex}-${selectValues[stepIndex] || 'empty'}`"
                                v-model="createSelectModel(stepIndex).value" :disabled="loading">

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
                                        :value="template.id.toString()"
                                        :disabled="isTemplateUsed(template.id, stepIndex)"
                                        :title="isTemplateUsed(template.id, stepIndex) ? 'Este template já está sendo usado em outra etapa' : ''">
                                        <div class="flex items-center gap-2 w-full"
                                            :class="{ 'opacity-50 cursor-not-allowed': isTemplateUsed(template.id, stepIndex) }">
                                            <Badge v-if="template.color" :variant="template.color as any"
                                                class="w-3 h-3 rounded-full p-0" />
                                            <div class="flex-1">
                                                <div class="font-medium flex items-center gap-2">
                                                    {{ template.name }}
                                                    <Badge v-if="isTemplateUsed(template.id, stepIndex)"
                                                        variant="secondary"
                                                        class="text-xs px-1 py-0 bg-red-100 text-red-700">
                                                        Em uso
                                                    </Badge>
                                                </div>
                                                <div class="text-xs text-muted-foreground">
                                                    {{ template.description }} • {{ template.estimated_duration_days
                                                    }} dia(s) • {{ template.suggested_order }}ª etapa
                                                    <span v-if="isTemplateUsed(template.id, stepIndex)"
                                                        class="text-red-500 font-medium">
                                                        • Já utilizado
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <!-- Data Esperada -->
                        <div class="col-span-2 space-y-2">
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

                        <!-- Usuário Responsável -->
                        <div class="col-span-3 space-y-2">
                            <label class="text-sm font-medium text-gray-700 flex items-center gap-2">
                                <User class="h-4 w-4 text-purple-500" />
                                <span> Responsável </span>
                            </label>
                            <div class="relative">
                                <Popover :open="openPopovers[stepIndex]"
                                    @update:open="(open: boolean) => openPopovers[stepIndex] = open">
                                    <PopoverTrigger as-child>
                                        <Button variant="outline" role="combobox"
                                            :aria-expanded="openPopovers[stepIndex]"
                                            class="w-full justify-between border-gray-300 focus:border-purple-500"
                                            @click="togglePopover(stepIndex)" type="button">
                                            <div v-if="getUser(step.responsible_user_id)"
                                                class="flex items-center gap-2 truncate">
                                                <div
                                                    class="w-5 h-5 bg-purple-100 rounded-full flex items-center justify-center">
                                                    <User class="h-3 w-3 text-purple-600" />
                                                </div>
                                                <span class="text-sm truncate w-full">{{
                                                    getUser(step.responsible_user_id)?.name }}</span>
                                            </div>
                                            <div v-else class="flex items-center gap-2 text-gray-500">
                                                <div
                                                    class="w-5 h-5 bg-gray-100 rounded-full flex items-center justify-center">
                                                    <User class="h-3 w-3 text-gray-400" />
                                                </div>
                                                <span class="text-sm truncate w-full">Selecionar usuário</span>
                                            </div>
                                            <ChevronDown class="ml-2 h-4 w-4 shrink-0 opacity-50" />
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent class="w-80 p-0">
                                        <div class="p-3">
                                            <!-- Campo de busca -->
                                            <div class="mb-3">
                                                <Input v-model="userSearchQuery[stepIndex]"
                                                    placeholder="Buscar usuário..." class="h-8 text-sm" />
                                            </div>

                                            <div v-if="loadingUsers" class="flex items-center justify-center py-4">
                                                <div
                                                    class="animate-spin rounded-full h-4 w-4 border-b-2 border-purple-500">
                                                </div>
                                                <span class="ml-2 text-sm text-gray-600">Carregando usuários...</span>
                                            </div>
                                            <div v-else-if="users.length === 0"
                                                class="py-4 text-center text-sm text-gray-500">
                                                Nenhum usuário encontrado
                                            </div>
                                            <div v-else-if="getFilteredUsers(stepIndex).length === 0"
                                                class="py-4 text-center text-sm text-gray-500">
                                                Nenhum usuário encontrado para "{{ userSearchQuery[stepIndex] }}"
                                            </div>
                                            <div v-else class="max-h-48 overflow-y-auto space-y-1">
                                                <!-- Opção para limpar seleção -->
                                                <div v-if="step.responsible_user_id" @click="clearUser(stepIndex)"
                                                    class="flex items-center gap-2 p-2 rounded-md hover:bg-red-50 cursor-pointer transition-colors border-b border-gray-100 mb-2">
                                                    <div
                                                        class="w-6 h-6 bg-red-100 rounded-full flex items-center justify-center">
                                                        <Trash2 class="h-3 w-3 text-red-600" />
                                                    </div>
                                                    <div class="flex-1">
                                                        <div class="text-sm font-medium text-red-700">Remover
                                                            responsável</div>
                                                        <div class="text-xs text-red-500">Limpar seleção atual</div>
                                                    </div>
                                                </div>

                                                <!-- Lista de usuários -->
                                                <div v-for="user in getFilteredUsers(stepIndex)" :key="user.id"
                                                    @click="selectUser(stepIndex, user.id)"
                                                    class="flex items-center gap-2 p-2 rounded-md hover:bg-purple-50 cursor-pointer transition-colors"
                                                    :class="{ 'bg-purple-100': step.responsible_user_id === user.id }">
                                                    <div
                                                        class="w-6 h-6 bg-purple-100 rounded-full flex items-center justify-center">
                                                        <User class="h-3 w-3 text-purple-600" />
                                                    </div>
                                                    <div class="flex-1 min-w-0">
                                                        <div class="text-sm font-medium text-gray-900 truncate">{{
                                                            user.name }}</div>
                                                        <div v-if="user.email" class="text-xs text-gray-500 truncate">{{
                                                            user.email }}</div>
                                                    </div>
                                                    <Check v-if="step.responsible_user_id === user.id"
                                                        class="h-4 w-4 text-purple-600" />
                                                </div>
                                            </div>
                                        </div>
                                    </PopoverContent>
                                </Popover>
                            </div>
                        </div>



                        <!-- Data de Conclusão Calculada -->
                        <div v-if="step.completed_date" class="col-span-2 space-y-2">
                            <label class="text-sm font-medium text-gray-700 flex items-center gap-2"
                                title="Data de conclusão calculada">
                                <Calendar class="h-4 w-4 text-green-500" />
                                Conclusão
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
                                            :value="template.id.toString()"
                                            :disabled="isTemplateUsed(template.id, stepIndex)"
                                            :title="isTemplateUsed(template.id, stepIndex) ? 'Este template já está sendo usado em outra etapa' : ''">
                                            <div class="flex items-center gap-2 w-full"
                                                :class="{ 'opacity-50 cursor-not-allowed': isTemplateUsed(template.id, stepIndex) }">
                                                <Badge v-if="template.color" :variant="template.color as any"
                                                    class="w-3 h-3 rounded-full p-0" />
                                                <div class="flex-1">
                                                    <div class="font-medium flex items-center gap-2">
                                                        {{ template.name }}
                                                        <Badge v-if="isTemplateUsed(template.id, stepIndex)"
                                                            variant="secondary"
                                                            class="text-xs px-1 py-0 bg-red-100 text-red-700">
                                                            Em uso
                                                        </Badge>
                                                    </div>
                                                    <div class="text-xs text-muted-foreground">
                                                        {{ template.description }} • {{ template.estimated_duration_days
                                                        }} dia(s) • {{ template.suggested_order }}ª etapa
                                                        <span v-if="isTemplateUsed(template.id, stepIndex)"
                                                            class="text-red-500 font-medium">
                                                            • Já utilizado
                                                        </span>
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

    <!-- Modal de Edição de Etapa -->
    <Dialog v-model:open="editModalOpen">
        <DialogContent class="max-w-2xl max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <Pencil class="h-5 w-5 text-blue-600" />
                    Editar Etapa {{ (editingStepIndex ?? 0) + 1 }}
                    <Badge v-if="editingStepData?.template_id" variant="outline" class="ml-2">
                        {{ getTemplateName(editingStepData.template_id) }}
                    </Badge>
                </DialogTitle>
            </DialogHeader>

            <div v-if="editingStepData" class="space-y-6 py-4">
                <!-- Template da Etapa -->
                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-700 flex items-center gap-2">
                        <Calendar class="h-4 w-4 text-blue-500" />
                        Template da Etapa
                    </label>
                                         <Select :model-value="editingStepData.template_id?.toString() || ''" @update:model-value="(value) => editingStepData!.template_id = value ? Number(value) : null" :disabled="loading">
                        <SelectTrigger class="w-full border-gray-300 focus:border-blue-500">
                            <SelectValue placeholder="Selecione um template de etapa" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="template in templates" :key="template.id"
                                :value="template.id.toString()">
                                <div class="flex items-center gap-2 w-full">
                                    <Badge v-if="template.color" :variant="template.color as any"
                                        class="w-3 h-3 rounded-full p-0" />
                                    <div class="flex-1">
                                        <div class="font-medium">{{ template.name }}</div>
                                        <div class="text-xs text-muted-foreground">
                                            {{ template.description }} • {{ template.estimated_duration_days }} dia(s)
                                        </div>
                                    </div>
                                </div>
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Grid com campos principais -->
                <div class="grid grid-cols-2 gap-4">
                    <!-- Data Esperada -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-gray-700 flex items-center gap-2">
                            <Calendar class="h-4 w-4 text-orange-500" />
                            Data Esperada
                        </label>
                        <Input type="date" v-model="editingStepData.expected_date"
                            class="w-full border-gray-300 focus:border-orange-500" />
                    </div>

                    <!-- Duração -->
                    <div class="space-y-2">
                        <label class="text-sm font-medium text-gray-700 flex items-center gap-2">
                            <Clock class="h-4 w-4 text-blue-500" />
                            Duração
                        </label>
                        <div class="relative">
                            <Input type="number" min="1" v-model="editingStepData.estimated_duration_days"
                                placeholder="Dias" class="w-full border-gray-300 focus:border-blue-500 pr-12" />
                            <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-xs text-gray-500">dias</span>
                        </div>
                    </div>
                </div>

                <!-- Usuário Responsável -->
                <div class="space-y-2">
                    <label class="text-sm font-medium text-gray-700 flex items-center gap-2">
                        <User class="h-4 w-4 text-purple-500" />
                        Usuário Responsável
                    </label>
                                         <Select :model-value="editingStepData.responsible_user_id?.toString() || 'null'" @update:model-value="(value) => editingStepData!.responsible_user_id = value === 'null' ? null : Number(value)">
                        <SelectTrigger class="w-full border-gray-300 focus:border-purple-500">
                            <SelectValue placeholder="Selecionar usuário responsável" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="null">
                                <div class="flex items-center gap-2 text-gray-500">
                                    <User class="h-4 w-4" />
                                    Nenhum responsável
                                </div>
                            </SelectItem>
                            <SelectItem v-for="user in users" :key="user.id" :value="user.id.toString()">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 bg-purple-100 rounded-full flex items-center justify-center">
                                        <User class="h-3 w-3 text-purple-600" />
                                    </div>
                                    <div class="flex-1">
                                        <div class="text-sm font-medium">{{ user.name }}</div>
                                        <div v-if="user.email" class="text-xs text-gray-500">{{ user.email }}</div>
                                    </div>
                                </div>
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                                 <!-- Campos Adicionais -->
                 <div class="grid grid-cols-2 gap-4">
                     <!-- Nome da Etapa -->
                     <div class="space-y-2">
                         <label class="text-sm font-medium text-gray-700 flex items-center gap-2">
                             <FileText class="h-4 w-4 text-indigo-500" />
                             Nome da Etapa
                         </label>
                         <Input v-model="editingStepData.name" placeholder="Nome personalizado da etapa"
                             class="w-full border-gray-300 focus:border-indigo-500" />
                     </div>

                     <!-- Status -->
                     <div class="space-y-2">
                         <label class="text-sm font-medium text-gray-700 flex items-center gap-2">
                             <AlertCircle class="h-4 w-4 text-amber-500" />
                             Status
                         </label>
                         <Select v-model="editingStepData.status">
                             <SelectTrigger class="w-full border-gray-300 focus:border-amber-500">
                                 <SelectValue placeholder="Selecionar status" />
                             </SelectTrigger>
                             <SelectContent>
                                 <SelectItem value="pending">
                                     <div class="flex items-center gap-2">
                                         <div class="w-2 h-2 bg-gray-400 rounded-full"></div>
                                         Pendente
                                     </div>
                                 </SelectItem>
                                 <SelectItem value="in_progress">
                                     <div class="flex items-center gap-2">
                                         <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                                         Em Progresso
                                     </div>
                                 </SelectItem>
                                 <SelectItem value="completed">
                                     <div class="flex items-center gap-2">
                                         <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                                         Concluída
                                     </div>
                                 </SelectItem>
                                 <SelectItem value="cancelled">
                                     <div class="flex items-center gap-2">
                                         <div class="w-2 h-2 bg-red-500 rounded-full"></div>
                                         Cancelada
                                     </div>
                                 </SelectItem>
                             </SelectContent>
                         </Select>
                     </div>
                 </div>

                 <!-- Descrição -->
                 <div class="space-y-2">
                     <label class="text-sm font-medium text-gray-700 flex items-center gap-2">
                         <FileText class="h-4 w-4 text-slate-500" />
                         Descrição
                     </label>
                     <Textarea v-model="editingStepData.description" 
                         placeholder="Descrição detalhada da etapa..."
                         class="w-full border-gray-300 focus:border-slate-500 min-h-[80px]" />
                 </div>

                 <!-- Observações -->
                 <div class="space-y-2">
                     <label class="text-sm font-medium text-gray-700 flex items-center gap-2">
                         <Info class="h-4 w-4 text-cyan-500" />
                         Observações
                     </label>
                     <Textarea v-model="editingStepData.notes" 
                         placeholder="Observações, comentários ou instruções especiais..."
                         class="w-full border-gray-300 focus:border-cyan-500 min-h-[60px]" />
                 </div>

                 <!-- Etapa Obrigatória -->
                 <div class="flex items-center space-x-2">
                     <Checkbox v-model:checked="editingStepData.is_required" id="is_required" />
                     <label for="is_required" class="text-sm font-medium text-gray-700 flex items-center gap-2">
                         <AlertCircle class="h-4 w-4 text-red-500" />
                         Esta etapa é obrigatória
                     </label>
                 </div>

                 <!-- Data de Conclusão (calculada) -->
                 <div v-if="editingStepData.completed_date" class="space-y-2">
                     <label class="text-sm font-medium text-gray-700 flex items-center gap-2">
                         <Calendar class="h-4 w-4 text-green-500" />
                         Data de Conclusão Calculada
                     </label>
                     <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 p-3 rounded-lg">
                         <div class="text-sm font-semibold text-green-800 flex items-center gap-2">
                             <div class="w-2 h-2 bg-green-500 rounded-full"></div>
                             {{ formatDate(editingStepData.completed_date) }}
                         </div>
                     </div>
                 </div>
            </div>

            <!-- Botões da Modal -->
            <div class="flex justify-end gap-3 pt-4 border-t">
                <Button variant="outline" @click="closeEditModal" type="button">
                    <X class="h-4 w-4 mr-2" />
                    Cancelar
                </Button>
                <Button @click="saveEditedStep" type="button" class="bg-blue-600 hover:bg-blue-700">
                    <Save class="h-4 w-4 mr-2" />
                    Salvar Alterações
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>