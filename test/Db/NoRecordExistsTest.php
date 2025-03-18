<?php

namespace LaminasTest\Db\Validator;

use ArrayObject;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Adapter\Driver\ConnectionInterface;
use Laminas\Db\Adapter\Driver\DriverInterface;
use Laminas\Db\Adapter\Driver\ResultInterface;
use Laminas\Db\Adapter\Driver\StatementInterface;
use Laminas\Db\Adapter\ParameterContainer;
use Laminas\Db\Validator\NoRecordExists;
use Laminas\Validator\Exception\RuntimeException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use TypeError;

/**
 * @group      Laminas_Validator
 */
class NoRecordExistsTest extends TestCase
{
    /**
     * Return a Mock object for a Db result with rows
     *
     * @return Adapter
     */
    protected function getMockHasResult()
    {
        // mock the adapter, driver, and parts
        $mockConnection = $this->createMock(ConnectionInterface::class);

        // Mock has result
        $mockHasResultRow      = new ArrayObject(['one' => 'one']);

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
        $mockHasResultDriver
            ->method('createStatement')
            ->willReturn($mockHasResultStatement);
        $mockHasResultDriver
            ->method('getConnection')
            ->willReturn($mockConnection);

        return $this->getMockBuilder(Adapter::class)
            ->setConstructorArgs([$mockHasResultDriver])
            ->onlyMethods([])
            ->getMock();
    }

    /**
     * Return a Mock object for a Db result without rows
     *
     * @return Adapter
     */
    protected function getMockNoResult()
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
        $mockNoResultDriver->expects($this->any())->method('createStatement')->willReturn($mockNoResultStatement);
        $mockNoResultDriver->expects($this->any())->method('getConnection')->willReturn($mockConnection);

        return $this->getMockBuilder(Adapter::class)
            ->setConstructorArgs([$mockNoResultDriver])
            ->onlyMethods([])
            ->getMock();
    }

    /**
     * Test to ensure constructor options are passed as array
     */
    public function testNoRecordExistsConstructorArray(): void
    {
        $this->expectException(TypeError::class);
        new NoRecordExists('users');
    }

    /**
     * Test basic function of RecordExists (no exclusion)
     *
     * @return void
     */
    public function testBasicFindsRecord()
    {
        $validator = new NoRecordExists([
            'table' => 'users',
            'field' => 'field1',
            'adapter' => $this->getMockHasResult()
        ]);
        $this->assertFalse($validator->isValid('value1'));
    }

    /**
     * Test basic function of RecordExists (no exclusion)
     *
     * @return void
     */
    public function testBasicFindsNoRecord()
    {
        $validator = new NoRecordExists([
            'table' => 'users',
            'field' => 'field1',
            'adapter' => $this->getMockNoResult()
        ]);
        $this->assertTrue($validator->isValid('nosuchvalue'));
    }

    /**
     * Test the exclusion function
     *
     * @return void
     */
    public function testExcludeWithArray()
    {
        $validator = new NoRecordExists([
            'table' => 'users',
            'field' => 'field1',
            'excludes' => [
                'field' => 'id',
                'value' => 1
            ],
            'adapter' => $this->getMockHasResult()
        ]);
        $this->assertFalse($validator->isValid('value3'));
    }

    /**
     * Test the exclusion function
     * with an array
     *
     * @return void
     */
    public function testExcludeWithArrayNoRecord()
    {
        $validator = new NoRecordExists([
            'table' => 'users',
            'field' => 'users',
            'excludes' => [
                'field' => 'id',
                'value' => 1
            ],
            'adapter' => $this->getMockNoResult()
        ]);
        $this->assertTrue($validator->isValid('nosuchvalue'));
    }

    /**
     * Test the exclusion function
     * with a string
     *
     * @return void
     */
    public function testExcludeWithString()
    {
        $validator = new NoRecordExists([
            'table' => 'users',
            'field' => 'users',
            'excludes' => 'id != 1',
            'adapter' => $this->getMockHasResult()
        ]);
        $this->assertFalse($validator->isValid('value3'));
    }

    /**
     * Test the exclusion function
     * with a string
     *
     * @return void
     */
    public function testExcludeWithStringNoRecord()
    {
        $validator = new NoRecordExists([
            'table' => 'users',
            'field' => 'users',
            'excludes' => 'id != 1',
            'adapter' => $this->getMockNoResult()
        ]);
        $this->assertTrue($validator->isValid('nosuchvalue'));
    }

    /**
     * Test that the class throws an exception if no adapter is provided
     * and no default is set.
     *
     * @return void
     */
    public function testThrowsExceptionWithNoAdapter()
    {
        $validator = new NoRecordExists([
            'table' => 'users',
            'field' => 'users',
            'excludes' => 'id != 1'
        ]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No database adapter present');
        $validator->isValid('nosuchvalue');
    }

    /**
     * Test that schemas are supported and run without error
     *
     * @return void
     */
    public function testWithSchema()
    {
        $validator = new NoRecordExists([
            'table'  => 'users',
            'schema' => 'my',
            'field' => 'users',
            'adapter' => $this->getMockHasResult()
        ]);
        $this->assertFalse($validator->isValid('value1'));
    }

    /**
     * Test that schemas are supported and run without error
     *
     * @return void
     */
    public function testWithSchemaNoResult()
    {
        $validator = new NoRecordExists([
            'table'  => 'users',
            'schema' => 'my',
            'field' => 'users',
            'adapter' => $this->getMockNoResult()
        ]);
        $this->assertTrue($validator->isValid('value1'));
    }

    public function testEqualsMessageTemplates(): void
    {
        $validator        = new NoRecordExists([
            'table' => 'users',
            'field' => 'field1'
        ]);

        $reflectedClass = new ReflectionClass($validator);
        $reflectionProperty = $reflectedClass->getProperty('messageTemplates');
        $reflectionProperty->setAccessible(true);

        $messageTemplates = [
            'noRecordFound' => 'No record matching the input was found',
            'recordFound'   => 'A record matching the input was found',
        ];
        $this->assertSame($messageTemplates, $reflectionProperty->getValue($validator));
    }
}
