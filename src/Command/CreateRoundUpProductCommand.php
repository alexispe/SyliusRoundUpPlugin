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

namespace Alexispe\SyliusRoundUpPlugin\Command;

use Sylius\Component\Channel\Repository\ChannelRepositoryInterface;
use Sylius\Component\Core\Model\ChannelPricing;
use Sylius\Component\Core\Model\ChannelPricingInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\Component\Product\Factory\ProductFactoryInterface;
use Sylius\Component\Product\Factory\ProductVariantFactoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(name: 'alexispe:round-up:create-product')]
class CreateRoundUpProductCommand extends Command
{
    private ProductFactoryInterface $productFactory;
    private ProductRepositoryInterface $productRepository;
    private string $roundUpProductCode;
    private ProductVariantFactoryInterface $productVariantFactory;
    private ChannelRepositoryInterface $channelRepository;
    private TranslatorInterface $translator;

    public function __construct(
        string $roundUpProductCode,
        ProductFactoryInterface $productFactory,
        ProductRepositoryInterface $productRepository,
        ProductVariantFactoryInterface $productVariantFactory,
        ChannelRepositoryInterface $channelRepository,
        TranslatorInterface $translator
    ) {
        parent::__construct();
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->roundUpProductCode = $roundUpProductCode;
        $this->productVariantFactory = $productVariantFactory;
        $this->channelRepository = $channelRepository;
        $this->translator = $translator;
    }

    protected static $defaultName = 'alexispe:round-up:create-product';

    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Delete existing round up product if present')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($existingProduct = $this->productRepository->findOneByCode($this->roundUpProductCode)) {
            if ($input->getOption('force')) {
                $this->productRepository->remove($existingProduct);
            } else {
                $io->error('Product already exists, use --force to delete it');
                return Command::SUCCESS;
            }
        }

        /** @var ProductInterface $product */
        $product = $this->productFactory->createNew();

        $product->setName($this->translator->trans('alexispe_sylius_round_up_plugin.product.round_up'));
        $product->setCode($this->roundUpProductCode);
        $product->setSlug('round-up');
        $product->setEnabled(true);

        $productVariant = $this->productVariantFactory->createNew();
        $productVariant->setCode($this->roundUpProductCode);

        $channels = $this->channelRepository->findAll();
        foreach ($channels as $channel) {
            $channelPricing = new ChannelPricing();
            $channelPricing->setChannelCode($channel->getCode());
            $channelPricing->setPrice(0);
            $channelPricing->setOriginalPrice(0);
            $productVariant->addChannelPricing($channelPricing);
        }

        $product->addVariant($productVariant);

        $this->productRepository->add($product);

        $io->success('Product created');

        return Command::SUCCESS;
    }
}