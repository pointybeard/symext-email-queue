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

namespace pointybeard\Symphony\Extensions\EmailQueue\Exceptions;

final class EmailAlreadySentException extends EmailQueueException
{
    public function __construct(string $uuid, $code = 0, \Exception $previous = null)
    {
        parent::__construct("Email with UUID {$uuid} has already been sent.", $code, $previous);
    }
}
