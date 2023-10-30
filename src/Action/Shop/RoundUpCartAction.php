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

use Alexispe\SyliusRoundUpPlugin\Calculator\RoundUpPriceCalculator;
use Alexispe\SyliusRoundUpPlugin\Resolver\RoundUpProductResolver;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\View\View;
use Sylius\Bundle\ResourceBundle\Controller\AuthorizationCheckerInterface;
use Sylius\Bundle\ResourceBundle\Controller\EventDispatcherInterface;
use Sylius\Bundle\ResourceBundle\Controller\FlashHelperInterface;
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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class RoundUpCartAction extends AbstractController
{
    protected MetadataInterface $metadata;
    protected RequestConfigurationFactoryInterface $requestConfigurationFactory;
    private EventDispatcherInterface $eventDispatcher;
    private FlashHelperInterface $flashHelper;
    private ?ViewHandlerInterface $viewHandler;
    private RedirectHandlerInterface $redirectHandler;
    private CartContextInterface $cartContext;
    private OrderModifierInterface $orderModifier;
    private EntityManagerInterface $entityManager;
    private CartItemFactoryInterface $cartItemFactory;
    private OrderItemQuantityModifierInterface $orderItemQuantityModifier;
    private AuthorizationCheckerInterface $authorizationChecker;
    private RoundUpProductResolver $roundUpProductResolver;
    private RoundUpPriceCalculator $roundUpPriceCalculator;

    public function __construct(
        MetadataInterface $metadata,
        RequestConfigurationFactoryInterface $requestConfigurationFactory,
        ?ViewHandlerInterface $viewHandler,
        EventDispatcherInterface $eventDispatcher,
        RedirectHandlerInterface $redirectHandler,
        FlashHelperInterface $flashHelper,
        AuthorizationCheckerInterface $authorizationChecker,
        CartContextInterface $cartContext,
        OrderModifierInterface $orderModifier,
        EntityManagerInterface $entityManager,
        CartItemFactoryInterface $cartItemFactory,
        OrderItemQuantityModifierInterface $orderItemQuantityModifier,
        RoundUpProductResolver $roundUpProductResolver,
        RoundUpPriceCalculator $roundUpPriceCalculator
    ) {
        $this->metadata = $metadata;
        $this->requestConfigurationFactory = $requestConfigurationFactory;
        $this->eventDispatcher = $eventDispatcher;
        $this->flashHelper = $flashHelper;
        $this->viewHandler = $viewHandler;
        $this->redirectHandler = $redirectHandler;
        $this->cartContext = $cartContext;
        $this->orderModifier = $orderModifier;
        $this->entityManager = $entityManager;
        $this->cartItemFactory = $cartItemFactory;
        $this->orderItemQuantityModifier = $orderItemQuantityModifier;
        $this->authorizationChecker = $authorizationChecker;
        $this->roundUpProductResolver = $roundUpProductResolver;
        $this->roundUpPriceCalculator = $roundUpPriceCalculator;
    }

    public function __invoke(Request $request): Response
    {
        $cart = $this->getCurrentCart();
        $configuration = $this->requestConfigurationFactory->create($this->metadata, $request);

        $this->isGrantedOr403($configuration, CartActions::ADD);
        $roundUpProduct = $this->roundUpProductResolver->resolve();

        $orderItem = $this->cartItemFactory->createForProduct($roundUpProduct);

        $this->orderItemQuantityModifier->modify($orderItem, 1);

        $this->orderModifier->addToOrder($cart, $orderItem);

        $cartManager = $this->entityManager;
        $cartManager->persist($cart);
        $cartManager->flush();

        $orderItem = $this->resolveAddedOrderItem($cart, $orderItem);

        $orderItem->setUnitPrice($this->roundUpPriceCalculator->calculate($cart));
        $cartManager->persist($orderItem);
        $cartManager->flush();

        $resourceControllerEvent = $this->eventDispatcher->dispatchPostEvent(CartActions::ADD, $configuration, $orderItem);
        if ($resourceControllerEvent->hasResponse()) {
            return $resourceControllerEvent->getResponse();
        }

        $this->flashHelper->addSuccessFlash($configuration, CartActions::ADD, $orderItem);

        if ($request->isXmlHttpRequest()) {
            return $this->viewHandler->handle($configuration, View::create([], Response::HTTP_CREATED));
        }

        return $this->redirectHandler->redirectToResource($configuration, $orderItem);
    }

    protected function getCurrentCart(): OrderInterface
    {
        return $this->cartContext->getCart();
    }

    protected function resolveAddedOrderItem(OrderInterface $order, OrderItemInterface $item): OrderItemInterface
    {
        return $order->getItems()->filter(fn (OrderItemInterface $orderItem): bool => $orderItem->equals($item))->first();
    }

    /**
     * @throws AccessDeniedException
     */
    protected function isGrantedOr403(RequestConfiguration $configuration, string $permission): void
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
