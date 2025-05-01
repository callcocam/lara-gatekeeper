import type { App, Component } from 'vue';

// --- Form Components --- 
import DynamicForm from './components/form/DynamicForm.vue';
import FormFieldWrapper from './components/form/FormFieldWrapper.vue';
// Import *all* individual field components:
import FormFieldInput from './components/form/fields/FormFieldInput.vue';
import FormFieldTextarea from './components/form/fields/FormFieldTextarea.vue';
import FormFieldSelect from './components/form/fields/FormFieldSelect.vue';
import FormFieldCombobox from './components/form/fields/FormFieldCombobox.vue';
import FormFieldRichText from './components/form/fields/FormFieldRichText.vue';
import RichTextToolbar from './components/form/fields/RichTextToolbar.vue'; // Toolbar is also needed
import FormFieldFile from './components/form/fields/FormFieldFile.vue';
import FormFieldModalSelect from './components/form/fields/FormFieldModalSelect.vue';
import FormFieldRadioGroup from './components/form/fields/FormFieldRadioGroup.vue';
import FormFieldCheckboxList from './components/form/fields/FormFieldCheckboxList.vue';
import FormFieldRepeater from './components/form/fields/FormFieldRepeater.vue';

// --- Table Components --- 
import ServerSideDataTable from './components/table/ServerSideDataTable.vue';
import DataTableToolbar from './components/table/DataTableToolbar.vue';
import ServerSideDataTablePagination from './components/table/ServerSideDataTablePagination.vue';
import DataTableFacetedFilter from './components/table/DataTableFacetedFilter.vue';
import DataTableViewOptions from './components/table/DataTableViewOptions.vue';

// Export the plugin install function
const install = (app: App): void => {
    
    // Register UI Components
    registerUIComponents(app);
    // Register Form Components
    app.component('DynamicForm', DynamicForm);
    app.component('FormFieldWrapper', FormFieldWrapper);
    // Register Individual Fields 
    app.component('FormFieldInput', FormFieldInput);
    app.component('FormFieldTextarea', FormFieldTextarea);
    app.component('FormFieldSelect', FormFieldSelect);
    app.component('FormFieldCombobox', FormFieldCombobox);
    app.component('FormFieldRichText', FormFieldRichText);
    app.component('RichTextToolbar', RichTextToolbar);
    app.component('FormFieldFile', FormFieldFile);
    app.component('FormFieldModalSelect', FormFieldModalSelect);
    app.component('FormFieldRadioGroup', FormFieldRadioGroup);
    app.component('FormFieldCheckboxList', FormFieldCheckboxList);
    app.component('FormFieldRepeater', FormFieldRepeater);

    // Register Table Components
    app.component('ServerSideDataTable', ServerSideDataTable);
    app.component('DataTableToolbar', DataTableToolbar);
    app.component('ServerSideDataTablePagination', ServerSideDataTablePagination);
    app.component('DataTableFacetedFilter', DataTableFacetedFilter);
    app.component('DataTableViewOptions', DataTableViewOptions);


    console.log('[LaraGatekeeperPlugin] Components registered globally.');
}

// Register Form Components

/**
 * Registra automaticamente todos os componentes UI
 */
const registerUIComponents = (app: App): void => {
    const componentRegistry: string[] = [];

    // Importação de componentes UI usando glob do Vite
    Object.entries<{ default: Component }>(
        import.meta.glob<{ default: Component }>('./components/ui/**/*.vue', { eager: true })
    ).forEach(([path, definition]) => {
        const componentFileName = path.split('/').pop() || '';
        const originalName = componentFileName.replace(/\.\w+$/, '');
        if (componentRegistry.indexOf(originalName) === -1) {
            app.component(originalName, definition.default);
            componentRegistry.push(originalName);
            console.log('originalName', originalName);
        }
    });
}

export default {
    install
};