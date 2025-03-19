<?php

declare(strict_types=1);

namespace LaminasTest\Db\Validator;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Select;
use Laminas\Db\Validator\AbstractDbValidator;
use Laminas\Validator\Exception\InvalidArgumentException;
use LaminasTest\Db\Validator\TestAsset\ConcreteDbValidator;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

use function property_exists;

/**
 * @group      Laminas_Validator
 */
final class AbstractDbTest extends TestCase
{
    protected AbstractDbValidator $validator;

    #[\Override]
    protected function setUp(): void
    {
        $this->validator = new ConcreteDbValidator([
            'table'  => 'table',
            'field'  => 'field',
            'schema' => 'schema',
        ]);
    }

    public function testConstructorWithNoTableAndSchemaKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Table or Schema option missing.');
        $this->validator = new ConcreteDbValidator([
            'field' => 'field',
        ]);
    }

    public function testConstructorWithNoFieldKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field option missing.');
        new ConcreteDbValidator([
            'schema' => 'schema',
            'table'  => 'table',
        ]);
    }

    public function testSetSelect(): void
    {
        $select = new Select();
        $this->validator->setSelect($select);

        $this->assertSame($select, $this->validator->getSelect());
    }

    public function testGetSchema(): void
    {
        $schema = 'test_db';
        $this->validator->setSchema($schema);

        $this->assertEquals($schema, $this->validator->getSchema());
    }

    public function testGetTable(): void
    {
        $table = 'test_table';
        $this->validator->setTable($table);

        $this->assertEquals($table, $this->validator->getTable());
    }

    public function testGetField(): void
    {
        $field = 'test_field';
        $this->validator->setField($field);

        $this->assertEquals($field, $this->validator->getField());
    }

    public function testGetExclude(): void
    {
        $field = 'test_field';
        $this->validator->setField($field);

        $this->assertEquals($field, $this->validator->getField());
    }

    /**
     * @group #46
     * @throws Exception
     */
    public function testSetAdapterIsEquivalentToSetDbAdapter(): void
    {
        $adapterFirst = $this->createStub(Adapter::class);

        $this->validator->setAdapter($adapterFirst);
        $this->assertTrue(property_exists($this->validator, 'adapter'));
        $this->assertEquals($adapterFirst, $this->validator->getAdapter());
    }
}
