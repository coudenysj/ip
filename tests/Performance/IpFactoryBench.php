<?php

declare(strict_types=1);

namespace Darsyn\IP\Tests\Performance;

use Darsyn\IP\Version\IPv4;
use Darsyn\IP\Version\IPv6;
use Darsyn\IP\Version\Multi;

class IpFactoryBench
{
    /**
     * @Revs(100000)
     */
    public function benchCreateIpV4()
    {
        $ip = IPv4::factory('192.0.2.1');
        $ip->getNetworkIp(30);

        $ip->getDotAddress();
    }

    /**
     * @Revs(100000)
     */
    public function benchCreateIpV6()
    {
        $ip = IPv6::factory('2001:db8:a::');
        $ip->getNetworkIp(64);

        $ip->getCompactedAddress();
    }

    /**
     * @Revs(100000)
     */
    public function benchCreateIpMulti()
    {
        $ip = Multi::factory('192.0.2.2');
        $ip->getNetworkIp(29);

        $ip->getProtocolAppropriateAddress();
    }
}
