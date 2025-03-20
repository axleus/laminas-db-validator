<?php

declare(strict_types=1);

namespace LaminasTest\Db\Validator;

use ArrayObject;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\Adapter\Driver\StatementInterface;
use Laminas\Db\Adapter\ParameterContainer;
use Laminas\Db\Adapter\Platform\PlatformInterface;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\TableIdentifier;
use Laminas\Db\Validator\RecordExists;
use Laminas\Validator\Exception\InvalidArgumentException;
use LaminasTest\Db\Validator\TestAsset\TrustingSql92Platform;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use TypeError;

/**
 * @group      Laminas_Validator
 */
final class RecordExistsTest extends TestCase
{
    protected function getMockAdapter(): Adapter
    {
        $mockStatement = $this->createMock(StatementInterface::class);
        $mockStatement->expects($this->any())->method('execute')->willReturn([]);

        $mockPlatform = $this->createMock(PlatformInterface::class);
        $mockPlatform->expects($this->any())->method('getName')->willReturn('platform');

        $mockDriver = $this->createMock(DriverInterface::class);
        $mockDriver->expects($this->any())->method('createStatement')->willReturn($mockStatement);

        return $this->getMockBuilder(Adapter::class)
            ->setConstructorArgs([$mockDriver])
            ->onlyMethods([])
            ->getMock();
    }

    /**
     * Return a Mock object for a Db result with rows
     *
     * @throws Exception
     * @throws Exception
     * @throws Exception
     * @throws Exception
     * @throws Exception
     * @throws Exception
     */
    protected function getMockHasResult(): Adapter
    {
        // mock the adapter, driver, and parts
        $mockConnection = $this->createMock(ConnectionInterface::class);

        // Mock has result
        $mockHasResultRow = new ArrayObject(['one' => 'one']);

        $mockHasResult = $this->createMock(ResultInterface::class);
        $mockHasResult
            ->method('current')
            ->willReturn($mockHasResultRow);

        $mockHasResultStatement = $this->createMock(StatementInterface::class);
        $mockHasResultStatement
            ->method('execute')
            ->willReturn($mockHasResult);

        $mockHasResultStatement
            ->method('getParameterContainer')
            ->willReturn(new ParameterContainer());

        $mockHasResultDriver = $this->createMock(DriverInterface::class);
        $mockHasResultDriver->expects($this->any())->method('createStatement')->willReturn($mockHasResultStatement);
        $mockHasResultDriver->expects($this->any())->method('getConnection')->willReturn($mockConnection);

        return $this->getMockBuilder(Adapter::class)
            ->setConstructorArgs([$mockHasResultDriver])
            ->onlyMethods([])
            ->getMock();
    }

    /**
     * Return a Mock object for a Db result without rows
     *
     * @throws Exception
     * @throws Exception
     * @throws Exception
     * @throws Exception
     * @throws Exception
     * @throws Exception
     */
    protected function getMockNoResult(): Adapter
    {
        // mock the adapter, driver, and parts
        $mockConnection = $this->createMock(ConnectionInterface::class);

        $mockNoResult = $this->createMock(ResultInterface::class);
        $mockNoResult
            ->method('current')
            ->willReturn(null);

        $mockNoResultStatement = $this->createMock(StatementInterface::class);
        $mockNoResultStatement
            ->method('execute')
            ->willReturn($mockNoResult);

        $mockNoResultStatement
            ->method('getParameterContainer')
            ->willReturn(new ParameterContainer());

        $mockNoResultDriver = $this->createMock(DriverInterface::class);
        $mockNoResultDriver
            ->method('createStatement')
            ->willReturn($mockNoResultStatement);
        $mockNoResultDriver
            ->method('getConnection')
            ->willReturn($mockConnection);

        return $this->getMockBuilder(Adapter::class)
            ->setConstructorArgs([$mockNoResultDriver])
            ->onlyMethods([])
            ->getMock();
    }

    /**
     * Test to ensure constructor options are passed as array
     *
     * @psalm-suppress InvalidArgument
     */
    public function testRecordExistsConstructorArray(): void
    {
        $this->expectException(TypeError::class);
        new RecordExists('users');
    }

    /**
     * Test basic function of RecordExists (no exclusion)
     *
     * @throws Exception
     * @throws Exception
     * @return void
     */
    public function testBasicFindsRecord()
    {
        $validator = new RecordExists([
            'table'   => 'users',
            'field'   => 'field1',
            'adapter' => $this->getMockHasResult(),
        ]);
        $this->assertTrue($validator->isValid('value1'));
    }

    /**
     * Test basic function of RecordExists (no exclusion)
     *
     * @throws Exception
     * @throws Exception
     * @return void
     */
    public function testBasicFindsNoRecord()
    {
        $validator = new RecordExists([
            'table'   => 'users',
            'field'   => 'field1',
            'adapter' => $this->getMockNoResult(),
        ]);
        $this->assertFalse($validator->isValid('nosuchvalue'));
    }

    /**
     * Test the exclusion function
     *
     * @throws Exception
     * @throws Exception
     * @return void
     */
    public function testExcludeWithArray()
    {
        $validator = new RecordExists([
            'table'   => 'users',
            'field'   => 'field1',
            'exclude' => [
                'field' => 'id',
                'value' => 1,
            ],
            'adapter' => $this->getMockHasResult(),
        ]);
        $this->assertTrue($validator->isValid('value3'));
    }

    /**
     * Test the exclusion function
     * with an array
     *
     * @throws Exception
     * @throws Exception
     * @return void
     */
    public function testExcludeWithArrayNoRecord()
    {
        $validator = new RecordExists([
            'table'   => 'users',
            'field'   => 'field1',
            'exclude' => [
                'field' => 'id',
                'value' => 1,
            ],
            'adapter' => $this->getMockNoResult(),
        ]);
        $this->assertFalse($validator->isValid('nosuchvalue'));
    }

    /**
     * Test the exclusion function
     * with a string
     *
     * @throws Exception
     * @throws Exception
     * @return void
     */
    public function testExcludeWithString()
    {
        $validator = new RecordExists([
            'table'   => 'users',
            'field'   => 'field1',
            'exclude' => 'id != 1',
            'adapter' => $this->getMockHasResult(),
        ]);
        $this->assertTrue($validator->isValid('value3'));
    }

    /**
     * Test the exclusion function
     * with a string
     *
     * @throws Exception
     * @throws Exception
     * @return void
     */
    public function testExcludeWithStringNoRecord()
    {
        $validator = new RecordExists([
            'table'   => 'users',
            'field'   => 'field1',
            'exclude' => 'id != 1',
            'adapter' => $this->getMockNoResult(),
        ]);
        $this->assertFalse($validator->isValid('nosuchvalue'));
    }

    /**
     * @group Laminas-8863
     * @throws Exception
     */
    public function testExcludeConstructor(): void
    {
        $validator = new RecordExists([
            'table'   => 'users',
            'field'   => 'field1',
            'exclude' => 'id != 1',
            'adapter' => $this->getMockHasResult(),
        ]);
        $this->assertTrue($validator->isValid('value3'));
    }

    /**
     * Test that the class throws an exception if no adapter is provided
     * and no default is set.
     *
     * @return void
     */
    public function testThrowsExceptionWithNoAdapter()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Adapter option missing.');
        /** @psalm-suppress InvalidArgument */
        $validator = new RecordExists([
            'table'   => 'users',
            'field'   => 'field1',
            'exclude' => 'id != 1',
        ]);
        $validator->isValid('nosuchvalue');
    }

    /**
     * Test that schemas are supported and run without error
     *
     * @throws Exception
     * @throws Exception
     * @return void
     */
    public function testWithSchema()
    {
        $validator = new RecordExists([
            'table'   => 'users',
            'schema'  => 'my',
            'field'   => 'field1',
            'adapter' => $this->getMockHasResult(),
        ]);
        $this->assertTrue($validator->isValid('value1'));
    }

    /**
     * Test that schemas are supported and run without error
     *
     * @throws Exception
     */
    public function testWithSchemaNoResult(): void
    {
        $validator = new RecordExists([
            'table'   => 'users',
            'schema'  => 'my',
            'field'   => 'field1',
            'adapter' => $this->getMockNoResult(),
        ]);
        $this->assertFalse($validator->isValid('value1'));
    }

    /**
     * Test that the supplied table and schema are successfully passed to the select
     * statement
     *
     * @throws Exception
     */
    public function testSelectAcknowledgesTableAndSchema(): void
    {
        $validator = new RecordExists([
            'table'   => 'users',
            'schema'  => 'my',
            'field'   => 'field1',
            'adapter' => $this->getMockHasResult(),
        ]);
        $table     = $validator->getSelect()->getRawState('table');
        $this->assertInstanceOf(TableIdentifier::class, $table);
        $this->assertEquals(['users', 'my'], $table->getTableAndSchema());
    }

    public function testEqualsMessageTemplates(): void
    {
        $validator = new RecordExists([
            'adapter' => $this->getMockAdapter(),
            'table'   => 'users',
            'field'   => 'field1',
        ]);

        $reflectedClass     = new ReflectionClass($validator);
        $reflectionProperty = $reflectedClass->getProperty('messageTemplates');
        /** @psalm-suppress UnusedMethodCall */
        $reflectionProperty->setAccessible(true);

        $messageTemplates = [
            'noRecordFound' => 'No record matching the input was found',
            'recordFound'   => 'A record matching the input was found',
        ];

        $this->assertSame($messageTemplates, $reflectionProperty->getValue($validator));
    }

    /**
     * @testdox Laminas\Db\Validator\RecordExists::getSelect
     * @throws Exception
     * @throws Exception
     */
    public function testGetSelect(): void
    {
        $validator = new RecordExists([
            'table'   => 'users',
            'schema'  => 'my',
            'field'   => 'field1',
            'exclude' => [
                'field' => 'foo',
                'value' => 'bar',
            ],
            'adapter' => $this->getMockHasResult(),
        ]);
        $select    = $validator->getSelect();
        $this->assertInstanceOf(Select::class, $select);
        $this->assertEquals(
            'SELECT "my"."users"."field1" AS "field1" FROM "my"."users" WHERE "field1" = \'\' AND "foo" != \'bar\'',
            $select->getSqlString(new TrustingSql92Platform())
        );

        $sql        = new Sql($this->getMockHasResult());
        $statement  = $sql->prepareStatementForSqlObject($select);
        $parameters = $statement->getParameterContainer();
        $this->assertNotNUll($parameters);

        $this->assertEquals('', $parameters['where1']);
        $this->assertEquals('bar', $parameters['where2']);
    }

    /**
     * @cover Laminas\Db\Validator\RecordExists::getSelect
     * @group Laminas-4521
     * @throws Exception
     */
    public function testGetSelectWithSameValidatorTwice(): void
    {
        $validator = new RecordExists(
            [
                'table'   => 'users',
                'schema'  => 'my',
                'field'   => 'field1',
                'exclude' => [
                    'field' => 'foo',
                    'value' => 'bar',
                ],
                'adapter' => $this->getMockHasResult(),
            ]
        );
        $select    = $validator->getSelect();
        $this->assertInstanceOf(Select::class, $select);
        $this->assertEquals(
            'SELECT "my"."users"."field1" AS "field1" FROM "my"."users" WHERE "field1" = \'\' AND "foo" != \'bar\'',
            $select->getSqlString(new TrustingSql92Platform())
        );
    }
}
