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

namespace Alexispe\SyliusRoundUpPlugin\Processor;

use Alexispe\SyliusRoundUpPlugin\Calculator\RoundUpPriceCalculator;
use Alexispe\SyliusRoundUpPlugin\Model\AdjustmentInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Factory\AdjustmentFactoryInterface;
use Sylius\Component\Order\Model\OrderInterface as BaseOrderInterface;
use Sylius\Component\Order\Processor\OrderProcessorInterface;
use Webmozart\Assert\Assert;

final class RoundUpProcessor implements OrderProcessorInterface
{
    public function __construct(
        private AdjustmentFactoryInterface $adjustmentFactory,
        private RoundUpPriceCalculator $roundUpPriceCalculator,
    ) {
    }

    public function process(BaseOrderInterface $order): void
    {
        Assert::isInstanceOf($order, OrderInterface::class);

        if (!$order->canBeProcessed()) {
            return;
        }

        if (false === $order->getAdjustments(AdjustmentInterface::ROUND_UP_ADJUSTMENT)->isEmpty()) {
            $order->removeAdjustments(AdjustmentInterface::ROUND_UP_ADJUSTMENT);

            $roundUpAmount = $this->roundUpPriceCalculator->calculate($order);

            /** @var AdjustmentInterface $adjustment */
            $adjustment = $this->adjustmentFactory->createNew();
            $adjustment->setType(AdjustmentInterface::ROUND_UP_ADJUSTMENT);
            $adjustment->setAmount($roundUpAmount);
            $adjustment->setLabel('Round Up');

            $order->addAdjustment($adjustment);
        }
    }
}
