<?php

namespace Laminas\Db\Validator;

use Laminas\Validator\Exception;

/**
 * Confirms a record does not exist in a table.
 */
final class NoRecordExists extends AbstractDbValidator
{
    /**
     * @param mixed $value
     * @return bool
     */
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

        $result = $this->query((string) $value);
        if ($result) {
            $valid = false;
            $this->error(self::ERROR_RECORD_FOUND);
        }

        return $valid;
    }
}