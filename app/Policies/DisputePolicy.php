<?php

namespace App\Policies;

use App\Models\Dispute;
use App\Models\User;

class DisputePolicy
{
    /**
     * El Super Admin tiene autorización automática para cualquier acción.
     */
    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    /**
     * Visualización de la disputa:
     * Restringida al comprador que la abrió, al taller artesano afectado o a moderadores.
     */
    public function view(User $user, Dispute $dispute): bool
    {
        if ($user->hasRole('moderator')) {
            return true;
        }

        if ($user->id === $dispute->customer_id) {
            return true;
        }

        if ($user->hasRole('artisan') && $user->shop) {
            return $user->shop->id === $dispute->shop_id;
        }

        return false;
    }

    /**
     * Participar con mensajes en la disputa:
     * Si no está cerrada, pueden responder el comprador, el artesano o el moderador.
     */
    public function reply(User $user, Dispute $dispute): bool
    {
        if (in_array($dispute->status, ['resolved', 'closed'], true)) {
            return false;
        }

        return $this->view($user, $dispute);
    }

    /**
     * Resolución y dictamen final de la disputa:
     * Exclusivo del moderador o administrador.
     */
    public function resolve(User $user, Dispute $dispute): bool
    {
        return $user->hasRole('moderator');
    }
}
