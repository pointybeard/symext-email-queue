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

namespace pointybeard\Symphony\Extensions\EmailQueue\Models;

use pointybeard\Symphony\Classmapper;
use pointybeard\Symphony\Extensions\EmailQueue\Exceptions;
use pointybeard\Symphony\Extensions\EmailQueue\Traits;
use pointybeard\Symphony\Extensions\Settings;

final class Template extends Classmapper\AbstractModel implements Classmapper\Interfaces\FilterableModelInterface, Classmapper\Interfaces\SortableModelInterface
{
    use Traits\HasUuidTrait;
    use Classmapper\Traits\HasModelTrait;
    use Classmapper\Traits\HasFilterableModelTrait;
    use Classmapper\Traits\HasSortableModelTrait;

    public function getSectionHandle(): string
    {
        return 'email-templates';
    }

    protected static function getCustomFieldMapping(): array
    {
        return [
            'external-template-uid' => [
                'flags' => self::FLAG_INT,
            ],

            'fields' => [
                'databaseFieldName' => 'relation_id',
                'classMemberName' => 'fields',
                'flags' => self::FLAG_ARRAY | self::FLAG_INT | self::FLAG_NULL,
            ],

            'provider' => [
                'databaseFieldName' => 'relation_id',
                'classMemberName' => 'providerId',
                'flags' => self::FLAG_INT | self::FLAG_REQUIRED,
            ],

            'name' => [
                'flags' => self::FLAG_STR | self::FLAG_SORTBY | self::FLAG_SORTASC | self::FLAG_REQUIRED,
            ],
        ];
    }

    public static function loadFromName(string $name): ?self
    {
        $result = self::fetch(
            Classmapper\FilterFactory::build('Basic', 'name', $name)
        )->current();

        return $result instanceof self ? $result : null;
    }

    public function populateFieldsArrayFromData(array $data): array
    {
        // Guard
        $fields = $this->fields();
        if (false == ($fields instanceof \SymphonyPDO\Lib\ResultIterator)) {
            return $data;
        }

        $errors = [];

        foreach ($fields as $f) {
            // Make sure each field appears in the data array, even if it's an empty string
            $data[$f->name] ?? '';

            // Check for a default value
            if (strlen(trim((string) $data[$f->name])) <= 0 && strlen(trim((string) $f->defaultValue)) > 0) {
                $data[$f->name] = $f->defaultValue;
            }

            if (false == is_string($data[$f->name]) && false == is_array($data[$f->name])) {
                $errors[] = "{$f->name}: Required field is missing or invalid.";
            }

            if (false == empty($errors)) {
                throw new Exceptions\EmailQueueException('Populating fields array failed. Returned: '.implode(PHP_EOL, $errors));
            }
        }

        return $data;
    }

    public function send(string $recipientEmailAddress, Settings\SettingsResultIterator $credentials, array $data = [], array $attachments = [], string $replyTo = null, string $cc = null): void
    {
        // Send the email via the provider for this template
        $this->provider()->instanciate()->send(
            $credentials,
            $this,
            $recipientEmailAddress,
            $this->populateFieldsArrayFromData($data),
            $attachments,
            $replyTo,
            $cc
        );
    }

    public function provider(): ?Provider
    {
        return Provider::loadFromId($this->providerId);
    }

    public function fields(): ?\SymphonyPDO\Lib\ResultIterator
    {
        $fieldIds = $this->fields;

        if (false == is_array($fieldIds)) {
            if (strlen(trim((string) $fieldIds)) <= 0) {
                return null;
            }

            // We have the field IDs, but as a CSV string. Need to explode
            // that and then create some custom SQL to pull them all out.
            $fieldIds = explode(',', $fieldIds);
        } elseif (true == empty($fieldIds)) {
            return null;
        }

        // Convert the ID values from strings to integers.
        $fieldIds = array_map('intval', $fieldIds);

        // Remove duplicates
        $fieldIds = array_unique($fieldIds, SORT_NUMERIC);

        return TemplateField::fetchFromIdList($fieldIds);
    }
}
