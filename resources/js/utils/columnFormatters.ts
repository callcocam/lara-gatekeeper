import { h, inject } from 'vue';
import { format } from 'date-fns';
// Ajustar caminho relativo para o Badge dentro do pacote
import { Badge } from '../components/ui/badge'; 
import { formatterRegistryKey } from '../injectionKeys';

// --- Funções Helper de Formatação ---

export const formatDate = (value: any, options?: any): string => {
    if (!value) return '';
    try {
        const formatString = typeof options === 'string' ? options : options?.format || 'dd/MM/yyyy';
        return format(new Date(value), formatString);
    } catch (e) {
        console.error("Erro ao formatar data:", value, e);
        return String(value); // Retorna o valor original em caso de erro
    }
};

export const formatDateTime = (value: any, options?: any): string => {
    if (!value) return '';
    try {
        const formatString = typeof options === 'string' ? options : options?.format || 'dd/MM/yyyy HH:mm';
        return format(new Date(value), formatString);
    } catch (e) {
        console.error("Erro ao formatar data/hora:", value, e);
        return String(value);
    }
};

export const renderBadge = (value: any, options?: Record<string, string>) => {
    if (value === null || value === undefined) return '';
    const valueStr = String(value);
    // Mapeamento de valor para variante do Badge (pode vir das opções)
    const variant = options && options[valueStr] ? options[valueStr] : 'default';
    // @ts-ignore // Ignorar erro de tipo para variant dinâmica
    return h(Badge, { variant: variant }, () => valueStr);
};

export const formatCurrency = (value: any, options?: any): string => {
    if (value === null || value === undefined) return '';
    const numericValue = Number(value);
    if (isNaN(numericValue)) {
        console.error("Erro ao formatar moeda: valor não numérico", value);
        return String(value);
    }
    const locale = options?.locale || 'pt-BR';
    const currency = options?.currency || 'BRL';
    try {
        return new Intl.NumberFormat(locale, { style: 'currency', currency: currency }).format(numericValue);
    } catch(e) {
        console.error("Erro ao formatar moeda:", value, e);
        return String(value);
    }
};

export const renderLink = (value: any, options?: { href?: string, text?: string, target?: string, template?: string }) => {
    if (!value && !options?.text) return '';

    // Se href for uma template string (ex: /users/{id}/edit)
    let finalHref = options?.href ?? '#';
    if (options?.template && value) {
         finalHref = options.template.replace(/\{value\}/g, String(value)); // Substitui {value}
         // Adicionar lógica para substituir outras chaves se necessário (ex: info.row.original.id)
    } else if (typeof value === 'string' && value.startsWith('http')) {
         finalHref = value; // Assume que o próprio valor é a URL
    }


    const text = options?.text ?? String(value);
    const target = options?.target ?? '_self';

    return h('a', { href: finalHref, target: target, class: 'text-primary hover:underline' }, text);
};

// Adicionar mais helpers aqui (renderTags, renderImages, etc.)


// --- Função Principal de Renderização ---

const renderCellUsingRegistry = (info: any): string | ReturnType<typeof h> => {
    const formatterRegistry = inject(formatterRegistryKey);

    const formatterName = info.column.columnDef.meta?.formatter as string | undefined;
    const formatterOptions = info.column.columnDef.meta?.formatterOptions;
    const value = info.getValue();

    if (!formatterRegistry) {
        console.error('[LaraGatekeeper] Formatter registry not provided!');
        return value === null || value === undefined ? '' : String(value);
    }

    if (formatterName && formatterRegistry[formatterName]) {
        try {
            return formatterRegistry[formatterName](value, formatterOptions, info);
        } catch (e) {
            console.error(`[LaraGatekeeper] Error applying formatter "${formatterName}":`, e, { value, formatterOptions });
            return value === null || value === undefined ? '' : String(value);
        }
    } else {
        if (formatterName) {
             console.warn(`[LaraGatekeeper] Column formatter "${formatterName}" not found in registry.`);
        }
        return value === null || value === undefined ? '' : String(value);
    }
};

export const renderFormattedCell = renderCellUsingRegistry; 