<?php

namespace Darsyn\IP\Tests\Doctrine;

use Darsyn\IP\Doctrine\MultiType;
use Darsyn\IP\Tests\TestCase;
use Darsyn\IP\Version\Multi as IP;
use Doctrine\DBAL\Types\Type;
use PDO;

class MultiTypeTest extends TestCase
{
    /** @var \Doctrine\DBAL\Platforms\AbstractPlatform $platform */
    private $platform;

    /** @var \Darsyn\IP\Doctrine\MultiType $type */
    private $type;

    public static function setUpBeforeClass()
    {
        if (class_exists(Type::class)) {
            Type::addType('ip_multi', MultiType::class);
        }
    }

    private function getPlatformMock()
    {
        // We have to use MySQL as the platform here, because the AbstractPlatform does not support BINARY types.
        return $this
            ->getMockBuilder('Doctrine\DBAL\Platforms\MySqlPlatform')
            ->setMethods(['getBinaryTypeDeclarationSQL'])
            ->getMockForAbstractClass();
    }

    protected function setUp()
    {
        parent::setUp();
        if (!class_exists('Doctrine\DBAL\Types\Type')) {
            $this->markTestSkipped('Skipping test that requires "doctrine/dbal".');
        }

        $this->platform = $this->getPlatformMock();
        $this->platform
            ->expects($this->any())
            ->method('getBinaryTypeDeclarationSQL')
            ->will($this->returnValue('DUMMYBINARY()'));
        $this->type = Type::getType('ip_multi');
    }

    /**
     * @test
     */
    public function testIpConvertsToDatabaseValue()
    {
        $ip = new IP('12.34.56.78');

        $expected = $ip->getBinary();
        $actual = $this->type->convertToDatabaseValue($ip, $this->platform);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     * @expectedException \Doctrine\DBAL\Types\ConversionException
     */
    public function testInvalidIpConversionForDatabaseValue()
    {
        $this->type->convertToDatabaseValue('abcdefg', $this->platform);
    }

    /**
     * @test
     */
    public function testNullConversionForDatabaseValue()
    {
        $this->assertNull($this->type->convertToDatabaseValue(null, $this->platform));
    }

    /**
     * @test
     */
    public function testIpConvertsToPHPValue()
    {
        $ip = new IP('12.34.56.78');
        /** @var IP $dbIp */
        $dbIp = $this->type->convertToPHPValue($ip->getBinary(), $this->platform);
        $this->assertInstanceOf(IP::class, $dbIp);
        $this->assertEquals('12.34.56.78', $dbIp->getDotAddress());
    }

    /**
     * @test
     */
    public function testIpObjectConvertsToPHPValue()
    {
        $ip = new IP('12.34.56.78');
        /** @var IP $dbIp */
        $dbIp = $this->type->convertToPHPValue($ip, $this->platform);
        $this->assertInstanceOf(IP::class, $dbIp);
        $this->assertSame($ip, $dbIp);
    }

    /**
     * @test
     */
    public function testStreamConvertsToPHPValue()
    {
        $ip = new IP('12.34.56.78');
        $stream = fopen('php://memory','r+');
        fwrite($stream, $ip->getBinary());
        rewind($stream);
        /** @var IP $dbIp */
        $dbIp = $this->type->convertToPHPValue($stream, $this->platform);
        $this->assertInstanceOf(IP::class, $dbIp);
        $this->assertEquals('12.34.56.78', $dbIp->getDotAddress());
    }

    /**
     * @test
     * @expectedException \Doctrine\DBAL\Types\ConversionException
     */
    public function testInvalidIpConversionForPHPValue()
    {
        $this->type->convertToPHPValue('abcdefg', $this->platform);
    }

    /**
     * @test
     */
    public function testNullConversionForPHPValue()
    {
        $this->assertNull($this->type->convertToPHPValue(null, $this->platform));
    }

    /**
     * @test
     */
    public function testGetName()
    {
        $this->assertEquals('ip', $this->type->getName());
    }

    /**
     * @test
     */
    public function testGetBinaryTypeDeclarationSQL()
    {
        $this->assertEquals('DUMMYBINARY()', $this->type->getSqlDeclaration(['length' => 16], $this->platform));
    }

    /**
     * @test
     */
    public function testBindingTypeIsAValidPDOTypeConstant()
    {
        // Get all constants of the PDO class.
        $constants = (new \ReflectionClass(PDO::class))->getConstants();
        // Now filter out any constants that don't begin with "PARAM_".
        $paramConstants = array_intersect_key(
            $constants,
            array_flip(array_filter(array_keys($constants), function ($key) {
                return strpos($key, 'PARAM_') === 0;
            }))
        );
        // Check that the return value of the Type's binding value is a valid
        // PDO PARAM constant.
        $this->assertContains($this->type->getBindingType(), $paramConstants);
    }

    /**
     * @test
     */
    public function testRequiresSQLCommentHint()
    {
        $this->assertTrue($this->type->requiresSQLCommentHint($this->platform));
    }
}
