<?php

declare (strict_types=1);
namespace AcVendor\DI\Definition\Resolver;

use AcVendor\DI\Definition\Definition;
use AcVendor\DI\Definition\Exception\InvalidDefinition;
use AcVendor\DI\Definition\FactoryDefinition;
use AcVendor\DI\Invoker\FactoryParameterResolver;
use AcVendor\Invoker\Exception\NotCallableException;
use AcVendor\Invoker\Exception\NotEnoughParametersException;
use AcVendor\Invoker\Invoker;
use AcVendor\Invoker\ParameterResolver\AssociativeArrayResolver;
use AcVendor\Invoker\ParameterResolver\DefaultValueResolver;
use AcVendor\Invoker\ParameterResolver\NumericArrayResolver;
use AcVendor\Invoker\ParameterResolver\ResolverChain;
use AcVendor\Psr\Container\ContainerInterface;
/**
 * Resolves a factory definition to a value.
 *
 * @since 4.0
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class FactoryResolver implements DefinitionResolver
{
    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var Invoker|null
     */
    private $invoker;
    /**
     * @var DefinitionResolver
     */
    private $resolver;
    /**
     * The resolver needs a container. This container will be passed to the factory as a parameter
     * so that the factory can access other entries of the container.
     */
    public function __construct(ContainerInterface $container, DefinitionResolver $resolver)
    {
        $this->container = $container;
        $this->resolver = $resolver;
    }
    /**
     * Resolve a factory definition to a value.
     *
     * This will call the callable of the definition.
     *
     * @param FactoryDefinition $definition
     */
    public function resolve(Definition $definition, array $parameters = [])
    {
        if (!$this->invoker) {
            $parameterResolver = new ResolverChain([new AssociativeArrayResolver(), new FactoryParameterResolver($this->container), new NumericArrayResolver(), new DefaultValueResolver()]);
            $this->invoker = new Invoker($parameterResolver, $this->container);
        }
        $callable = $definition->getCallable();
        try {
            $providedParams = [$this->container, $definition];
            $extraParams = $this->resolveExtraParams($definition->getParameters());
            $providedParams = \array_merge($providedParams, $extraParams, $parameters);
            return $this->invoker->call($callable, $providedParams);
        } catch (NotCallableException $e) {
            // Custom error message to help debugging
            if (\is_string($callable) && \class_exists($callable) && \method_exists($callable, '__invoke')) {
                throw new InvalidDefinition(\sprintf('Entry "%s" cannot be resolved: factory %s. Invokable classes cannot be automatically resolved if autowiring is disabled on the container, you need to enable autowiring or define the entry manually.', $definition->getName(), $e->getMessage()));
            }
            throw new InvalidDefinition(\sprintf('Entry "%s" cannot be resolved: factory %s', $definition->getName(), $e->getMessage()));
        } catch (NotEnoughParametersException $e) {
            throw new InvalidDefinition(\sprintf('Entry "%s" cannot be resolved: %s', $definition->getName(), $e->getMessage()));
        }
    }
    public function isResolvable(Definition $definition, array $parameters = []) : bool
    {
        return \true;
    }
    private function resolveExtraParams(array $params) : array
    {
        $resolved = [];
        foreach ($params as $key => $value) {
            // Nested definitions
            if ($value instanceof Definition) {
                $value = $this->resolver->resolve($value);
            }
            $resolved[$key] = $value;
        }
        return $resolved;
    }
}
