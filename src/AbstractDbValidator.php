<?php

declare(strict_types=1);

namespace Laminas\Db\Validator;

use Closure;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\AdapterAwareInterface;
use Laminas\Db\Adapter\AdapterAwareTrait;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\Driver\StatementInterface;
use Laminas\Db\Sql\Predicate\PredicateInterface;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\TableIdentifier;
use Laminas\Db\Sql\Where;
use Laminas\Translator\TranslatorInterface;
use Laminas\Validator\AbstractValidator;
use Laminas\Validator\Exception;
use Laminas\Validator\Exception\InvalidArgumentException;
use Laminas\Validator\Exception\RuntimeException;

use function is_array;
use function is_scalar;

/**
 * Class for Database record validation
 *
 * @psalm-type OptionsArgument = array{
 * adapter: Adapter,
 * select?: string|array|Select,
 * table?: string,
 * schema?: string,
 * field?: string,
 * exclude?: array|Closure|PredicateInterface|Where|string,
 * messages?: array<string, string>,
 * translator?: TranslatorInterface|null,
 * translatorTextDomain?: string|null,
 * translatorEnabled?: bool,
 * valueObscured?: bool,
 * }
 */
abstract class AbstractDbValidator extends AbstractValidator implements AdapterAwareInterface
{
    use AdapterAwareTrait;

    /**
     * Error constants
     */
    public const ERROR_NO_RECORD_FOUND = 'noRecordFound';
    public const ERROR_RECORD_FOUND    = 'recordFound';

    /** @var array<string, string> Message templates */
    protected array $messageTemplates = [
        self::ERROR_NO_RECORD_FOUND => 'No record matching the input was found',
        self::ERROR_RECORD_FOUND    => 'A record matching the input was found',
    ];

    /**
     * Select object to use. can be set, or will be auto-generated
     */
    protected ?Select $select = null;

    protected ?string $schema = null;

    protected string $table;

    protected string $field;

    protected array|Closure|PredicateInterface|Where|string|null $exclude;

    /**
     * Provides basic configuration for use with Laminas\Validator\Db Validators
     * Setting $exclude allows a single record to be excluded from matching.
     *
     * The following option keys are supported:
     * 'table'   => The database table to validate against
     * 'schema'  => The schema keys
     * 'field'   => The field to check for a match
     * 'exclude' => An optional where clause or field/value pair to exclude from the query
     * 'select' => An optional Select instance to use
     * 'adapter' => An optional database adapter to use
     *
     * @param OptionsArgument $options = []
     * @throws InvalidArgumentException
     */
    public function __construct(array $options)
    {
        if (! isset($options['adapter'])) {
            throw new Exception\InvalidArgumentException('Adapter option missing.');
        }

        $this->adapter = $options['adapter'];
        unset($options['adapter']);

        $this->table = $options['table'] ?? '';
        unset($options['table']);

        $this->schema = $options['schema'] ?? null;
        unset($options['schema']);

        $this->field = $options['field'] ?? '';
        unset($options['field']);

        $this->exclude = $options['exclude'] ?? null;
        unset($options['exclude']);

        if (isset($options['select']) && $options['select'] instanceof Select) {
            $this->select = $options['select'];
            unset($options['select']);
        }

        if ($this->table === '' && $this->schema === null) {
            throw new Exception\InvalidArgumentException('Table or Schema option missing.');
        }

        if ($this->field === '') {
            throw new Exception\InvalidArgumentException('Field option missing.');
        }

        parent::__construct($options);
    }

    /**
     * Returns the set adapter
     *
     * @throws RuntimeException When no database adapter is defined.
     */
    public function getAdapter(): ?AdapterInterface
    {
        return $this->adapter;
    }

    /**
     * Returns the set exclude clause
     */
    public function getExclude(): array|Closure|PredicateInterface|Where|string|null
    {
        return $this->exclude;
    }

    /**
     * Returns the set field
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * Returns the set table
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Returns the set schema
     */
    public function getSchema(): ?string
    {
        return $this->schema;
    }

    /**
     * Gets the select object to be used by the validator.
     * If no select object was supplied to the constructor,
     * then it will auto-generate one from the given table,
     * schema, field, and adapter options.
     *
     * @return Select The Select object which will be used if present
     */
    public function getSelect(): Select
    {
        if ($this->select instanceof Select) {
            return $this->select;
        }

        // Build select object
        $select          = new Select();
        $tableIdentifier = new TableIdentifier($this->table, $this->schema);
        $select->from($tableIdentifier)->columns([$this->field]);
        $select->where->equalTo($this->field, '');

        $exclude = $this->getExclude();
        if ($exclude !== null) {
            if (is_array($exclude)) {
                $select->where->notEqualTo(
                    (string) $exclude['field'],
                    (string) $exclude['value']
                );
            } else {
                $select->where($exclude);
            }
        }

        return $select;
    }

    /**
     * Run query and returns matches, or null if no matches are found.
     *
     * @return mixed when matches are found.
     */
    protected function query(mixed $value): mixed
    {
        $sql       = new Sql($this->adapter);
        $statement = $sql->prepareStatementForSqlObject($this->getSelect());
        if (! $statement instanceof StatementInterface) {
            throw new Exception\RuntimeException('No valid statement present');
        }

        $parameters = $statement->getParameterContainer();
        if ($parameters !== null) {
            if (! is_scalar($value) && $value !== null) {
                throw new Exception\InvalidArgumentException('Value must be string, integer or null');
            }
            $parameters['where1'] = $value;
        }

        $result = $statement->execute();

        return $result->current();
    }
}
