<?php

namespace App\Exceptions\Domain;

use DomainException;

/**
 * Excepción base de las reglas de negocio de Artesa.
 * Los controladores la convierten en un mensaje de error para el usuario.
 */
class ArtesaDomainException extends DomainException
{
}
