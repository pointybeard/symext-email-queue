<?php

declare(strict_types=1);

/*
 * This file is part of the "Email Queue Extension for Symphony CMS" repository.
 *
 * Copyright 2020 Alannah Kearney <hi@alannahkearney.com>
 *
 * For the full copyright and license information, please view the LICENCE
 * file that was distributed with this source code.
 */

if (!file_exists(__DIR__.'/vendor/autoload.php')) {
    throw new Exception(sprintf('Could not find composer autoload file %s. Did you run `composer update` in %s?', __DIR__.'/vendor/autoload.php', __DIR__));
}

require_once __DIR__.'/vendor/autoload.php';

use pointybeard\Symphony\Extended;
use pointybeard\Symphony\Extensions\EmailQueue;

// Check if the class already exists before declaring it again.
if (!class_exists('\\Extension_EmailQueue')) {
    final class Extension_EmailQueue extends Extended\AbstractExtension
    {
        public function registerEmailProviders(): void
        {
            // Register all providers
            (new EmailQueue\ProviderIterator())->each(function (EmailQueue\AbstractProvider $p) {
                $p->register();
            });
        }

        public function update($previousVersion = false): bool
        {
            parent::update($previousVersion);

            $this->registerEmailProviders();

            return true;
        }

        public function install(): bool
        {
            // Check dependencies
            parent::install();

            $this->registerEmailProviders();

            return true;
        }
    }
}
