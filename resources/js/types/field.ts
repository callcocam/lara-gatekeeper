
// DEFINE and EXPORT types locally (ou mover para um arquivo types.ts dentro do pacote)
export interface FieldConfig {
    name: string;
    label: string;
    type: string;
    required?: boolean;
    description?: string;
    options?: Record<string, string>;
    row?: number;
    colSpan?: number;
    // Allow any other props needed by specific field components
    [key: string]: any;
}

export interface FormErrors {
    [key: string]: string; // Erros do Inertia são string, não array
}

export interface FormValues {
    [key: string]: any;
}
export interface ActionProps {
    action: {
        id: string;
        label: string;
        icon?: string;
        color?: string;
        url?: string;
        component?: string;
        iconPosition?: string;
        size: "default" | "sm" | "lg" | "icon" | null | undefined;
        variant?: "default" | "destructive" | "outline" | "secondary" | "ghost" | "link";
        method?: "GET" | "POST" | "PUT" | "DELETE";
        confirm?: {
            title: string;
            description: string;
            confirmButtonText: string;
            cancelButtonText: string;
            confirmIcon?: string;
            cancelIcon?: string;
        };
        modal: {
            fields: Array<FieldConfig>;
            modalHeading: string;
            modalDescription?: string;
            modalConfirmButtonText?: string;
            modalCancelButtonText?: string;
            [key: string]: any;
        };
    };
}