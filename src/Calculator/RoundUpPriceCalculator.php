<?php

/*
 * This file is part of the SyliusRoundUpPlugin package.
 *
 * (c) Alexis Petit
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Alexispe\SyliusRoundUpPlugin\Calculator;

use Sylius\Component\Order\Model\OrderInterface;

class RoundUpPriceCalculator
{
    public function calculate(OrderInterface $cart): int
    {
        $total = $cart->getTotal();

        $decimal = $total % 100;

        return 100 - $decimal;
    }
}
