import '@tanstack/vue-table';

declare module '@tanstack/vue-table' {
  // Adicionar propriedades customizadas ao meta da coluna
  interface ColumnMeta<TData, TValue> {
    formatter?: string;
    formatterOptions?: any;
    html?: boolean;
    // Adicionar outras propriedades meta customizadas se necessário
  }
} 