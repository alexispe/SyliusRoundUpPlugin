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

use Alexispe\SyliusRoundUpPlugin\Resolver\RoundUpProductResolver;
use Sylius\Component\Order\Model\OrderItemInterface;
use Sylius\Component\Order\Model\OrderInterface;
use Webmozart\Assert\Assert;

class RoundUpPriceCalculator {
    private RoundUpProductResolver $roundUpProductResolver;

    public function __construct(
        RoundUpProductResolver $roundUpProductResolver
    ) {
        $this->roundUpProductResolver = $roundUpProductResolver;
    }

    public function calculate(OrderInterface $cart): int
    {
        $total = $cart->getTotal();

        if ($cart instanceof \Sylius\Component\Core\Model\OrderInterface) {
            $items = $cart->getItems()->filter(function(\Sylius\Component\Core\Model\OrderItemInterface $item) {
                return $item->getProduct() === $this->roundUpProductResolver->resolve();
            });

            foreach ($items as $item) {
                $total -= $item->getTotal();
            }
        }

        $decimal = $total % 100;

        return 100 - $decimal;
    }
}
