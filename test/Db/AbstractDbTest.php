<?php

declare(strict_types=1);

namespace LaminasTest\Db\Validator;

use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Driver\StatementInterface;
use Laminas\Db\Adapter\ParameterContainer;
use Laminas\Db\Validator\AbstractDbValidator;
use Laminas\Validator\Exception\InvalidArgumentException;
use LaminasTest\Db\Validator\TestAsset\ConcreteDbValidator;
use Override;
use PHPUnit\Framework\TestCase;

/**
 * @group      Laminas_Validator
 */
final class AbstractDbTest extends TestCase
{
    protected Adapter $adapter;
    protected AbstractDbValidator $validator;

    #[Override]
    protected function setUp(): void
    {
        $mockConnection = $this->createMock(ConnectionInterface::class);

        $mockStatement = $this->createMock(StatementInterface::class);
        $mockStatement
            ->method('execute')
            ->willReturn([]);

        $mockStatement
            ->method('getParameterContainer')
            ->willReturn(new ParameterContainer());

        $mockDriver = $this->createMock(DriverInterface::class);
        $mockDriver
            ->method('createStatement')
            ->willReturn($mockStatement);
        $mockDriver
            ->method('getConnection')
            ->willReturn($mockConnection);

        $this->adapter = $this->getMockBuilder(Adapter::class)
            ->setConstructorArgs([$mockDriver])
            ->onlyMethods([])
            ->getMock();

        $this->validator = new ConcreteDbValidator([
            'adapter' => $this->adapter,
            'table'   => 'table',
            'field'   => 'field',
            'schema'  => 'schema',
        ]);
    }

    public function testConstructorWithNoTableAndSchemaKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Table or Schema option missing.');
        $this->validator = new ConcreteDbValidator([
            'adapter' => $this->adapter,
            'field'   => 'field',
        ]);
    }

    public function testConstructorWithNoFieldKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Field option missing.');
        new ConcreteDbValidator([
            'adapter' => $this->adapter,
            'schema'  => 'schema',
            'table'   => 'table',
        ]);
    }

    public function testGetSchema(): void
    {
        $schema          = 'test_db';
        $this->validator = new ConcreteDbValidator([
            'adapter' => $this->adapter,
            'field'   => 'field',
            'schema'  => $schema,
        ]);

        $this->assertEquals($schema, $this->validator->getSchema());
    }

    public function testGetTable(): void
    {
        $table           = 'test_table';
        $this->validator = new ConcreteDbValidator([
            'adapter' => $this->adapter,
            'field'   => 'field',
            'table'   => $table,
        ]);

        $this->assertEquals($table, $this->validator->getTable());
    }

    public function testGetField(): void
    {
        $field           = 'test_field';
        $this->validator = new ConcreteDbValidator([
            'adapter' => $this->adapter,
            'table'   => 'test_table',
            'field'   => $field,
        ]);

        $this->assertEquals($field, $this->validator->getField());
    }

    public function testGetExclude(): void
    {
        $exclude         = 'foo = "bar"';
        $this->validator = new ConcreteDbValidator([
            'adapter' => $this->adapter,
            'table'   => 'test_table',
            'field'   => 'field',
            'exclude' => $exclude,
        ]);

        $this->assertEquals($exclude, $this->validator->getExclude());

        $exclude         = ['field' => 'foo', 'value' => 'bar'];
        $this->validator = new ConcreteDbValidator([
            'adapter' => $this->adapter,
            'table'   => 'test_table',
            'field'   => 'field',
            'exclude' => $exclude,
        ]);

        $this->assertEquals($exclude, $this->validator->getExclude());
    }
}
