<?php

namespace LaminasTest\Db\Validator\TestAsset;

use Laminas\Db\Adapter\Platform\Sql92;

final class TrustingSql92Platform extends Sql92
{
    /**
     * {@inheritDoc}
     */
    #[\Override]
    public function quoteValue($value): string
    {
        return $this->quoteTrustedValue($value);
    }
}
