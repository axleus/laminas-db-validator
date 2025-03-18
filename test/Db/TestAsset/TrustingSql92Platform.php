<?php

namespace LaminasTest\Db\Validator\TestAsset;

use Laminas\Db\Adapter\Platform\Sql92;

class TrustingSql92Platform extends Sql92
{
    /**
     * {@inheritDoc}
     */
    public function quoteValue($value)
    {
        return $this->quoteTrustedValue($value);
    }
}
