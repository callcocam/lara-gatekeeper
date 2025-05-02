import { clsx, type ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

// Assume-se que clsx e tailwind-merge estão disponíveis via node_modules da aplicação principal
// (como peer dependencies implícitas ou diretas do pacote)
 
export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
} 