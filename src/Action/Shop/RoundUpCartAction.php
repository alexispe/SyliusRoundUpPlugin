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

namespace Alexispe\SyliusRoundUpPlugin\Action\Shop;

use Alexispe\SyliusRoundUpPlugin\Resolver\RoundUpProductResolver;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\View;
use Sylius\Bundle\ResourceBundle\Controller\AuthorizationCheckerInterface;
use Sylius\Bundle\ResourceBundle\Controller\EventDispatcherInterface;
use Sylius\Bundle\ResourceBundle\Controller\RedirectHandlerInterface;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfiguration;
use Sylius\Bundle\ResourceBundle\Controller\RequestConfigurationFactoryInterface;
use Sylius\Bundle\ResourceBundle\Controller\ViewHandlerInterface;
use Sylius\Component\Core\Factory\CartItemFactoryInterface;
use Sylius\Component\Order\CartActions;
use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Model\OrderInterface;
use Sylius\Component\Order\Model\OrderItemInterface;
use Sylius\Component\Order\Modifier\OrderItemQuantityModifierInterface;
use Sylius\Component\Order\Modifier\OrderModifierInterface;
use Sylius\Component\Resource\Metadata\MetadataInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class RoundUpCartAction extends AbstractController
{
    public function __construct(
        ContainerInterface $container,
        private MetadataInterface $metadata,
        private RequestConfigurationFactoryInterface $requestConfigurationFactory,
        private ?ViewHandlerInterface $viewHandler,
        private EventDispatcherInterface $eventDispatcher,
        private RedirectHandlerInterface $redirectHandler,
        private AuthorizationCheckerInterface $authorizationChecker,
        private CartContextInterface $cartContext,
        private OrderModifierInterface $orderModifier,
        private EntityManagerInterface $entityManager,
        private CartItemFactoryInterface $cartItemFactory,
        private OrderItemQuantityModifierInterface $orderItemQuantityModifier,
        private RoundUpProductResolver $roundUpProductResolver,
    ) {
        $this->container = $container;
    }

    public function __invoke(Request $request): Response
    {
        $cart = $this->getCurrentCart();

        $configuration = $this->requestConfigurationFactory->create($this->metadata, $request);

        $this->isGrantedOr403($configuration, CartActions::ADD);
        $roundUpProduct = $this->roundUpProductResolver->resolve();

        if (null === $roundUpProduct) {
            $this->addFlash('error', 'alexispe_sylius_round_up_plugin.ui.product_not_found');

            return $this->redirectHandler->redirectToIndex($configuration);
        }

        $orderItem = $this->cartItemFactory->createForProduct($roundUpProduct);

        $this->orderItemQuantityModifier->modify($orderItem, 1);

        $this->orderModifier->addToOrder($cart, $orderItem);

        $this->entityManager->persist($cart);
        $this->entityManager->flush();

        $orderItem = $this->resolveAddedOrderItem($cart, $orderItem);

        $resourceControllerEvent = $this->eventDispatcher->dispatchPostEvent(CartActions::ADD, $configuration, $orderItem);
        if ($resourceControllerEvent->hasResponse()) {
            /** @var Response $response */
            $response = $resourceControllerEvent->getResponse();

            return $response;
        }

        $this->addFlash('success', 'alexispe_sylius_round_up_plugin.ui.cart_rounded_up');

        if ($request->isXmlHttpRequest()) {
            /** @var ViewHandlerInterface $viewHandler */
            $viewHandler = $this->viewHandler;

            return $viewHandler->handle($configuration, View::create([], Response::HTTP_CREATED));
        }

        return $this->redirectHandler->redirectToResource($configuration, $orderItem);
    }

    private function getCurrentCart(): OrderInterface
    {
        return $this->cartContext->getCart();
    }

    private function resolveAddedOrderItem(OrderInterface $order, OrderItemInterface $item): OrderItemInterface
    {
        /** @var OrderItemInterface $orderItem */
        $orderItem = $order->getItems()->filter(fn (OrderItemInterface $orderItem): bool => $orderItem->equals($item))->first();

        return $orderItem;
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
