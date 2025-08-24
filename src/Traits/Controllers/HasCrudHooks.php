<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Traits\Controllers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait HasCrudHooks
{
    /**
     * Hook executado antes de armazenar um novo modelo
     */
    protected function beforeStore(array $validatedData, Request $request): array
    {
        return $validatedData;
    }

    /**
     * Hook executado após armazenar um novo modelo
     */
    protected function afterStore(?Model $model, array $validatedData, Request $request): void
    {
        // Implementação específica nos controllers filhos
    }

    /**
     * Hook executado antes de atualizar um modelo
     */
    protected function beforeUpdate(array $validatedData, Request $request, ?Model $modelInstance = null): array
    {
        return $validatedData;
    }

    /**
     * Hook executado após atualizar um modelo
     */
    protected function afterUpdate(?Model $model, array $validatedData, Request $request): void
    {
        // Implementação específica nos controllers filhos
    }

    /**
     * Hook executado antes de excluir um modelo
     */
    protected function beforeDestroy(Model $modelInstance): void
    {
        // Implementação específica nos controllers filhos
    }

    /**
     * Hook executado após excluir um modelo
     */
    protected function afterDestroy(Model $modelInstance): void
    {
        // Implementação específica nos controllers filhos
    }

    /**
     * Processa os dados para atualização
     */
    protected function getDataToUpdate(array $validatedData, Model $modelInstance): array
    {
        return $validatedData;
    }

    /**
     * Processa dados antes do store/update (senhas, user_id, etc.)
     */
    protected function processCommonData(array $validatedData, Request $request, bool $isUpdate = false): array
    {
        // Lógica para tratar senha
        if (isset($validatedData['password'])) {
            if ($isUpdate && empty($validatedData['password'])) {
                unset($validatedData['password']); // Remove senha vazia no update
            } else {
                $validatedData['password'] = bcrypt($validatedData['password']);
            }
        }

        // Adicionar user_id se não existir
        if (!isset($validatedData['user_id']) && !$isUpdate) {
            $validatedData['user_id'] = $request->user()->id;
        }

        return $validatedData;
    }
}
