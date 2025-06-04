export interface BackendColumnDef {
    accessorKey: string;
    header: string;
    sortable?: boolean;
    searchable?: boolean;
    filterable?: boolean;
    formatter?: string;
    formatterOptions?: any;
    html?: boolean;
    meta?: {
        formatter?: string;
        formatterOptions?: any;
        html?: boolean;
        [key: string]: any;
    };
} 