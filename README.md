# SyliusRoundUpPlugin

This plugin simply round up your cart.

![Screenshot of cart page](https://github.com/alexispe/SyliusRoundUpPlugin/blob/main/docs/demo-cart.png?raw=true)

## Installation

### 1. Composer

`composer require alexispe/sylius-round-up-plugin`

### 2. Load bundle

Add to the bundle list in `config/bundles.php`:

```php
<?php

return [
    // ...
    Alexispe\SyliusRoundUpPlugin\AlexispeSyliusRoundUpPlugin::class => ['all' => true],
    // ...
];
```

### 3. Load configuration

Add to the imports list in `config/packages/_sylius.yaml`:

```yaml
imports:
    ...
    - { resource: "@AlexispeSyliusRoundUpPlugin/config/config.yml" }
```

### 4. Create round up product
```
bin/console alexispe:round-up:create-product
```

## Contribute

### Quickstart Installation

1. Execute `docker compose up -d`

2. Initialize plugin `docker compose exec app make init`

3. See your browser `open localhost`

### Running plugin tests

```bash
docker compose exec app make phpstan
docker compose exec app make psalm
docker compose exec app make phpunit
docker compose exec app make phpspec
docker compose exec app make behat
```
