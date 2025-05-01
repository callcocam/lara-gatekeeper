interface FilterOption {
    label: string;
    value: string;
    icon?: any; // Manter tipo 'any' por flexibilidade ou definir um tipo de componente Vue
}

interface Filter {
    title: string;
    options: FilterOption[];
    column: string; // A coluna da tabela que este filtro afeta
}

export type { FilterOption, Filter };
