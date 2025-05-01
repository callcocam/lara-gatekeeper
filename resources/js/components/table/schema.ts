import { z } from 'zod'

// Esquema base genérico
export const baseEntitySchema = z.object({
  id: z.string(),
})

// Esquema específico para tarefas
export const taskSchema = baseEntitySchema.extend({
  title: z.string(),
  status: z.string(),
  label: z.string(),
  priority: z.string(),
})

// Esquema específico para usuários
export const userSchema = baseEntitySchema.extend({
  name: z.string(),
  email: z.string(),
  status: z.string().optional(),
  tenant_id: z.string().nullable().optional(),
  tenant: z.object({
    id: z.string(),
    name: z.string(),
  }).optional(),
})

// Esquema específico para produtos
export const productSchema = baseEntitySchema.extend({
  name: z.string(),
  price: z.number().optional(),
  stock: z.number().optional(),
  status: z.string(),
  category_id: z.string().nullable().optional(),
})

export type BaseEntity = z.infer<typeof baseEntitySchema>
export type Task = z.infer<typeof taskSchema>
export type User = z.infer<typeof userSchema>
export type Product = z.infer<typeof productSchema>

// Tipo utilitário para dados genéricos da tabela
export type TableData = BaseEntity & Record<string, any>