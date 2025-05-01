/**
 * Este arquivo existe apenas para compatibilidade com código existente.
 * Para novos componentes, importe diretamente de @/composables/useStatus.
 */

import { userStatuses } from '@/composables/useStatus'
export * from '@/composables/useStatus'

// Para compatibilidade com código existente
export const statuses = userStatuses

// Helper para gerar filtros padrão para um tipo específico de dados
export function getFiltersForEntity(entityType: 'users' | 'products' | 'orders') {
    switch (entityType) {
        case 'users':
            return [
                {
                    title: 'Status',
                    options: userStatuses,
                    column: 'status',
                }
            ]
        case 'products':
            return [
                {
                    title: 'Status',
                    options: statuses, // usa o mesmo que userStatuses neste caso
                    column: 'status',
                }
            ]
        case 'orders':
            return [
                {
                    title: 'Status',
                    options: statuses,
                    column: 'status',
                }
            ]
        default:
            return []
    }
} 