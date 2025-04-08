<?php
/*
 * Created by Claudio Campos.
 * User: callcocam@gmail.com, contato@sigasmart.com.br
 * https://www.sigasmart.com.br
 */

namespace Callcocam\LaraGatekeeper\Core\Support\Concerns;

use Closure;
use Illuminate\Contracts\Container\BindingResolutionException;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;

trait EvaluatesClosures
{
    protected string $evaluationIdentifier;

    /**
     * Avalia o valor ou executa a Closure com dependências injetadas.
     *
     * @template T
     *
     * @param  T|callable(): T  $value
     * @param  array<string, mixed>  $namedInjections
     * @param  array<string, mixed>  $typedInjections
     * @return T
     */
    public function evaluate(mixed $value, array $namedInjections = [], array $typedInjections = []): mixed
    {
        if (! $value instanceof Closure) {
            return $value;
        }
 
        $dependencies = [];

        foreach ((new ReflectionFunction($value))->getParameters() as $parameter) { 
            $dependencies[] = $this->resolveClosureDependencyForEvaluation(
                $parameter,
                $namedInjections,
                $typedInjections
            );
        }

        return $value(...$dependencies);
    }

    /**
     * Resolve as dependências necessárias para a execução de uma Closure.
     *
     * @param  ReflectionParameter  $parameter
     * @param  array<string, mixed>  $namedInjections
     * @param  array<string, mixed>  $typedInjections
     * @return mixed
     *
     * @throws BindingResolutionException
     */
    protected function resolveClosureDependencyForEvaluation(ReflectionParameter $parameter, array $namedInjections, array $typedInjections): mixed
    {
        $parameterName = $parameter->getName();
        

        if (array_key_exists($parameterName, $namedInjections)) {
            return value($namedInjections[$parameterName]);
        }

        $typedParameterClassName = $this->getTypedReflectionParameterClassName($parameter);

        if (filled($typedParameterClassName) && array_key_exists($typedParameterClassName, $typedInjections)) {
            return value($typedInjections[$typedParameterClassName]);
        }

        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        if ($parameter->isOptional()) {
            return null;
        }

        if ($typedParameterClassName) {
            return app()->make($typedParameterClassName);
        }

        $staticClass = static::class;

        throw new BindingResolutionException("Unresolvable dependency [\${$parameterName}] in class [{$staticClass}]");
    }

    /**
     * Retorna o nome da classe refletida pelo parâmetro, se disponível.
     *
     * @param  ReflectionParameter  $parameter
     * @return string|null
     */
    protected function getTypedReflectionParameterClassName(ReflectionParameter $parameter): ?string
    {
        $type = $parameter->getType();

        if (! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        $name = $type->getName();
        $class = $parameter->getDeclaringClass();

        if (!$class) {
            return $name;
        }

        return match ($name) {
            'self' => $class->getName(),
            'parent' => $class->getParentClass()?->getName(),
            default => $name,
        };
    }
}
