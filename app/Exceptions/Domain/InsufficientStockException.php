<?php

namespace App\Exceptions\Domain;

class InsufficientStockException extends ArtesaDomainException
{
    public static function for(string $item, int $requested, int $available): self
    {
        return new self("Stock insuficiente para «{$item}»: se piden {$requested} y quedan {$available}.");
    }
}
