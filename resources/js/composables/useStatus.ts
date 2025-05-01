import { h } from 'vue'
import { Badge } from '@/components/ui/badge'

export interface StatusOption {
    value: string
    label: string
    color?: string
}

// Mapeamento padrão de cores por status
const defaultStatusColors: Record<string, string> = {
    active: 'bg-green-500/20 text-green-700 hover:bg-green-500/10',
    inactive: 'bg-red-500/20 text-red-700 hover:bg-red-500/10',
    pending: 'bg-yellow-500/20 text-yellow-700 hover:bg-yellow-500/10',
    blocked: 'bg-gray-500/20 text-gray-700 hover:bg-gray-500/10',
    draft: 'bg-blue-500/20 text-blue-700 hover:bg-blue-500/10',
    published: 'bg-green-500/20 text-green-700 hover:bg-green-500/10',
}

// Tipos comuns de status
export const userStatuses: StatusOption[] = [
    { value: 'published', label: 'Ativo', color: defaultStatusColors.active },
    { value: 'draft', label: 'Inativo', color: defaultStatusColors.draft }, 
]

export const contentStatuses: StatusOption[] = [
    { value: 'draft', label: 'Rascunho', color: defaultStatusColors.draft },
    { value: 'published', label: 'Publicado', color: defaultStatusColors.published },
]

/**
 * Encontra um StatusOption pelo seu valor
 */
export function getStatusByValue(value: string, collection: StatusOption[] = userStatuses): StatusOption | undefined {
    return collection.find(status => status.value === value)
}

/**
 * Renderiza um componente Badge para o status
 */
export function renderStatusBadge(value: string, collection: StatusOption[] = userStatuses) {
    const status = getStatusByValue(value, collection)
    if (!status) return value

    return h(Badge, { class: status.color || defaultStatusColors.published }, () => status.label)
}

export function useStatus() {
    return {
        userStatuses,
        contentStatuses,
        getStatusByValue,
        renderStatusBadge,
        defaultStatusColors
    }
}

export default useStatus 