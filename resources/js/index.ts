/// <reference types="vite/client" />

import './../css/app.css';
import type { App, Component } from 'vue' 
import DynamicForm from './components/DynamicForm.vue';
import DataTable from './components/table/DataTable.vue';

// Definição de interfaces para tipagem adequada
export interface PluginOptions {
    baseUrl?: string;
    tenant?: string | null;
    apiPath?: string;
    csrfToken?: string;
    [key: string]: any;
}

export interface ComponentMap {
    [key: string]: Component;
} 

/**
 * Função de instalação do plugin Plannerate
 * @param app Instância do aplicativo Vue
 * @param options Opções de configuração do plugin
 */
const install = (app: App, options: PluginOptions = {}): void => {
    // Registro de componentes globais
    registerMainComponents(app);

    // Registro automático de componentes UI
    registerUIComponents(app); 

    // Configuração global
    app.config.globalProperties.$plannerate = options;

    // Configuração de injeção para acesso em componentes
    app.provide('plannerateOptions', options);
}

/**
 * Registra os componentes principais do Plannerate
 */
const registerMainComponents = (app: App): void => {
    app.component('DynamicForm', DynamicForm);
    app.component('v-dynamic-form', DynamicForm);
    app.component('DataTable', DataTable);
    app.component('v-datatable', DataTable);
}

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
            console.log('originalName', originalName);
        }
    });
}

// Exportando o plugin com método install
export default {
    install
}