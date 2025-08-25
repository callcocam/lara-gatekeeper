<?php

// Exemplo de uso do SelectFilter melhorado

use Callcocam\LaraGatekeeper\Core\Support\Filters\SelectFilter;

// Exemplo 1: Filtro simples com opções manuais
SelectFilter::make('status', 'Status')
    ->options([
        ['label' => 'Ativo', 'value' => 'active'],
        ['label' => 'Inativo', 'value' => 'inactive'],
        ['label' => 'Pendente', 'value' => 'pending'],
    ]);

// Exemplo 2: Filtro múltiplo com opções manuais
SelectFilter::make('tags', 'Tags')
    ->multiple()
    ->options([
        ['label' => 'Importante', 'value' => 'important'],
        ['label' => 'Urgente', 'value' => 'urgent'],
        ['label' => 'Baixa Prioridade', 'value' => 'low'],
    ]);

// Exemplo 3: Filtro com relacionamento (carrega opções automaticamente)
SelectFilter::make('category_id', 'Categoria')
    ->relationship('category', 'name', 'id');

// Exemplo 4: Filtro múltiplo com relacionamento
SelectFilter::make('user_ids', 'Usuários')
    ->multiple()
    ->relationship('users', 'name', 'id');

// Exemplo 5: Filtro com opções e contadores
SelectFilter::make('priority', 'Prioridade')
    ->options([
        ['label' => 'Alta', 'value' => 'high', 'count' => 15],
        ['label' => 'Média', 'value' => 'medium', 'count' => 30],
        ['label' => 'Baixa', 'value' => 'low', 'count' => 8],
    ]);

// Exemplo 6: Filtro com formatação customizada
SelectFilter::make('department_id', 'Departamento')
    ->relationship('department', 'name', 'id')
    ->formatUsing(function ($query, $value) {
        // Lógica customizada se necessário
        if (is_array($value)) {
            $query->whereHas('department', function ($q) use ($value) {
                $q->whereIn('id', $value);
            });
        } else {
            $query->whereHas('department', function ($q) use ($value) {
                $q->where('id', $value);
            });
        }
    });
