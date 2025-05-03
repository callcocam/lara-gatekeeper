import type { InjectionKey, Component } from 'vue';

// Chave para o registro de formatadores de coluna
export const formatterRegistryKey = Symbol('LaraGatekeeperFormatterRegistry') as InjectionKey<Record<string, Function>>;

// Chave para o registro de componentes de campo de formulário
export const fieldRegistryKey = Symbol('LaraGatekeeperFieldRegistry') as InjectionKey<Record<string, Component>>; 