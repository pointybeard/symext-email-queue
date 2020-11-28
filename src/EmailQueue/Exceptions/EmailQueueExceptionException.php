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

use pointybeard\Helpers\Exceptions\ReadableTrace;

class EmailQueueExceptionException extends ReadableTrace\ReadableTraceException
{
    public function getReadableTrace(string $format = '[{{PATH}}/{{FILENAME}}:{{LINE}}] {{CLASS}}{{TYPE}}{{FUNCTION}}();'): ?string
    {
        // The trace of any previous exception that is an instance of
        // ReadableTraceException is likely to be much more informative
        // so use that intead
        if ($this->getPrevious() instanceof \pointybeard\Helpers\Exceptions\ReadableTrace\ReadableTraceException) {
            return $this->getPrevious()->getReadableTrace();
        }

        // Otherwise, default to whatever trace we have
        return parent::getReadableTrace();
    }
}
