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

class ProviderFactory extends Factory\AbstractFactory
{
    public function getTemplateNamespace(): string
    {
        return __NAMESPACE__.'\\Providers\\%s';
    }

    public function getExpectedClassType(): ?string
    {
        return __NAMESPACE__.'\\AbstractProvider';
    }
}
