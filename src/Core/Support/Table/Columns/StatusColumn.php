<?php

/**
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Table\Columns;

use Callcocam\LaraGatekeeper\Core\Support\Column;
use UnitEnum;
use BackedEnum;
use ReflectionClass;

class StatusColumn extends Column
{
    /**
     * Cria uma coluna de status com configuração automática baseada em Enum
     *
     * @param string $label Label da coluna
     * @param string|null $accessorKey Campo do banco (padrão: 'status')
     * @param string|null $enumClass Classe do Enum (será detectada automaticamente se não fornecida)
     * @return static
     */
    public static function make(string $label = 'Status', ?string $accessorKey = null, ?string $enumClass = null): self
    {
        $instance = new static($label, $accessorKey ?? 'status');
        
        // Configura automaticamente o componente para status
        $instance->component('StatusColumn');
        
        // Se um Enum foi fornecido, configura automaticamente
        if ($enumClass) {
            $instance->fromEnum($enumClass);
        }
        
        return $instance;
    }

    /**
     * Configura a coluna baseada em um Enum específico
     *
     * @param string $enumClass Classe do Enum
     * @return static
     */
    public function fromEnum(string $enumClass): static
    {
        if (!enum_exists($enumClass)) {
            throw new \InvalidArgumentException("A classe {$enumClass} não é um Enum válido.");
        }

        // Extrai as opções do Enum automaticamente
        $options = $this->extractEnumOptions($enumClass);
        
        if (!empty($options)) {
            $this->options($options);
        }

        return $this;
    }

    /**
     * Detecta automaticamente o Enum baseado no nome do accessor
     * Convenção: 'status' -> App\Enums\{Model}Status
     *
     * @param string $modelClass Classe do Model para detectar o Enum
     * @return static
     */
    public function autoDetectEnum(string $modelClass): static
    {
        $modelName = class_basename($modelClass);
        $accessorKey = $this->getAccessorKey();
        
        // Converte accessor_key para EnumClass (ex: 'status' -> 'Status')
        $enumSuffix = ucfirst(str_replace('_', '', $accessorKey));
        
        // Tenta encontrar o Enum seguindo convenções comuns
        $possibleEnums = [
            "App\\Enums\\{$modelName}{$enumSuffix}",
            "App\\Enums\\{$enumSuffix}",
            "App\\Enums\\{$modelName}\\{$enumSuffix}",
        ];

        foreach ($possibleEnums as $enumClass) {
            if (enum_exists($enumClass)) {
                return $this->fromEnum($enumClass);
            }
        }

        // Se não encontrou, retorna sem configurar (fallback graceful)
        return $this;
    }

    /**
     * Extrai opções de um Enum com suporte a métodos label() e color()
     *
     * @param string $enumClass
     * @return array
     */
    protected function extractEnumOptions(string $enumClass): array
    {
        try {
            // Primeiro tenta usar o método options() se existir
            if (method_exists($enumClass, 'options')) {
                $options = $enumClass::options();
                
                // Se retornou array com estrutura correta, usa direto
                if (is_array($options) && $this->isValidOptionsArray($options)) {
                    return $this->enrichOptionsWithColors($options, $enumClass);
                }
            }

            // Fallback: constrói manualmente a partir dos cases
            return $this->buildOptionsFromCases($enumClass);
            
        } catch (\Throwable $e) {
            // Em caso de erro, retorna array vazio (fallback graceful)
            return [];
        }
    }

    /**
     * Constrói opções a partir dos cases do Enum
     *
     * @param string $enumClass
     * @return array
     */
    protected function buildOptionsFromCases(string $enumClass): array
    {
        $options = [];
        $reflection = new ReflectionClass($enumClass);
        
        // Verifica se é um BackedEnum
        if (!$reflection->implementsInterface(BackedEnum::class)) {
            return [];
        }

        foreach ($enumClass::cases() as $case) {
            $option = [
                'value' => $case->value,
                'label' => $this->extractLabel($case),
            ];

            // Adiciona cor se o método color() existir
            if (method_exists($case, 'color')) {
                $option['color'] = $case->color();
            }

            $options[] = $option;
        }

        return $options;
    }

    /**
     * Adiciona informações de cor às opções existentes
     *
     * @param array $options
     * @param string $enumClass
     * @return array
     */
    protected function enrichOptionsWithColors(array $options, string $enumClass): array
    {
        // Se as opções já têm cores, retorna como está
        if (!empty($options) && isset($options[0]['color'])) {
            return $options;
        }

        // Adiciona cores se os cases do Enum tiverem método color()
        foreach ($options as &$option) {
            if (isset($option['value'])) {
                $case = $this->findEnumCase($enumClass, $option['value']);
                if ($case && method_exists($case, 'color')) {
                    $option['color'] = $case->color();
                }
            }
        }

        return $options;
    }

    /**
     * Encontra um case do Enum pelo valor
     *
     * @param string $enumClass
     * @param mixed $value
     * @return UnitEnum|null
     */
    protected function findEnumCase(string $enumClass, mixed $value): ?UnitEnum
    {
        try {
            return $enumClass::from($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Extrai o label de um case do Enum
     *
     * @param UnitEnum $case
     * @return string
     */
    protected function extractLabel(UnitEnum $case): string
    {
        // Primeiro tenta método label()
        if (method_exists($case, 'label')) {
            return $case->label();
        }

        // Fallback: usa o nome do case formatado
        return ucfirst(str_replace('_', ' ', strtolower($case->name)));
    }

    /**
     * Verifica se um array de opções tem a estrutura correta
     *
     * @param array $options
     * @return bool
     */
    protected function isValidOptionsArray(array $options): bool
    {
        if (empty($options)) {
            return false;
        }

        $firstOption = $options[0];
        return is_array($firstOption) && 
               isset($firstOption['value']) && 
               isset($firstOption['label']);
    }

    /**
     * Atalho para criar coluna de status com Enum específico
     *
     * @param string $enumClass
     * @param string $label
     * @param string|null $accessorKey
     * @return static
     */
    public static function withEnum(string $enumClass, string $label = 'Status', ?string $accessorKey = null): static
    {
        return static::make($label, $accessorKey)->fromEnum($enumClass);
    }

    /**
     * Atalho para detectar automaticamente baseado no Model
     *
     * @param string $modelClass
     * @param string $label
     * @param string|null $accessorKey
     * @return static
     */
    public static function forModel(string $modelClass, string $label = 'Status', ?string $accessorKey = null): static
    {
        return static::make($label, $accessorKey)->autoDetectEnum($modelClass);
    }
}
