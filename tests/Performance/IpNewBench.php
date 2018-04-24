<?php

declare(strict_types=1);

namespace Darsyn\IP\Tests\Performance;

use Darsyn\IP\Version\IPv4;
use Darsyn\IP\Version\IPv6;
use Darsyn\IP\Version\Multi;

class IpNewBench
{
    /**
     * @Revs(100000)
     */
    public function benchCreateIpV4()
    {
        $ip = new IPv4('192.0.2.1');
        $ip->getNetworkIp(30);

        $ip->getDotAddress();
    }

    /**
     * @Revs(100000)
     */
    public function benchCreateIpV6()
    {
        $ip = new IPv6('2001:db8:a::');
        $ip->getNetworkIp(64);

        $ip->getCompactedAddress();
    }

    /**
     * @Revs(100000)
     */
    public function benchCreateIpMulti()
    {
        $ip = new Multi('192.0.2.2');
        $ip->getNetworkIp(29);

        $ip->getProtocolAppropriateAddress();
    }
}
