<?php

namespace App\Exceptions\Domain;

/**
 * Una línea de pedido no es coherente con su pedido, subpedido, taller o variante.
 */
class InvalidOrderLineException extends ArtesaDomainException
{
}
