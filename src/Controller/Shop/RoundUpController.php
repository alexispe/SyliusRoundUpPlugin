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

namespace Alexispe\SyliusRoundUpPlugin\Controller\Shop;

use Alexispe\SyliusRoundUpPlugin\Calculator\RoundUpPriceCalculator;
use Alexispe\SyliusRoundUpPlugin\Model\AdjustmentInterface;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\View;
use Psr\Container\ContainerInterface;
use Sylius\Bundle\ResourceBundle\Controller\AuthorizationCheckerInterface;
use Sylius\Bundle\ResourceBundle\Controller\RedirectHandlerInterface;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfigurationFactoryInterface;
use Sylius\Bundle\ResourceBundle\Controller\ViewHandlerInterface;
use Sylius\Component\Order\CartActions;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Factory\AdjustmentFactoryInterface;
use Sylius\Component\Order\Model\OrderInterface;
use Sylius\Component\Resource\Metadata\MetadataInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class RoundUpController extends AbstractController
{
    public function __construct(
        ContainerInterface $container,
        private MetadataInterface $metadata,
        private RequestConfigurationFactoryInterface $requestConfigurationFactory,
        private ?ViewHandlerInterface $viewHandler,
        private RedirectHandlerInterface $redirectHandler,
        private AuthorizationCheckerInterface $authorizationChecker,
        private CartContextInterface $cartContext,
        private EntityManagerInterface $entityManager,
        private AdjustmentFactoryInterface $adjustmentFactory,
        private RoundUpPriceCalculator $roundUpPriceCalculator,
        private TranslatorInterface $translator,
    ) {
        $this->container = $container;
    }

    public function addToCart(Request $request): Response
    {
        $cart = $this->getCurrentCart();

        $configuration = $this->requestConfigurationFactory->create($this->metadata, $request);

        $this->isGrantedOr403($configuration, CartActions::ADD);

        $cart->removeAdjustments(AdjustmentInterface::ROUND_UP_ADJUSTMENT);

        $roundUpAmount = $this->roundUpPriceCalculator->calculate($cart);

        /** @var AdjustmentInterface $adjustment */
        $adjustment = $this->adjustmentFactory->createNew();
        $adjustment->setType(AdjustmentInterface::ROUND_UP_ADJUSTMENT);
        $adjustment->setAmount($roundUpAmount);
        $adjustment->setLabel('Round Up');

        $cart->addAdjustment($adjustment);

        $this->entityManager->persist($cart);
        $this->entityManager->flush();

        $this->addFlash('success', $this->translator->trans('alexispe_sylius_round_up_plugin.ui.cart_rounded_up'));

        if ($request->isXmlHttpRequest()) {
            /** @var ViewHandlerInterface $viewHandler */
            $viewHandler = $this->viewHandler;

            return $viewHandler->handle($configuration, View::create([], Response::HTTP_CREATED));
        }

        return $this->redirectHandler->redirectToResource($configuration, $cart);
    }

    public function removeFromCart(Request $request): Response
    {
        $cart = $this->getCurrentCart();

        $configuration = $this->requestConfigurationFactory->create($this->metadata, $request);

        $this->isGrantedOr403($configuration, CartActions::REMOVE);

        $cart->removeAdjustments(AdjustmentInterface::ROUND_UP_ADJUSTMENT);

        $this->entityManager->persist($cart);
        $this->entityManager->flush();

        $this->addFlash('success', $this->translator->trans('alexispe_sylius_round_up_plugin.ui.undo_cart_rounded_up'));

        if ($request->isXmlHttpRequest()) {
            /** @var ViewHandlerInterface $viewHandler */
            $viewHandler = $this->viewHandler;

            return $viewHandler->handle($configuration, View::create([], Response::HTTP_CREATED));
        }

        return $this->redirectHandler->redirectToResource($configuration, $cart);
    }

    private function getCurrentCart(): OrderInterface
    {
        return $this->cartContext->getCart();
    }

    /**
     * @throws AccessDeniedException
     */
    private function isGrantedOr403(RequestConfiguration $configuration, string $permission): void
    {
        if (!$configuration->hasPermission()) {
            return;
        }

        $permission = $configuration->getPermission($permission);

        if (!$this->authorizationChecker->isGranted($configuration, $permission)) {
            throw new AccessDeniedException();
        }
    }
}
