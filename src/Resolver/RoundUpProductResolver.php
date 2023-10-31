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

use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;

class RoundUpProductResolver {

    private ProductRepositoryInterface $productRepository;
    private string $roundUpProductCode;

    public function __construct(
        string $roundUpProductCode,
        ProductRepositoryInterface $productRepository
    ) {
        $this->productRepository = $productRepository;
        $this->roundUpProductCode = $roundUpProductCode;
    }

    public function resolve(): ?ProductInterface
    {
        return $this->productRepository->findOneByCode($this->roundUpProductCode);
    }
}
