# Sistema de Importação de Excel

Este componente permite importar dados de arquivos Excel (.xlsx, .xls, .csv) para qualquer tabela do sistema utilizando filas do Laravel para processamento em segundo plano.

## Requisitos

Para utilizar este componente, é necessário instalar os seguintes pacotes:

### Backend (PHP)
```bash
composer require phpoffice/phpspreadsheet
```

### Frontend (JavaScript)
Nenhuma dependência adicional, o componente utiliza Vue.js e axios que já estão presentes no sistema.

## Como usar

### 1. Adicionar o componente ButtomImport em sua view

```vue
<template>
  <div>
    <ButtomImport 
      target-table="nome_da_tabela" 
      @import-click="handleImportClick"
    />
    <ImportExcel 
      ref="importModal"
      target-table="nome_da_tabela"
    />
  </div>
</template>

<script setup>
import ButtomImport from '@/components/import/ButtomImport.vue';
import ImportExcel from '@/components/import/ImportExcel.vue';
import { ref } from 'vue';

const importModal = ref(null);

function handleImportClick() {
  // Obter o elemento modal
  const modalElement = document.getElementById('importExcelModal');
  
  // Inicializar o modal Bootstrap
  const modal = new bootstrap.Modal(modalElement);
  
  // Mostrar o modal
  modal.show();
}
</script>
```

### 2. Certificar-se de que a tabela de destino existe no banco de dados

O sistema de importação tenta mapear automaticamente as colunas do Excel para as colunas da tabela, então é importante que a tabela já exista no banco de dados antes de tentar importar dados.

### 3. Executar fila de processamento

Para que o processamento em segundo plano funcione, é necessário executar o worker de filas do Laravel:

```bash
php artisan queue:work
```

## Configurações

### Opções de importação

O componente `ImportExcel.vue` permite as seguintes opções:

- **Primeira linha contém cabeçalho**: Quando marcado, a primeira linha do arquivo Excel será interpretada como cabeçalho e não como dados.
- **Pular validação**: Quando marcado, a validação dos dados não será realizada antes da inserção no banco de dados.

## Customização

### Adaptando para tabelas específicas

Para adaptar o sistema para uma tabela específica, você pode criar um serviço que estende o `ImportService` e sobrescrever os métodos necessários:

```php
<?php

namespace App\Services;

class UserImportService extends ImportService
{
    /**
     * Sobrescrever o método de validação para usuários
     */
    protected function validateRow(array $data, string $tableName): array
    {
        $errors = [];
        
        // Validar e-mail
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'E-mail inválido';
        }
        
        // Outras validações específicas
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
```

Em seguida, registre este serviço no container de dependências do Laravel (`AppServiceProvider`):

```php
$this->app->when(\App\Jobs\ImportExcelJob::class)
          ->needs(\App\Services\ImportService::class)
          ->give(function () {
              return new \App\Services\UserImportService();
          });
```

## Tratamento de Erros

O sistema registra erros no log do Laravel e também os retorna para o frontend, que os exibe no modal de importação. 