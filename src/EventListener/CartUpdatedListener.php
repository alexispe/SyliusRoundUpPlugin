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
    private RoundUpPriceCalculator $roundUpPriceCalculator;
    private ObjectManager $orderManager;
    private RoundUpOrderItemResolver $roundUpOrderItemResolver;

    public function __construct(
        RoundUpPriceCalculator $roundUpPriceCalculator,
        ObjectManager $orderManager,
        RoundUpOrderItemResolver $roundUpOrderItemResolver
    ) {
        $this->roundUpPriceCalculator = $roundUpPriceCalculator;
        $this->orderManager = $orderManager;
        $this->roundUpOrderItemResolver = $roundUpOrderItemResolver;
    }

    public function recalculateRoundUp(GenericEvent $event): void
    {
        $cart = $event->getSubject();
        Assert::isInstanceOf($cart, OrderInterface::class);

        $orderManager = $this->orderManager;
        $orderItem = $this->roundUpOrderItemResolver->resolve($cart);

        if ($orderItem === null) {
            return;
        }

        $orderItem->setUnitPrice($this->roundUpPriceCalculator->calculate($cart));
        $orderItem->setImmutable(true);
        $orderManager->persist($orderItem);
        $orderManager->flush();
    }
}
