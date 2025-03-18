<?php

namespace Laminas\Db\Validator;

use Closure;
use Laminas\Db\Adapter\AdapterInterface;
use Laminas\Db\Adapter\AdapterAwareInterface;
use Laminas\Db\Adapter\AdapterAwareTrait;
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

/**
 * Class for Database record validation
 * @psalm-type OptionsArgument = array{
 * adapter?: AdapterInterface,
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
     *
     * @var null|Select
     */
    protected ?Select $select = null;

    /** @var null|string */
    protected ?string $schema = null;

    /** @var string */
    protected string $table = '';

    /** @var string */
    protected string $field = '';

    /** @var array|Closure|PredicateInterface|Where|string|null */
    protected array|Closure|PredicateInterface|Where|string|null $exclude = null;

    /**
     * Provides basic configuration for use with Laminas\Validator\Db Validators
     * Setting $exclude allows a single record to be excluded from matching.
     * Exclude can either be a String containing a where clause, or an array with `field` and `value` keys
     * to define the where clause added to the sql.
     * A database adapter may optionally be supplied to avoid using the registered default adapter.
     * The following option keys are supported:
     * 'table'   => The database table to validate against
     * 'schema'  => The schema keys
     * 'field'   => The field to check for a match
     * 'exclude' => An optional where clause or field/value pair to exclude from the query
     * 'adapter' => An optional database adapter to use
     *
     * @param OptionsArgument $options = []
     * @throws InvalidArgumentException
     */
    public function __construct(array $options = [])
    {
        foreach ($options as $property => $value) {
            switch ($property) {
                case 'table':
                case 'schema':
                    unset($options[$property]);
                    $method = 'set' . ucfirst($property);
                    $this->$method($value);
                    break;

                case 'field':
                    if (! is_string($value)) {
                        throw new InvalidArgumentException('Field option must be a string or array.');
                    }
                    unset($options[$property]);
                    $this->setField($value);
                    break;

                case 'exclude':
                    if (!is_array($value) &&
                        !$value instanceof Closure &&
                        !$value instanceof PredicateInterface &&
                        !is_string($value)
                    ) {
                        throw new InvalidArgumentException(
                            'Exclude option must be a string, array, Closure or PredicateInterface object.'
                        );
                    }
                    unset($options[$property]);
                    $this->setExclude($value);
                    break;

                case 'adapter':
                    if (! ($value instanceof AdapterInterface)) {
                        throw new InvalidArgumentException('AdapterInterface not passed.');
                    }
                    unset($options[$property]);
                    $this->setAdapter($value);
                    break;

                case 'select':
                    if (! ($value instanceof Select)) {
                        throw new InvalidArgumentException('Select is not a valid Laminas\\Db\\Sql\\Select object.');
                    }
                    unset($options[$property]);
                    $this->setSelect($value);
                    break;
            }
        }

        if ($this->getTable() === '' && $this->getSchema() === null) {
            throw new Exception\InvalidArgumentException('Table or Schema option missing.');
        }

        if ($this->getField() === '') {
            throw new Exception\InvalidArgumentException('Field option missing.');
        }

        parent::__construct($options);
    }

    /**
     * Returns the set adapter
     *
     * @throws RuntimeException When no database adapter is defined.
     * @return null|AdapterInterface
     */
    public function getAdapter(): ?AdapterInterface
    {
        return $this->adapter;
    }

    /**
     * Sets a new database adapter
     *
     * @return self Provides a fluent interface
     */
    public function setAdapter(AdapterInterface $adapter): self
    {
        $this->setDbAdapter($adapter);
        return $this;
    }

    /**
     * Returns the set exclude clause
     *
     * @return array|Closure|PredicateInterface|Where|string|null
     */
    public function getExclude(): array|Closure|PredicateInterface|Where|string|null
    {
        return $this->exclude;
    }

    /**
     * Sets a new exclude clause
     *
     * @param array|Closure|PredicateInterface|Where|string|null $exclude
     * @return $this Provides a fluent interface
     */
    public function setExclude(array|Closure|PredicateInterface|Where|string|null $exclude): self
    {
        $this->exclude = $exclude;
        $this->select  = null;
        return $this;
    }

    /**
     * Returns the set field
     *
     * @return string
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * Sets a new field
     *
     * @param string $field
     * @return $this
     */
    public function setField(string $field): self
    {
        $this->field  = $field;
        return $this;
    }

    /**
     * Returns the set table
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Sets a new table
     *
     * @param string $table
     * @return $this Provides a fluent interface
     */
    public function setTable(string $table): self
    {
        $this->table  = $table;
        return $this;
    }

    /**
     * Returns the set schema
     *
     * @return string|null
     */
    public function getSchema(): ?string
    {
        return $this->schema;
    }

    /**
     * Sets a new schema
     *
     * @param string $schema
     * @return $this Provides a fluent interface
     */
    public function setSchema(string $schema): self
    {
        $this->schema = $schema;
        $this->select = null;
        return $this;
    }

    /**
     * Sets the select object to be used by the validator
     *
     * @return $this Provides a fluent interface
     */
    public function setSelect(Select $select): self
    {
        $this->select = $select;
        return $this;
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
     * @param string $value
     * @return array|null when matches are found.
     */
    protected function query(string $value) : ?iterable
    {
        $sql                  = new Sql($this->getAdapter());
        $statement            = $sql->prepareStatementForSqlObject($this->getSelect());
        $parameters           = $statement->getParameterContainer();

        if ($parameters !== null) {
            $parameters['where1'] = $value;
        }

        $result               = $statement->execute();

        return $result->current();
    }
}