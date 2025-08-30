
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

export type ActionPosition = 'top' | 'footer' | 'row';

export type ActionVariant = 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link';

export type ActionSize = 'default' | 'sm' | 'lg' | 'icon';

export type ActionMethod = 'GET' | 'POST' | 'PUT' | 'DELETE';

export type ActionIconPosition = 'left' | 'right' | 'top' | 'bottom';

export type ActionComponent = 'GtButton' | 'GtLink' | 'GtModal' | 'GtConfirmModal' | string;

export type ActionConfirm = {
    title: string;
    description: string;
    confirmButtonText: string;
    cancelButtonText: string;
    confirmIcon?: string;
    cancelIcon?: string;
};

export type ActionModal = {
    fields: FieldConfig[];
    modalHeading: string;
    modalDescription?: string;
    modalConfirmButtonText?: string;
    cancelButtonText?: string;
    confirmButtonText?: string;
    cancelButtonVariant?: ActionVariant;
    confirmButtonVariant?: ActionVariant;
    confirmButtonIcon?: string;
    cancelButtonIcon?: string;
};

export type ActionItemProps = {
    id: string;
    name: string;
    label: string;
    icon?: string;
    color?: string;
    url?: string;
    position?: ActionPosition;
    component?: ActionComponent;
    iconPosition?: ActionIconPosition;
    size?: ActionSize;
    variant?: ActionVariant;
    method?: ActionMethod;
    confirm?: ActionConfirm;
    modal?: ActionModal;
};

export interface ActionProps {
    action: ActionItemProps;
}