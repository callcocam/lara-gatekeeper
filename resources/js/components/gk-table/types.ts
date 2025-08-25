export interface FilterOption {
    label: string;
    value: string | number;
    icon?: any;
    count?: number;
}

export interface BaseFilter {
    id: string;
    label: string;
    name: string;
    component: any;
    modelValue?: string | number | string[] | number[];
}

export interface SelectFilter extends BaseFilter {
    options: FilterOption[];
}

export interface FacetedFilter extends BaseFilter {
    options: FilterOption[];
    multiple?: boolean;
}
