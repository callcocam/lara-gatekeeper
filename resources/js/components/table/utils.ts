import type { Updater } from '@tanstack/vue-table'
import type { ClassValue } from 'clsx'
import type { Ref } from 'vue'
import { clsx } from 'clsx'
import { twMerge } from 'tailwind-merge'
import type { TableData } from './schema'

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs))
}

export function valueUpdater<T>(updaterOrValue: ((old: T) => T) | T, ref: any) {
    ref.value = typeof updaterOrValue === 'function'
        ? (updaterOrValue as (old: T) => T)(ref.value)
        : updaterOrValue
}

export function formatDate(dateString: string | null) {
    if (!dateString) return '—'
    return new Date(dateString).toLocaleDateString('pt-BR')
}

export function formatCurrency(value: number | null | undefined) {
    if (value === null || value === undefined) return '—'
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value)
}

export function renderRelatedField(row: TableData, path: string, defaultValue = '—') {
    const parts = path.split('.')
    let value = row
    
    for (const part of parts) {
        if (value === undefined || value === null) return defaultValue
        value = value[part]
    }
    
    return value || defaultValue
}