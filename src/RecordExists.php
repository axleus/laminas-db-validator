<?php

declare(strict_types=1);

namespace Laminas\Db\Validator;

use Laminas\Validator\Exception;
use Override;

/**
 * Confirms a record exists in a table.
 */
final class RecordExists extends AbstractDbValidator
{
    #[Override]
    public function isValid(mixed $value): bool
    {
        /*
         * Check for an adapter being defined. If not, throw an exception.
         */
        if ($this->getAdapter() === null) {
            throw new Exception\RuntimeException('No database adapter present');
        }

        $valid = true;
        $this->setValue($value);

        $result = $this->query($value);
        if (! $result) {
            $valid = false;
            $this->error(self::ERROR_NO_RECORD_FOUND);
        }

        return $valid;
    }
}
