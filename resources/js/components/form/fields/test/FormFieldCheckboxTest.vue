<template>
  <div class="p-6 space-y-6">
    <h2 class="text-2xl font-bold">Teste do FormFieldCheckbox</h2>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Checkbox básico -->
      <div class="space-y-2">
        <h3 class="text-lg font-semibold">Checkbox Básico</h3>
        <FormFieldCheckbox
          id="checkbox-basic"
          :field="{
            name: 'basic',
            label: 'Checkbox Básico',
            description: 'Um checkbox simples'
          }"
          v-model="basicValue"
          @reactive="handleReactive('basic', $event)"
        />
        <p class="text-sm text-gray-600">Valor: {{ basicValue }}</p>
      </div>

      <!-- Checkbox obrigatório -->
      <div class="space-y-2">
        <h3 class="text-lg font-semibold">Checkbox Obrigatório</h3>
        <FormFieldCheckbox
          id="checkbox-required"
          :field="{
            name: 'required',
            label: 'Aceito os termos',
            description: 'Este campo é obrigatório',
            required: true
          }"
          v-model="requiredValue"
          @reactive="handleReactive('required', $event)"
        />
        <p class="text-sm text-gray-600">Valor: {{ requiredValue }}</p>
      </div>

      <!-- Checkbox desabilitado -->
      <div class="space-y-2">
        <h3 class="text-lg font-semibold">Checkbox Desabilitado</h3>
        <FormFieldCheckbox
          id="checkbox-disabled"
          :field="{
            name: 'disabled',
            label: 'Opção Desabilitada',
            description: 'Este checkbox está desabilitado',
            disabled: true
          }"
          v-model="disabledValue"
          @reactive="handleReactive('disabled', $event)"
        />
        <p class="text-sm text-gray-600">Valor: {{ disabledValue }}</p>
      </div>

      <!-- Checkbox com valor null -->
      <div class="space-y-2">
        <h3 class="text-lg font-semibold">Checkbox com Null</h3>
        <FormFieldCheckbox
          id="checkbox-null"
          :field="{
            name: 'null',
            label: 'Checkbox com Null',
            description: 'Testando valores null'
          }"
          v-model="nullValue"
          @reactive="handleReactive('null', $event)"
        />
        <p class="text-sm text-gray-600">Valor: {{ nullValue }}</p>
        <button 
          @click="nullValue = null"
          class="px-3 py-1 bg-gray-200 rounded text-sm"
        >
          Definir como null
        </button>
      </div>
    </div>

    <!-- Log de eventos -->
    <div class="mt-8">
      <h3 class="text-lg font-semibold mb-2">Log de Eventos</h3>
      <div class="bg-gray-100 p-4 rounded max-h-40 overflow-y-auto">
        <div v-for="(log, index) in eventLog" :key="index" class="text-sm font-mono">
          {{ log }}
        </div>
      </div>
      <button 
        @click="eventLog = []"
        class="mt-2 px-3 py-1 bg-red-200 rounded text-sm"
      >
        Limpar Log
      </button>
    </div>

    <!-- Valores atuais -->
    <div class="mt-8">
      <h3 class="text-lg font-semibold mb-2">Valores Atuais</h3>
      <pre class="bg-gray-100 p-4 rounded text-sm">{{ JSON.stringify({
        basic: basicValue,
        required: requiredValue,
        disabled: disabledValue,
        null: nullValue
      }, null, 2) }}</pre>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import FormFieldCheckbox from '../FormFieldCheckbox.vue'

// Valores dos checkboxes
const basicValue = ref(false)
const requiredValue = ref(false)
const disabledValue = ref(true)
const nullValue = ref<boolean | null>(null)

// Log de eventos
const eventLog = ref<string[]>([])

// Handler para eventos reactive
const handleReactive = (name: string, value: boolean | null) => {
  const timestamp = new Date().toLocaleTimeString()
  eventLog.value.unshift(`[${timestamp}] ${name}: ${value}`)
  
  // Manter apenas os últimos 20 logs
  if (eventLog.value.length > 20) {
    eventLog.value = eventLog.value.slice(0, 20)
  }
}
</script>
