interface FilterOption {
    label: string;
    value: string;
    icon?: any;
}

interface Filter {
    title: string;
    options: FilterOption[];
    column: string;
}

export type { FilterOption, Filter };
