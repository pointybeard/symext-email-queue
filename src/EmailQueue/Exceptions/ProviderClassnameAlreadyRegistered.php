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

namespace pointybeard\Symphony\Extensions\EmailQueue\Exceptions;

final class ProviderClassnameAlreadyRegistered extends EmailQueueException
{
    public function __construct(string $classname, $code = 0, \Exception $previous = null)
    {
        parent::__construct("Supplied provider classname '{$classname}' is already registered.", $code, $previous);
    }
}
