<?php

declare(strict_types=1);

namespace Laminas\Db\Validator;

use Laminas\ServiceManager\ServiceManager;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

use Psr\Container\NotFoundExceptionInterface;

use function assert;
use function is_array;

/** @psalm-import-type ServiceManagerConfiguration from ServiceManager */
final class ValidatorPluginManagerFactory
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container): ValidatorPluginManager
    {
        $config = $container->has('config')
            ? $container->get('config')
            : [];
        assert(is_array($config));

        /** @psalm-var ServiceManagerConfiguration $validators */
        $validators = isset($config['validators']) && is_array($config['validators'])
            ? $config['validators']
            : [];

        return new ValidatorPluginManager($container, $validators);
    }
}