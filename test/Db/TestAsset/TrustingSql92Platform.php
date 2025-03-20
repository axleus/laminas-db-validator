<?php

declare(strict_types=1);

namespace LaminasTest\Db\Validator\TestAsset;

use Laminas\Db\Adapter\Platform\Sql92;
use Override;

final class TrustingSql92Platform extends Sql92
{
    /**
     * {@inheritDoc}
     */
    #[Override]
    public function quoteValue($value): string
    {
        return $this->quoteTrustedValue($value);
    }
}
