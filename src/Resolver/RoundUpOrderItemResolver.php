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

namespace Alexispe\SyliusRoundUpPlugin\Resolver;

use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;

class RoundUpOrderItemResolver
{
    public function __construct(
        private string $roundUpProductCode,
    ) {
    }

    public function resolve(OrderInterface $order): ?OrderItemInterface
    {
        foreach ($order->getItems() as $item) {
            if ($item->getProduct()?->getCode() === $this->roundUpProductCode) {
                return $item;
            }
        }

        return null;
    }
}
