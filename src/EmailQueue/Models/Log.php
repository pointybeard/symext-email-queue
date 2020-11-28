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

namespace pointybeard\Symphony\Extensions\EmailQueue\Models;

use pointybeard\Symphony\Classmapper;

final class Log extends Classmapper\AbstractModel implements Classmapper\Interfaces\FilterableModelInterface, Classmapper\Interfaces\SortableModelInterface
{
    use Classmapper\Traits\HasModelTrait;
    use Classmapper\Traits\HasFilterableModelTrait;
    use Classmapper\Traits\HasSortableModelTrait;

    const STATUS_FAILED = 'Failed';
    const STATUS_SENT = 'Sent';

    public function getSectionHandle(): string
    {
        return 'email-logs';
    }

    protected static function getCustomFieldMapping(): array
    {
        return [
            'email' => [
                'databaseFieldName' => 'relation_id',
                'classMemberName' => 'emailId',
                'flags' => self::FLAG_INT | self::FLAG_REQUIRED,
            ],

            'date' => [
                'classMemberName' => 'dateCreatedAt',
                'flags' => self::FLAG_SORTBY | self::FLAG_SORTASC | self::FLAG_REQUIRED,
            ],
        ];
    }
}
