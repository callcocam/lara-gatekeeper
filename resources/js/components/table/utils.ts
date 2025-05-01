import type { Updater } from '@tanstack/vue-table'
import type { ClassValue } from 'clsx'
import type { Ref } from 'vue'
import { clsx } from 'clsx'
import { twMerge } from 'tailwind-merge'
import type { TableData } from './schema'

export function valueUpdater<T>(updaterOrValue: Updater<T>, ref: Ref<T>) {
    if (typeof updaterOrValue === 'function') {
        // @ts-ignore - TypeScript tem dificuldade aqui, mas a lógica está correta
        ref.value = updaterOrValue(ref.value);
    } else {
        ref.value = updaterOrValue;
    }
}

export function formatDate(dateString: string | null | undefined): string {
    if (!dateString) return '—'
    try {
      return new Date(dateString).toLocaleDateString('pt-BR', { 
          day: '2-digit', 
          month: '2-digit', 
          year: 'numeric' 
      });
    } catch (e) {
      console.error("Error formatting date:", dateString, e);
      return 'Data inválida';
    }
}

export function formatDateTime(dateTimeString: string | null | undefined): string {
    if (!dateTimeString) return '—'
     try {
        return new Date(dateTimeString).toLocaleString('pt-BR', {
            day: '2-digit', 
            month: '2-digit', 
            year: 'numeric', 
            hour: '2-digit', 
            minute: '2-digit' 
        });
    } catch (e) {
      console.error("Error formatting datetime:", dateTimeString, e);
      return 'Data inválida';
    }
}

export function formatCurrency(value: number | null | undefined): string {
    if (value === null || value === undefined || isNaN(value)) return '—'
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value)
}

export function getNestedValue(obj: Record<string, any>, path: string, defaultValue: any = '—'): any {
    const value = path.split('.').reduce((o, key) => (o && o[key] !== 'undefined') ? o[key] : undefined, obj);
    return value ?? defaultValue;
}

export function renderRelatedField(row: TableData, path: string, defaultValue = '—') {
    return getNestedValue(row, path, defaultValue);
}