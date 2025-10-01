import type { App, Component } from 'vue'; 

// Importar InjectionKeys
import { formatterRegistryKey, fieldRegistryKey } from './injectionKeys';

// Importar Registros Padrão
import * as defaultFormatters from './utils/columnFormatters';
import { defaultFieldComponents } from './components/gt-form/fieldRegistry';
import type { FieldRegistry } from './components/gt-form/fieldRegistry';

// --- Form Components --- 
// import DynamicForm from './components/form/DynamicForm.vue';
// import FormFieldWrapper from './components/form/FormFieldWrapper.vue';
// Import *all* individual field components: 

// --- Table Components --- 
// import ServerSideDataTable from './components/table/ServerSideDataTable.vue'; 
// import DataTableColumnHeader from './components/table/DataTableColumnHeader.vue';
// import DataTableViewOptions from './components/table/DataTableViewOptions.vue';
// import ConfirmationModal from './components/ConfirmationModal.vue';

// Tipagem para as opções do plugin
interface LaraGatekeeperPluginOptions {
  customFormatters?: Record<string, Function>;
  customFields?: FieldRegistry;
  customComponents?: Record<string, Component>;
}

// Export the plugin install function
const install = (app: App, options: LaraGatekeeperPluginOptions = {}): void => {

    console.log('[LaraGatekeeperPlugin] Installing plugin with options:', options);
    // --- Registro de Formatadores ---
    const { renderFormattedCell, ...usableFormatters } = defaultFormatters;
    const finalFormatters = { 
        ...usableFormatters, 
        ...(options.customFormatters || {}) 
    };
    app.provide(formatterRegistryKey, finalFormatters); 

    // --- Registro de Campos de Formulário ---
    const finalFields = { 
        ...defaultFieldComponents, 
        ...(options.customFields || {}) 
    };
    app.provide(fieldRegistryKey, finalFields); 

    // --- Registro de Componentes Globais Essenciais ---
    registerUIComponents(app);
    // app.component('DynamicForm', DynamicForm);
    // app.component('FormFieldWrapper', FormFieldWrapper);
    // app.component('ServerSideDataTable', ServerSideDataTable);
    // app.component('DataTableColumnHeader', DataTableColumnHeader);
    // app.component('DataTableViewOptions', DataTableViewOptions);
    // app.component('ConfirmationModal', ConfirmationModal);
    // console.log('[LaraGatekeeperPlugin] Essential components registered globally.');
}

// Register Form Components

/**
 * Registra automaticamente todos os componentes UI
 */
const registerUIComponents = (app: App): void => {
    const componentRegistry: string[] = [];

    // Importação de componentes UI usando glob do Vite
    Object.entries<{ default: Component }>(
        import.meta.glob<{ default: Component }>('./components/**/*.vue', { eager: true })
    ).forEach(([path, definition]) => {
        const componentFileName = path.split('/').pop() || '';
        const originalName = componentFileName.replace(/\.\w+$/, '');
        if (componentRegistry.indexOf(originalName) === -1) {
            app.component(originalName, definition.default);
            componentRegistry.push(originalName);
            // console.log('originalName', originalName);
        }
    });
}

export default {
    install
};