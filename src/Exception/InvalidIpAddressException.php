<?php declare(strict_types=1);

namespace Darsyn\IP\Exception;

class InvalidIpAddressException extends IpException
{
    /** @var mixed $ip */
    private $ip;

    public function __construct(string $ip, \Exception $previous = null)
    {
        $this->ip = $ip;
        parent::__construct('The IP address supplied is not valid.', null, $previous);
    }

    public function getSuppliedIp(): string
    {
        return $this->ip;
    }
}
