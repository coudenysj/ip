<?php declare(strict_types=1);

namespace Darsyn\IP\Strategy;

abstract class AbstractStrategy implements EmbeddingStrategyInterface
{
    protected function getBinaryLength(string $binary): int
    {
        return strlen(bin2hex($binary)) / 2;
    }

    protected function getBinaryFromHex(string $hex): string
    {
        return pack('H*', $hex);
    }
}
