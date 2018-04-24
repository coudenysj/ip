<?php

declare(strict_types=1);

namespace Darsyn\IP\Tests\Performance;

use Darsyn\IP\IP;

class IpBench
{
    /**
     * @Revs(100000)
     */
    public function benchCreateIpV4()
    {
        $ip = new IP('192.0.2.1');
        $ip->getNetworkIp(30);

        $ip->getShortAddress();
    }

    /**
     * @Revs(100000)
     */
    public function benchCreateIpV6()
    {
        $ip = new IP('2001:db8:a::');
        $ip->getNetworkIp(64);

        $ip->getShortAddress();
    }
}
