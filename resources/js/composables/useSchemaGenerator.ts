// useSchemaGenerator.ts
import * as z from 'zod'
import { computed } from 'vue'

type FieldType = 'string' | 'number' | 'boolean' | 'email' | 'password' | 'date' | 'select'

interface FieldConfig {
    label?: string
    description?: string
    type?: FieldType
    required?: boolean
    minLength?: number
    maxLength?: number
    min?: number
    max?: number
    pattern?: string
    enumValues?: Record<string, string>
    inputProps?: {
        placeholder?: string
        defaultValue?: any
        [key: string]: any
    }
    [key: string]: any
}

type FieldsConfig = Record<string, FieldConfig>

/**
 * Composable to generate Zod schema from field configuration
 */
export function useSchemaGenerator() {
    /**
     * Generate Zod schema from field configuration
     */
    function generateSchema(fieldsConfig: FieldsConfig) {
        const schemaObj: Record<string, any> = {}

        // Process each field in the configuration
        Object.entries(fieldsConfig).forEach(([fieldName, fieldConfig]) => {
            const isRequired = fieldConfig.required !== false
            let fieldSchema: z.ZodTypeAny

            // Determine the type of schema based on field configuration
            const fieldType = fieldConfig.type || 'string'

            switch (fieldType) {
                case 'email':
                    fieldSchema = z.string().email('E-mail inválido')
                    break
                case 'password':
                    fieldSchema = z.string().min(fieldConfig.minLength || 8, `Senha deve ter pelo menos ${fieldConfig.minLength || 8} caracteres`)
                    break
                case 'number':
                    let numberSchema = z.number()
                    if (typeof fieldConfig.min === 'number') {
                        numberSchema = numberSchema.min(fieldConfig.min, `Valor mínimo: ${fieldConfig.min}`)
                    }
                    if (typeof fieldConfig.max === 'number') {
                        numberSchema = numberSchema.max(fieldConfig.max, `Valor máximo: ${fieldConfig.max}`)
                    }
                    fieldSchema = numberSchema
                    break
                case 'boolean':
                    fieldSchema = z.boolean()
                    break
                case 'date':
                    fieldSchema = z.date()
                    break
                case 'select':
                    // For select fields, we could validate against the enum values
                    if (fieldConfig.enumValues) {
                        fieldSchema = z.enum( Object.values(fieldConfig.enumValues) as [string, ...string[]] )
                    } else {
                        fieldSchema = z.string()
                    }
                    break
                default:
                    // Default to string
                    let stringSchema = z.string()
                    if (fieldConfig.minLength) {
                        stringSchema = stringSchema.min(fieldConfig.minLength, `Mínimo de ${fieldConfig.minLength} caracteres`)
                    }
                    if (fieldConfig.maxLength) {
                        stringSchema = stringSchema.max(fieldConfig.maxLength, `Máximo de ${fieldConfig.maxLength} caracteres`)
                    }
                    if (fieldConfig.pattern) {
                        stringSchema = stringSchema.regex(new RegExp(fieldConfig.pattern), 'Formato inválido')
                    }
                    fieldSchema = stringSchema
            }

            // Make field optional if not required
            if (!isRequired) {
                fieldSchema = fieldSchema.optional().or(z.literal(''))
            } else if (fieldName !== 'password' && fieldName !== 'password_confirmation') {
                // Add min validation for required fields except password
                fieldSchema = z.string().min(1, `${fieldConfig.label || fieldName} é obrigatório`)
            }

            // Special case for password confirmation
            if (fieldName === 'password_confirmation') {
                // Make it optional by default, same as password
                fieldSchema = fieldSchema.optional().or(z.literal(''))
            }

            // Add to schema object
            schemaObj[fieldName] = fieldSchema
        })

        // Create and return the schema
        return z.object(schemaObj)
    }

    /**
     * Generate default values from field configuration
     */
    function generateDefaultValues(fieldsConfig: FieldsConfig) {
        const defaultValues: Record<string, any> = {}

        Object.entries(fieldsConfig).forEach(([fieldName, fieldConfig]) => {
            // Get default value from inputProps
            if (fieldConfig.inputProps?.defaultValue !== undefined) {
                defaultValues[fieldName] = fieldConfig.inputProps.defaultValue
            } else {
                // Set empty default based on field type
                switch (fieldConfig.type) {
                    case 'number':
                        defaultValues[fieldName] = 0
                        break
                    case 'boolean':
                        defaultValues[fieldName] = false
                        break
                    case 'date':
                        defaultValues[fieldName] = new Date()
                        break
                    default:
                        defaultValues[fieldName] = ''
                }
            }
        })

        return defaultValues
    }

    return {
        generateSchema,
        generateDefaultValues,
        
    }
}
export type { FieldsConfig }
// Usage example
/*
import { useSchemaGenerator } from '@/composables/useSchemaGenerator'

// Inside component setup
const { generateSchema, generateDefaultValues } = useSchemaGenerator()

const fieldConfig = {
  name: {
    label: 'Nome',
    required: true,
    minLength: 2,
    inputProps: {
      defaultValue: user.name
    }
  },
  // ... other fields
}

const schema = generateSchema(fieldConfig)
const defaultValues = computed(() => generateDefaultValues(fieldConfig))

// Pass to AutoForm
<AutoForm
  :schema="schema"
  :defaultValues="defaultValues"
  :fieldConfig="fieldConfig"
  @submit="handleSubmit"
/>
*/