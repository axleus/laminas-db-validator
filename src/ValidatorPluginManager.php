<?php

declare(strict_types=1);

namespace Laminas\Db\Validator;

use Closure;
use Laminas\ServiceManager\AbstractSingleInstancePluginManager;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\ValidatorInterface;
use Laminas\Validator\Translator\TranslatorAwareInterface;
use Laminas\Validator\ValidatorPluginManagerAwareInterface;
use Psr\Container\ContainerInterface;

use function array_replace_recursive;
use function assert;

/**
 * @psalm-import-type ServiceManagerConfiguration from ServiceManager
 * @extends AbstractSingleInstancePluginManager<ValidatorInterface>
 */
final class ValidatorPluginManager extends AbstractSingleInstancePluginManager
{
    private const DEFAULT_CONFIGURATION = [
        'factories' => [
            RecordExists::class                       => InvokableFactory::class,
            NoRecordExists::class                      => InvokableFactory::class,
        ],
        'aliases'   => [
            'dbnorecordexists'       => NoRecordExists::class,
            'dbNoRecordExists'       => NoRecordExists::class,
            'DbNoRecordExists'       => NoRecordExists::class,
            'dbrecordexists'         => RecordExists::class,
            'dbRecordExists'         => RecordExists::class,
            'DbRecordExists'         => RecordExists::class,
        ],
    ];

    /**
     * Whether to share by default; default to false
     */
    protected bool $sharedByDefault = false;

    protected string $instanceOf = ValidatorInterface::class;

    /**
     * @param ContainerInterface $creationContext
     * @param array              $config
     */
    public function __construct(ContainerInterface $creationContext, array $config = [])
    {
        /** @var ServiceManagerConfiguration $config */
        $config = array_replace_recursive(self::DEFAULT_CONFIGURATION, $config);
        parent::__construct($creationContext, $config);

        $this->addInitializer($this->injectTranslator(...));
        $this->addInitializer($this->injectValidatorPluginManager(...));
    }

    /** @internal */
    protected function injectTranslator(ContainerInterface $container, object $validator): void
    {
        if (! $validator instanceof TranslatorAwareInterface) {
            return;
        }

        if ($container->has('MvcTranslator')) {
            $translator = $container->get('MvcTranslator');
            assert($translator instanceof TranslatorInterface);
            $validator->setTranslator($translator);

            return;
        }

        if ($container->has(TranslatorInterface::class)) {
            $validator->setTranslator($container->get(TranslatorInterface::class));
        }
    }

    /** @internal */
    protected function injectValidatorPluginManager(
        ContainerInterface $container,
        object $validator,
    ): void {
        if (! $validator instanceof ValidatorPluginManagerAwareInterface) {
            return;
        }

        $validator->setValidatorPluginManager($this);
    }
}