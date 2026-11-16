<?php

namespace App\Console\Commands;

use App\Models\Shop;
use App\Services\Shops\ShopVerificationService;
use Illuminate\Console\Command;

/**
 * Verifica (o retira la verificación de) un taller.
 * Se invoca desde código en Admin\ShopVerificationController.
 */
class VerifyShop extends Command
{
    protected $signature = 'artesa:verify-shop
                            {shop : ID o slug del taller}
                            {--revoke : Retirar la verificación en lugar de concederla}';

    protected $description = 'Verifica un taller artesano o retira su verificación';

    public function handle(ShopVerificationService $service): int
    {
        $shopArgument = $this->argument('shop');

        $shop = Shop::where('id', $shopArgument)->orWhere('slug', $shopArgument)->first();

        if (! $shop) {
            $this->error("No existe el taller «{$shopArgument}».");

            return self::FAILURE;
        }

        if ($this->option('revoke')) {
            $service->revoke($shop);
            $this->info("Verificación retirada al taller «{$shop->name}». Sus productos activos pasan a inactivos.");

            return self::SUCCESS;
        }

        if ($shop->is_verified) {
            $this->warn("El taller «{$shop->name}» ya estaba verificado.");

            return self::SUCCESS;
        }

        $service->verify($shop);
        $this->info("Taller «{$shop->name}» verificado.");

        return self::SUCCESS;
    }
}
