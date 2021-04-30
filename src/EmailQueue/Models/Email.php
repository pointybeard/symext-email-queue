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

final class Email extends Classmapper\AbstractModel implements Classmapper\Interfaces\FilterableModelInterface, Classmapper\Interfaces\SortableModelInterface
{
    use Traits\HasUuidTrait;
    use Classmapper\Traits\HasModelTrait;
    use Classmapper\Traits\HasFilterableModelTrait;
    use Classmapper\Traits\HasSortableModelTrait;

    protected static function getCustomFieldMapping(): array
    {
        return [
            'recipient' => [
                'flags' => self::FLAG_STR | self::FLAG_REQUIRED,
            ],

            'template' => [
                'databaseFieldName' => 'relation_id',
                'classMemberName' => 'templateId',
                'flags' => self::FLAG_INT | self::FLAG_REQUIRED,
            ],

            'date-created' => [
                'classMemberName' => 'dateCreatedAt',
                'flags' => self::FLAG_SORTBY | self::FLAG_SORTASC | self::FLAG_REQUIRED,
            ],

            'date-sent' => [
                'classMemberName' => 'dateSentAt',
                'flags' => self::FLAG_NULL,
            ],

            'data' => [
                'classMemberName' => 'data',
                'flags' => self::FLAG_NULL,
            ],
        ];
    }

    public static function fetchByRecipient(string $recipient): \SymphonyPDO\Lib\ResultIterator
    {
        return self::fetch(
            Classmapper\FilterFactory::build('Basic', 'recipient', $recipient),
        );
    }

    public static function fetchByStatus(string $status): \SymphonyPDO\Lib\ResultIterator
    {
        return self::fetch(
            Classmapper\FilterFactory::build('Basic', 'status', $status)
        );
    }

    public function template(): ?Template
    {
        return Template::loadFromId($this->templateId);
    }

    public function hasBeenSent(): bool
    {
        return null !== $this->dateSentAt;
    }

    public function save(?int $flags = self::FLAG_ON_SAVE_VALIDATE, string $sectionHandle = null): self
    {
        // Check the contents of data to make sure that any required fields (according to the template) are set
        $this->template()->populateFieldsArrayFromData($this->parseRawDataFieldContents());

        return parent::save($flags, $sectionHandle);
    }

    protected function parseRawDataFieldContents(): array
    {
        try {
            return json_decode($this->data(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Exception $ex) {
            throw new \Exception('Invalid data provided to template. Expecting valid JSON. Returned: '.$ex->getMessage(), $ex->getCode(), $ex);
        }
    }

    public function send(?Settings\SettingsResultIterator $credentials = null, bool $forceSend = false, array $attachments = [], string $replyTo = null, string $cc = null): void
    {
        if (false == $forceSend && true == $this->hasBeenSent()) {
            throw new Exceptions\EmailAlreadySentException($this->uuid);
        }

        try {
            // Make sure template exists
            $template = $this->template();
            if (false == ($template instanceof Template)) {
                throw new \Exception('Invalid template specified.');
            }

            if (null == $credentials) {
                $credentials = Settings\Models\Setting::fetchByGroup($template->provider()->name());
            }

            $result = $template->send(
                $this->recipient(),
                $credentials,
                $this->parseRawDataFieldContents(),
                $attachments,
                $replyTo,
                $cc
            );

            // Sending was successful. Log it.
            (new Log())
                ->emailId($this->id)
                ->dateCreatedAt('now')
                ->status(Log::STATUS_SENT)
                ->message(null)
                ->payload(json_encode([
                    'recipient' => $this->recipient,
                    'fields' => $data,
                    'template' => $template->__toArray(),
                    'replyTo' => $replyTo,
                    'cc' => $cc,
                    'hasAttachments' => (false == empty($attachments)),
                ], JSON_PRETTY_PRINT))
                ->save()
            ;

            // Update this email, marking it as sent.
            $this
                ->dateSentAt('now')
                ->save()
            ;
        } catch (\Exception $ex) {
            throw new Exceptions\SendingEmailFailedException($ex->getMessage(), $this, $data, $this->recipient, $credentials);
        }
    }
}
