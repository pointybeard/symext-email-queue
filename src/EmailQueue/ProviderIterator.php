<?php

declare(strict_types=1);

/*
 * This file is part of the "Email Queue Extension for Symphony CMS" repository.
 *
 * Copyright 2020-2021 Alannah Kearney <hi@alannahkearney.com>
 *
 * For the full copyright and license information, please view the LICENCE
 * file that was distributed with this source code.
 */

namespace pointybeard\Symphony\Extensions\EmailQueue;

use pointybeard\Helpers\Foundation\Factory;

class ProviderIterator extends \RegexIterator
{
    public static function init(): void
    {
        // We only want this to happen once
        if (class_exists(__NAMESPACE__.'\\ProviderFactory')) {
            return;
        }

        Factory\create(
            __NAMESPACE__.'\\ProviderFactory',
            __NAMESPACE__.'\\Providers\%s',
            __NAMESPACE__.'\\AbstractProvider'
        );
    }

    public function __construct()
    {
        // Make sure the field factory has been created
        self::init();

        $providers = new \ArrayIterator();

        foreach (new \DirectoryIterator(__DIR__.'/Providers') as $p) {
            if (true == $p->isDot() || true == $p->isDir()) {
                continue;
            }
            $providers->append($p->getPathname());
        }

        parent::__construct(
            $providers,
            "@([^\/\.]+)\.php$@",
            \RegexIterator::GET_MATCH
        );
    }

    public function current(): AbstractProvider
    {
        $name = parent::current()[1];

        return ProviderFactory::build($name);
    }

    /**
     * Passes each record into $callback.
     *
     * @return int Returns total number of items iterated over
     */
    public function each(callable $callback, array $args = [])
    {
        $count = 0;

        // Ensure we're at the start of the iterator
        $this->rewind();

        // Loop over every item in the iterator
        while ($this->valid()) {
            // Execute the callback, giving it the data and any argments passed in
            $callback($this->current(), $args);
            // Move the cursor
            $this->next();
            // Keep track of the number of items we've looped over
            ++$count;
        }

        // Go back to the start
        $this->rewind();

        return $count;
    }
}
