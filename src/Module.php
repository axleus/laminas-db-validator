<?php

declare(strict_types=1);

namespace Laminas\Db\Validator;

use Laminas\ServiceManager\Factory\InvokableFactory;

final class Module
{
    /**
     * Return default laminas-db-validator configuration.
     *
     * @return array[]
     */
    public function getConfig(): array
    {
        return [
            'validators' => [
                'factories' => [
                    RecordExists::class   => InvokableFactory::class,
                    NoRecordExists::class => InvokableFactory::class,
                ],
                'aliases'   => [
                    'dbnorecordexists' => NoRecordExists::class,
                    'dbNoRecordExists' => NoRecordExists::class,
                    'DbNoRecordExists' => NoRecordExists::class,
                    'dbrecordexists'   => RecordExists::class,
                    'dbRecordExists'   => RecordExists::class,
                    'DbRecordExists'   => RecordExists::class,
                ],
            ],
        ];
    }
}
