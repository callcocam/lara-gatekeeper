export interface BackendColumnDef {
    id: string;
    name: string;
    label: string;
    nameFormatter?: string;
    component?: string;
    accessorKey: string;
    header: string;
    sortable?: boolean;
    searchable?: boolean;
    filterable?: boolean;
    formatter?: string;
    formatterOptions?: any;
    html?: boolean;
    options?: any;
    meta?: {
        formatter?: string;
        formatterOptions?: any;
        html?: boolean;
        [key: string]: any;
    };
}

export interface TableCellProps {
    item: Record<string, any>;
    column: BackendColumnDef;
}