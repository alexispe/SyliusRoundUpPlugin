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

namespace Alexispe\SyliusRoundUpPlugin\EventListener;

use Alexispe\SyliusRoundUpPlugin\Calculator\RoundUpPriceCalculator;
use Alexispe\SyliusRoundUpPlugin\Resolver\RoundUpOrderItemResolver;
use Doctrine\Persistence\ObjectManager;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Webmozart\Assert\Assert;

class CartUpdatedListener
{
    public function __construct(
        private RoundUpPriceCalculator $roundUpPriceCalculator,
        private ObjectManager $orderManager,
        private RoundUpOrderItemResolver $roundUpOrderItemResolver,
    ) {
    }

    public function recalculateRoundUp(GenericEvent $event): void
    {
        $cart = $event->getSubject();
        Assert::isInstanceOf($cart, OrderInterface::class);

        $orderItem = $this->roundUpOrderItemResolver->resolve($cart);

        if ($orderItem === null) {
            return;
        }

        $orderItem->setUnitPrice($this->roundUpPriceCalculator->calculate($cart));
        $orderItem->setImmutable(true);
        $this->orderManager->persist($orderItem);
        $this->orderManager->flush();
    }
}
