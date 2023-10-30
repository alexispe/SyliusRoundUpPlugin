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

  - PHPUnit

    ```bash
    vendor/bin/phpunit
    ```

  - PHPSpec

    ```bash
    vendor/bin/phpspec run
    ```

  - Behat (non-JS scenarios)

    ```bash
    vendor/bin/behat --strict --tags="~@javascript"
    ```

  - Behat (JS scenarios)
 
    1. [Install Symfony CLI command](https://symfony.com/download).
 
    2. Start Headless Chrome:
    
      ```bash
      google-chrome-stable --enable-automation --disable-background-networking --no-default-browser-check --no-first-run --disable-popup-blocking --disable-default-apps --allow-insecure-localhost --disable-translate --disable-extensions --no-sandbox --enable-features=Metal --headless --remote-debugging-port=9222 --window-size=2880,1800 --proxy-server='direct://' --proxy-bypass-list='*' http://127.0.0.1
      ```
    
    3. Install SSL certificates (only once needed) and run test application's webserver on `127.0.0.1:8080`:
    
      ```bash
      symfony server:ca:install
      APP_ENV=test symfony server:start --port=8080 --dir=tests/Application/public --daemon
      ```
    
    4. Run Behat:
    
      ```bash
      vendor/bin/behat --strict --tags="@javascript"
      ```
    
  - Static Analysis
  
    - Psalm
    
      ```bash
      vendor/bin/psalm
      ```
      
    - PHPStan
    
      ```bash
      vendor/bin/phpstan analyse -c phpstan.neon -l max src/  
      ```

  - Coding Standard
  
    ```bash
    vendor/bin/ecs check
    ```
