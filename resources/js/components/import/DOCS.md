# Sistema de Importação com Tailwind e Shadcn-Vue

Este componente permite importar dados de arquivos Excel (.xlsx, .xls, .csv) para qualquer tabela do sistema utilizando filas do Laravel para processamento em segundo plano.

## Recursos

- **Importação de diversos formatos**: XLSX, XLS e CSV
- **Processamento assíncrono**: Utiliza filas do Laravel para processamento em background
- **Mapeamento automático**: Detecta automaticamente as colunas baseado nos nomes
- **Mapeamento personalizado**: Interface para mapear manualmente as colunas do Excel para os campos do banco
- **Prévia de dados**: Visualize os dados antes de confirmar a importação
- **Interface moderna**: Componentes Tailwind e shadcn-vue para uma experiência de usuário fluida

## Requisitos

Para utilizar este componente, é necessário instalar os seguintes pacotes:

### Backend (PHP)
```bash
composer require phpoffice/phpspreadsheet
```

### Frontend (JavaScript)
Este componente depende das seguintes bibliotecas:
- [shadcn-vue](https://www.shadcn-vue.com/) - Para os componentes de UI
- [Tailwind CSS](https://tailwindcss.com/) - Para estilização
- [Lucide Vue Next](https://lucide.dev/) - Para ícones

## Como usar

### 1. Adicionar os componentes em sua view

```vue
<template>
  <div>
    <!-- O botão que ativa o modal de importação -->
    <ButtomImport 
      target-table="nome_da_tabela" 
      @import-click="handleImportClick"
      variant="outline"
    />
    
    <!-- O modal de importação (dialog) -->
    <ImportExcel 
      ref="importModal"
      target-table="nome_da_tabela"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import ButtomImport from '@/components/import/ButtomImport.vue';
import ImportExcel from '@/components/import/ImportExcel.vue';

const importModal = ref(null);

function handleImportClick() {
  // Abrir o modal quando o botão for clicado
  if (importModal.value) {
    importModal.value.open();
  }
}
</script>
```

### 2. Certificar-se de que a tabela de destino existe no banco de dados

O sistema de importação tenta mapear automaticamente as colunas do Excel para as colunas da tabela, então é importante que a tabela já exista no banco de dados antes de tentar importar dados.

### 3. Executar fila de processamento

Para que o processamento em segundo plano funcione, é necessário configurar e executar o worker de filas do Laravel:

```bash
php artisan queue:work
```

## Mapeamento de Colunas

O sistema oferece dois modos de mapeamento de colunas:

### Mapeamento Automático

Por padrão, o sistema tenta mapear automaticamente as colunas do Excel para os campos do banco de dados, seguindo estas regras:

1. Correspondência exata (ex: "nome" → "nome")
2. Correspondência parcial (ex: "nome do cliente" → "nome")
3. Correspondência de substrings (ex: "email_usuario" → "email")

### Mapeamento Personalizado

Para usar o mapeamento personalizado:

1. Marque a opção "Personalizar mapeamento de colunas" na tela de importação
2. Clique em "Próximo" para carregar o arquivo e analisar as colunas
3. Na tela de mapeamento, você verá:
   - À esquerda: campos do banco de dados (com indicação de obrigatoriedade)
   - À direita: dropdown para selecionar a coluna do Excel correspondente
   - Prévia dos dados para facilitar a identificação das colunas
4. Após configurar o mapeamento, clique em "Iniciar Importação"

Benefícios do mapeamento personalizado:
- Funciona mesmo quando os nomes das colunas são completamente diferentes
- Permite importação parcial (apenas colunas selecionadas)
- Evita erros de importação devido a mapeamento incorreto

## Customizando a Aparência

### Variantes de botão

O componente `ButtomImport.vue` aceita diferentes variantes de estilo:

```vue
<ButtomImport variant="default" /> <!-- Padrão -->
<ButtomImport variant="destructive" /> <!-- Vermelho -->
<ButtomImport variant="outline" /> <!-- Contorno -->
<ButtomImport variant="secondary" /> <!-- Secundário -->
<ButtomImport variant="ghost" /> <!-- Fantasma -->
<ButtomImport variant="link" /> <!-- Link -->
```

### Tamanhos de botão

```vue
<ButtomImport size="default" /> <!-- Padrão -->
<ButtomImport size="sm" /> <!-- Pequeno -->
<ButtomImport size="lg" /> <!-- Grande -->
<ButtomImport size="icon" /> <!-- Ícone -->
```

## Customização Avançada

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
    
    /**
     * Sobrescrever para adicionar campos calculados ou transformações
     */
    protected function prepareRowData(array $data, array $tableColumns): array
    {
        // Aplicar hash na senha se estiver presente
        if (isset($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }
        
        return $data;
    }
}
```

Em seguida, registre este serviço no container de dependências do Laravel (`ImportServiceProvider`):

```php
// No método register() do ImportServiceProvider

// Para tabelas específicas, você pode condicionar o serviço a ser usado
$this->app->when(ImportExcelJob::class)
    ->needs(ImportService::class)
    ->give(function ($app, $parameters) {
        // Obter a tabela alvo do job
        $targetTable = $parameters['targetTable'] ?? null;
        
        // Retornar o serviço específico dependendo da tabela
        if ($targetTable === 'users') {
            return new \App\Services\UserImportService();
        }
        
        // Retornar o serviço padrão para outras tabelas
        return new ImportService();
    });
```

## Tratamento de Erros

O sistema registra erros no log do Laravel e também os retorna para o frontend, que os exibe no modal de importação. Exemplos de erros tratados:

- Arquivo vazio ou corrompido
- Formato de arquivo inválido
- Colunas obrigatórias ausentes
- Erros de validação por linha
- Problemas de conexão com o banco de dados

## Configurações

O componente `ImportExcel.vue` permite as seguintes opções:

- **Primeira linha contém cabeçalho**: Quando marcado, a primeira linha do arquivo Excel será interpretada como cabeçalho e não como dados.
- **Pular validação**: Quando marcado, a validação dos dados não será realizada antes da inserção no banco de dados.
- **Personalizar mapeamento de colunas**: Quando marcado, permite mapear manualmente as colunas do Excel para os campos do banco de dados. 