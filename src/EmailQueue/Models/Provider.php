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
use pointybeard\Symphony\Extensions\EmailQueue;
use pointybeard\Symphony\Extensions\EmailQueue\Traits;

final class Provider extends Classmapper\AbstractModel implements Classmapper\Interfaces\FilterableModelInterface, Classmapper\Interfaces\SortableModelInterface
{
    use Traits\HasUuidTrait;
    use Classmapper\Traits\HasModelTrait;
    use Classmapper\Traits\HasFilterableModelTrait;
    use Classmapper\Traits\HasSortableModelTrait;

    public function getSectionHandle(): string
    {
        return 'email-providers';
    }

    protected static function getCustomFieldMapping(): array
    {
        return [
            'name' => [
                'flags' => self::FLAG_STR | self::FLAG_SORTBY | self::FLAG_SORTASC | self::FLAG_REQUIRED,
            ],
            'classname' => [
                'flags' => self::FLAG_STR | self::FLAG_REQUIRED,
            ],
        ];
    }

    public static function loadFromClassname(string $classname): ?self
    {
        $result = self::fetch(
            Classmapper\FilterFactory::build('Basic', 'classname', $classname)
        )->current();

        // current() returns true/false but we want to be returning NULL so we
        // cannot use the return value directly.
        return $result instanceof self ? $result : null;
    }

    public static function register(string $name, string $classname): self
    {
        if (false == self::isProviderClassnameValid($classname)) {
            throw new EmailQueue\Exceptions\ProviderClassnameInvalidException($classname);
        }

        if (self::loadFromClassname($classname) instanceof self) {
            throw new EmailQueue\Exceptions\ProviderClassnameAlreadyRegistered($classname);
        }

        return (new self())
            ->name($name)
            ->classname($classname)
            ->save()
        ;
    }

    protected static function isProviderClassnameValid($classname): bool
    {
        return true == class_exists($classname);
    }

    public function instanciate(): EmailQueue\AbstractProvider
    {
        if (false == self::isProviderClassnameValid($this->classname())) {
            throw new EmailQueue\Exceptions\ProviderClassnameInvalidException($this->classname());
        }

        return new $this->classname();
    }
}
