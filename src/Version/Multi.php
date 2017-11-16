<?php

namespace Darsyn\IP\Version;

use Darsyn\IP\Exception;
use Darsyn\IP\IpInterface;
use Darsyn\IP\Strategy\EmbeddingStrategyInterface;
use Darsyn\IP\Strategy\Mapped as MappedEmbeddingStrategy;

/**
 * Multi-version IP Address
 *
 * IP is an immutable value object that provides several notations of the same
 * IP value, including some helper functions for broadcast and network
 * addresses, and whether its within the range of another IP address according
 * to a CIDR (subnet mask), etc.
 * Although it deals with both IPv4 and IPv6 notations, it makes no distinction
 * between the two protocol formats as it converts both of them to a 16-byte
 * binary sequence for easy mathematical operations and consistency (for
 * example, storing both IPv4 and IPv6 addresses' binary sequences in a
 * fixed-length database column). in the same column in a database).
 *
 * @author    Zan Baldwin <hello@zanbaldwin.com>
 * @link      https://github.com/darsyn/ip
 * @copyright 2015 Zan Baldwin
 * @license   MIT/X11 <http://j.mp/mit-license>
 */
class Multi extends IPv6 implements MultiVersionInterface
{
    /** @var \Darsyn\IP\Strategy\EmbeddingStrategyInterface $defaultEmbeddingStrategy */
    private static $defaultEmbeddingStrategy;

    /** @var \Darsyn\IP\Strategy\EmbeddingStrategyInterface $embeddingStrategy */
    private $embeddingStrategy;

    /** @var bool $embedded */
    private $embedded;

    /**
     * {@inheritDoc}
     */
    public static function setDefaultEmbeddingStrategy(EmbeddingStrategyInterface $strategy): void
    {
        self::$defaultEmbeddingStrategy = $strategy;
    }

    /**
     * Get the default embedding strategy set. Default to the IPv4-mapped IPv6
     * embedding strategy if the user has not set one globally.
     */
    private function getDefaultEmbeddingStrategy(): EmbeddingStrategyInterface
    {
        return self::$defaultEmbeddingStrategy ?: new MappedEmbeddingStrategy;
    }

    /**
     * Constructor
     *
     * @throws Exception\InvalidIpAddressException
     */
    public function __construct(string $ip, EmbeddingStrategyInterface $strategy = null)
    {
        $this->embeddingStrategy = $strategy ?: static::getDefaultEmbeddingStrategy();

        try {
            // Convert from protocol notation to binary sequence.
            $binary = self::getProtocolFormatter()->pton($ip);

            // If the IP address is a binary sequence of 4 bytes, then pack it into
            // a 16 byte IPv6 binary sequence according to the embedding strategy.
            if ($this->getBinaryLength($binary) === 4) {
                $binary = $this->embeddingStrategy->pack($binary);
            }
        } catch (Exception\IpException $e) {
            throw new Exception\InvalidIpAddressException($ip, $e);
        }
        parent::__construct($binary);
    }

    /**
     * {@inheritDoc}
     */
    public function getProtocolAppropriateAddress(): string
    {
        // If binary string contains an embedded IPv4 address, then extract it.
        $ip = $this->isEmbedded()
            ? $this->getShortBinary()
            : $this->getBinary();
        // Render the IP address in the correct notation according to its
        // protocol (based on how long the binary string is).
        return self::getProtocolFormatter()->ntop($ip);
    }

    /**
     * {@inheritDoc}
     */
    public function getDotAddress(): string
    {
        if ($this->isEmbedded()) {
            try {
                return self::getProtocolFormatter()->ntop($this->getShortBinary());
            } catch (Exception\Formatter\FormatException $e) {
                throw new Exception\IpException('An unknown error occured internally.', 0, $e);
            }
        }
        throw new Exception\WrongVersionException(4, 6, $this->getBinary());
    }

    /**
     * {@inheritDoc}
     */
    public function getVersion(): int
    {
        return $this->isEmbedded() ? 4 : 6;
    }

    /**
     * {@inheritDoc}
     */
    public function getNetworkIp(int $cidr): IpInterface
    {
        try {
            if ($this->isCidrVersion4Appropriate($cidr) && $this->isEmbedded()) {
                return new static(
                    (new IPv4($this->getShortBinary()))
                        ->getNetworkIp($cidr)
                        ->getBinary(),
                    clone $this->embeddingStrategy
                );
            }
        } catch (Exception\Strategy\ExtractionException $e) {
        } catch (Exception\InvalidIpAddressException $e) {
        }
        return parent::getNetworkIp($cidr);
    }

    /**
     * {@inheritDoc}
     */
    public function getBroadcastIp(int $cidr): IpInterface
    {
        try {
            if ($this->isCidrVersion4Appropriate($cidr) && $this->isEmbedded()) {
                return new static(
                    (new IPv4($this->getShortBinary()))
                        ->getBroadcastIp($cidr)
                        ->getBinary(),
                    clone $this->embeddingStrategy
                );
            }
        } catch (Exception\Strategy\ExtractionException $e) {
        } catch (Exception\InvalidIpAddressException $e) {
        }
        return parent::getBroadcastIp($cidr);
    }

    /**
     * Is IP Address In Range?
     *
     * Returns a boolean value depending on whether the IP address in question
     * is within the range of the target IP/CIDR combination.
     *
     * @throws \Darsyn\IP\Exception\InvalidCidrException
     */
    public function inRange(IpInterface $ip, int $cidr): bool
    {
        // If both IP's (ours and theirs) are version 4 according to OUR
        // embedding strategy then attempt to compare them as IPv4 ranges first.
        // This purposefully will not work with comparing IPv4 addresses with
        // IPv4-embedded IPv6 addresses.
        try {
            if ($this->isVersion4() && $ip->isVersion4() && $this->embeddingStrategy->isEmbedded($ip->getBinary())) {
                $ours = $this->getShortBinary();
                $theirs = $this->embeddingStrategy->extract($ip->getBinary());
                if ((new IPv4($ours))->inRange(new IPv4($theirs), $cidr)) {
                    return true;
                }
            }
        } catch (Exception\Strategy\ExtractionException $e) {
        } catch (Exception\InvalidIpAddressException $e) {
        }
        // If they are not in range as IPv4 addresses, then just carry on and
        // compare them as normal IPv6 addresses.
        return parent::inRange($ip, $cidr);
    }

    /**
     * {@inheritDoc}
     */
    public function isEmbedded(): bool
    {
        if (null === $this->embedded) {
            $this->embedded = $this->embeddingStrategy->isEmbedded($this->getBinary());
        }
        return $this->embedded;
    }

    /**
     * {@inheritDoc}
     */
    public function isLinkLocal(): bool
    {
        return parent::isLinkLocal()
            || $this->isEmbedded()
            && (new IPv4($this->getShortBinary()))->isLinkLocal();
    }

    /**
     * {@inheritDoc}
     */
    public function isLoopback(): bool
    {
        return parent::isLoopback()
            || $this->isEmbedded()
            && (new IPv4($this->getShortBinary()))->isLoopback();
    }

    /**
     * {@inheritDoc}
     */
    public function isMulticast(): bool
    {
        return parent::isMulticast()
            || $this->isEmbedded()
            && (new IPv4($this->getShortBinary()))->isMulticast();
    }

    /**
     * {@inheritDoc}
     */
    public function isPrivateUse(): bool
    {
        return parent::isPrivateUse()
            || $this->isEmbedded()
            && (new IPv4($this->getShortBinary()))->isPrivateUse();
    }

    /**
     * {@inheritDoc}
     */
    public function isUnspecified(): bool
    {
        return parent::isUnspecified()
            || $this->isEmbedded()
            && (new IPv4($this->getShortBinary()))->isUnspecified();
    }

    private function getShortBinary(): string
    {
        return $this->embeddingStrategy->extract($this->getBinary());
    }

    /**
     * Is the CIDR provided appropriate for use with IPv4 addresses?
     */
    private function isCidrVersion4Appropriate(int $cidr): bool
    {
        return is_int($cidr) && $cidr <= 32;
    }
}
