<?php

declare (strict_types=1);
namespace AcVendor\DI;

use AcVendor\Psr\Container\ContainerExceptionInterface;
/**
 * Exception for the Container.
 */
class DependencyException extends \Exception implements ContainerExceptionInterface
{
}
