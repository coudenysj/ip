<?php

namespace Darsyn\IP\Tests\Doctrine;

use Darsyn\IP\IP;
use Darsyn\IP\Tests\TestCase;

class IPTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();
        if (\defined('HHVM_VERSION')) {
            $this->markTestSkipped('Skipping deprecated error test on HHVM.');
        }
    }

    /**
     * @test
     * @expectedException \PHPUnit\Framework\Exception
     */
    public function testDeprecatedIpEmitsUserError()
    {
        new IP('12.34.56.78');
    }
}
