# DynamicForm e Campos Personalizados

Este diretório contém um sistema de formulário dinâmico para Vue 3, utilizando `vee-validate` para gerenciamento de estado e componentes `shadcn-vue` para a interface.

## Componente Principal: `DynamicForm.vue`

O componente `DynamicForm` (`@/components/DynamicForm.vue`) é o ponto de entrada. Ele renderiza um formulário com base em uma configuração de campos e gerencia os valores e a submissão.

### Como Usar

```vue
<script setup lang="ts">
import { ref, computed } from 'vue';
import DynamicForm from '@/components/DynamicForm.vue';
import type { FieldConfig, FormValues } from '@/components/DynamicForm.vue';
// Importar componentes seletores se usar modalSelect
// import MySelectorComponent from '@/components/selectors/MySelectorComponent.vue';

// Exemplo: Receber configuração do backend (via Inertia props)
const props = defineProps<{
  fieldsConfigFromBackend: FieldConfig[];
  initialData?: Record<string, any>;
  backendErrors?: Record<string, string[]>;
}>();

// Ou definir estaticamente
// const staticFields: FieldConfig[] = [ /* ... sua config aqui ... */ ];

// Processar campos se necessário (ex: para modalSelect)
const processedFields = computed(() => {
    // Lógica para substituir nomes de componentes por componentes importados (ver exemplo em Edit.vue)
    return props.fieldsConfigFromBackend.map(field => {
        // if (field.type === 'modalSelect' && typeof field.selectionComponent === 'string') { ... }
        return field;
    });
});

const initialValues = computed(() => ({
    // Mapear initialData para os nomes dos campos
    field1: props.initialData?.some_value ?? null,
    // ...
    password: '', // Senhas geralmente começam vazias
    someFileField: null // Campos de arquivo começam vazios
}));

const handleFormSubmit = (submittedValues: FormValues) => {
    console.log('Formulário submetido!', submittedValues);

    // IMPORTANTE: Use FormData se houver campos de arquivo!
    const formData = new FormData();
    Object.entries(submittedValues).forEach(([key, value]) => {
        if (value instanceof File) {
            formData.append(key, value, value.name);
        } else if (Array.isArray(value)) { // Para Repeater ou CheckboxList
            formData.append(key, JSON.stringify(value));
        } else if (value !== null && value !== undefined) {
            formData.append(key, String(value));
        }
    });

    // Exemplo: Enviar com Inertia
    // router.post('/seu/endpoint', formData, { onSuccess: ..., onError: ... });
};

</script>

<template>
    <DynamicForm
        :fields="processedFields" 
        :initial-values="initialValues"
        :errors="backendErrors" 
        @submit="handleFormSubmit"
    >
        <template #actions="{ submit }">
            <button type="button" @click="submit">Salvar</button>
            <!-- Outros botões -->
        </template>
    </DynamicForm>
</template>
```

### Props do `DynamicForm`

*   `fields: FieldConfig[]`: (Obrigatório) Array com a configuração de cada campo.
*   `initialValues?: FormValues`: Objeto com os valores iniciais para os campos.
*   `errors?: Record<string, string[]>`: Objeto com os erros de validação vindos do backend (formato padrão do Laravel/Inertia).

### Eventos do `DynamicForm`

*   `@submit (values: FormValues)`: Emitido quando o formulário é submetido com sucesso (internamente usa `handleSubmit` do `vee-validate`). Os `values` são os dados atuais do formulário.

### Slot `#actions`

Permite injetar botões de ação (Salvar, Cancelar, etc.). O slot recebe um objeto com a propriedade `submit`, que é a função interna do `DynamicForm` para disparar a submissão. Use `@click="submit"` no seu botão de salvar.

## Configuração de Campos (`FieldConfig`)

Cada objeto no array `fields` deve ter as seguintes propriedades:

*   `name: string`: (Obrigatório) Nome único do campo (usado como chave nos valores e erros).
*   `label: string`: (Obrigatório) Texto do label exibido para o usuário.
*   `type: string`: (Obrigatório) Tipo do campo (veja tipos suportados abaixo).
*   `description?: string`: Texto de ajuda exibido abaixo do campo.
*   `inputProps?: Record<string, any>`: Objeto com props adicionais a serem passadas diretamente para o componente de input interno (ex: `placeholder`, `aria-label`).
*   `row?: number`: Para layout em grid, número da linha (base 0 ou 1, use consistentemente).
*   `colSpan?: number`: Para layout em grid, número de colunas (1-12) que o campo deve ocupar (padrão 12).
*   `options?: Record<string, string>`: Objeto `{ value: label }` usado pelos tipos `select`, `radio`, `checkboxList`.
*   `apiEndpoint?: string`: URL da API usada pelo tipo `combobox`.
*   `valueKey?: string`: Chave do valor no objeto de opção/item (padrão: `id`) para `combobox`, `modalSelect`.
*   `labelKey?: string`: Chave do label no objeto de opção/item (padrão: `name`) para `combobox`, `modalSelect`.
*   `initialLabel?: string`: Label inicial para `combobox` e `modalSelect` quando `modelValue` está preenchido.
*   `subFields?: FieldConfig[]`: Array de `FieldConfig` para os campos dentro de um `repeater`.
*   `addButtonLabel?: string`: Texto do botão para adicionar itens em um `repeater`.
*   `modalTitle?: string`: Título para o diálogo do `modalSelect`.
*   `selectionComponent?: string | object`: Componente (preferencialmente o objeto importado) a ser usado dentro do `modalSelect`.
*   `componentProps?: Record<string, any>`: Props a serem passadas para o `selectionComponent` no `modalSelect`.
*   `accept?: string`: String de tipos MIME ou extensões aceitas para `file` e `image` (ex: `'image/png, image/jpeg'`, `'.pdf'`).
*   `multiple?: boolean`: Permitir múltiplos arquivos para `file` e `image`.
*   `orientation?: 'vertical' | 'horizontal'`: Layout para `radio` e `checkboxList`.

## Tipos de Campos Suportados (`type`)

*   `'text'`: Input de texto padrão (`<Input>`).
*   `'email'`: Input de email (`<Input type="email">`).
*   `'password'`: Input de senha (`<Input type="password">`).
*   `'number'`: Input numérico (`<Input type="number">`).
*   `'textarea'`: Área de texto (`<Textarea>`).
*   `'select'`: Lista suspensa (`<Select>`). Requer `options`.
*   `'radio'`: Grupo de botões de rádio (`<RadioGroup>`). Requer `options`. Permite `orientation`.
*   `'checkboxList'`: Lista de checkboxes (`<Checkbox>`). Requer `options`. Permite `orientation`. O `modelValue` é um array dos valores selecionados.
*   `'richtext'`: Editor de texto rico (Tiptap + shadcn). `modelValue` é o HTML.
*   `'file'`: Upload de arquivo com drag-and-drop. Permite `accept`, `multiple`. `modelValue` é `File` ou `FileList`.
*   `'image'`: Igual a `'file'`, mas mostra preview de imagem. Permite `accept`, `multiple`. `modelValue` é `File` ou `FileList`.
*   `'combobox'`: Select com busca assíncrona via API (`<Command>`). Requer `apiEndpoint`. Permite `valueKey`, `labelKey`, `initialLabel`, `searchParam`.
*   `'modalSelect'`: Botão que abre um modal para seleção. Requer `modalTitle`, `selectionComponent`. Permite `valueKey`, `labelKey`, `initialLabel`, `componentProps`.
*   `'repeater'`: Permite adicionar/remover grupos de campos aninhados. Requer `subFields`. Permite `addButtonLabel`. O `modelValue` é um array de objetos. **(ATENÇÃO: Componente atualmente com erros pendentes)**.

## Notas Adicionais

*   **Validação:** A validação principal é feita no backend. Os erros retornados pelo backend são exibidos automaticamente.
*   **FormData:** Lembre-se de ajustar sua lógica de submissão para usar `FormData` se o formulário contiver campos do tipo `file` ou `image`.
*   **Repeater:** O componente `FormFieldRepeater.vue` ainda apresenta erros e precisa ser corrigido/refinado.
*   **Modal Select:** O componente `FormFieldModalSelect.vue` requer que você crie e passe um `selectionComponent` funcional que emita o evento `item-selected`.
*   **Tailwind:** Certifique-se que os arquivos de componentes (`.vue`) estão incluídos na configuração `content` do seu `tailwind.config.js` para que as classes de grid (`col-span-*`) sejam geradas. 