// Registro central dos componentes de campo padrão
 
// Tipar para garantir consistência (Opcional, mas bom)
import type { Component } from 'vue';
import FormFieldInput from './fields/FormFieldInput.vue';
import FormFieldTextarea from './fields/FormFieldTextarea.vue';
import FormFieldSelect from './fields/FormFieldSelect.vue';
import FormFieldCombobox from './fields/FormFieldCombobox.vue';
import FormFieldRichText from './fields/FormFieldRichText.vue';
import FormFieldFile from './fields/FormFieldFile.vue';
import FormFieldRepeater from './fields/FormFieldRepeater.vue';
import FormFieldModalSelect from './fields/FormFieldModalSelect.vue';
import FormFieldRadioGroup from './fields/FormFieldRadioGroup.vue';
import FormFieldCheckboxList from './fields/FormFieldCheckboxList.vue'; 
import FormFieldSwitch from './fields/FormFieldSwitch.vue';
import FormFieldCheckbox from './fields/FormFieldCheckbox.vue';
import FormFieldTags from './fields/FormFieldTags.vue';
import FormFieldSmartSelect from './fields/FormFieldSmartSelect.vue';
import FormFieldMultiLevelSelect from './fields/FormFieldMultiLevelSelect.vue';
import FormFieldArray from './fields/news/FormFieldArray.vue';

export const defaultFieldComponents = {
    text: FormFieldInput,
    date: FormFieldInput,
    datetime: FormFieldInput,
    time: FormFieldInput,
    tel: FormFieldInput,
    url: FormFieldInput,
    email: FormFieldInput,
    password: FormFieldInput,
    number: FormFieldInput,
    textarea: FormFieldTextarea,
    select: FormFieldSelect,
    combobox: FormFieldCombobox,
    richtext: FormFieldRichText,
    file: FormFieldFile,
    image: FormFieldFile, // 'image' ainda usa FormFieldFile?
    repeater: FormFieldRepeater,
    modalSelect: FormFieldModalSelect,
    radio: FormFieldRadioGroup,
    checkboxList: FormFieldCheckboxList, 
    switch: FormFieldSwitch,
    checkbox: FormFieldCheckbox,
    tags: FormFieldTags,
    smartSelect: FormFieldSmartSelect,
    // workflowStepCalculator: FormFieldWorkflowStepCalculator,
    multiLevelSelect: FormFieldMultiLevelSelect,
    // avatarPreview: FormFieldAvatarPreview, // Remover mapeamento
    // Adicionar outros campos padrão aqui
    array: FormFieldArray,
};

export type FieldRegistry = Record<string, Component>; 