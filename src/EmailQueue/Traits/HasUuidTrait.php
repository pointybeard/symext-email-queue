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

namespace pointybeard\Symphony\Extensions\EmailQueue\Traits;

use pointybeard\Symphony\Classmapper\FilterFactory;

trait HasUuidTrait
{
    public static function loadFromUuid(string $uuid): ?self
    {
        $result = self::fetch(
            FilterFactory::build('Basic', 'uuid', $uuid)
        )->current();

        return $result instanceof self
            ? $result
            : null
        ;
    }
}
