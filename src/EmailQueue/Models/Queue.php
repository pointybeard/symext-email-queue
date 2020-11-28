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
use pointybeard\Symphony\Extensions\EmailQueue\Traits;

final class Queue extends Classmapper\AbstractModel implements Classmapper\Interfaces\FilterableModelInterface, Classmapper\Interfaces\SortableModelInterface
{
    use Traits\HasUuidTrait;
    use Classmapper\Traits\HasModelTrait;
    use Classmapper\Traits\HasFilterableModelTrait;
    use Classmapper\Traits\HasSortableModelTrait;

    const STATUS_QUEUED = 'Queued';
    const STATUS_FAILED = 'Failed';
    const STATUS_HELD = 'Held';

    const PRIORITY_LOW = '(3) Low';
    const PRIORITY_NORMAL = '(2) Normal';
    const PRIORITY_HIGH = '(1) High';

    public function getSectionHandle(): string
    {
        return 'email-queue';
    }

    protected static function getCustomFieldMapping(): array
    {
        return [
            'email' => [
                'databaseFieldName' => 'relation_id',
                'classMemberName' => 'emailId',
                'flags' => self::FLAG_INT | self::FLAG_REQUIRED,
            ],

            'sent-attempts' => [
                'classMemberName' => 'sendAttemptsRemaining',
                'flags' => self::FLAG_INT,
            ],

            'send-date' => [
                'classMemberName' => 'nextSendAttemptDate',
                'flags' => self::FLAG_NULL,
                'databaseFieldName' => 'date',
            ],

            'date-queued' => [
                'classMemberName' => 'dateQueuedAt',
                'databaseFieldName' => 'date',
                'flags' => self::FLAG_REQUIRED,
            ],

            'priority' => [
                'flags' => self::FLAG_STR | self::FLAG_SORTBY | self::FLAG_SORTASC | self::FLAG_REQUIRED,
            ],
        ];
    }

    public function email(): ?Email
    {
        return Email::loadFromId($this->emailId);
    }

    public static function loadFromEmailId(int $emailId): ?self
    {
        return self::fetch(
            Classmapper\FilterFactory::build('Basic', 'emailId', $emailId, \PDO::PARAM_INT)
        )->current();
    }

    public static function fetchByStatus($status): \Iterator
    {
        return self::fetch(
            Classmapper\FilterFactory::build('Basic', 'status', $status)
        );
    }

    public static function fetchByPriority(string $priority): \Iterator
    {
        return self::fetch(
            Classmapper\FilterFactory::build('Basic', 'priority', $priority)
        );
    }

    public static function fetchEmailsReadyToSend(): \Iterator
    {
        return (new self())
            ->appendFilter(Classmapper\FilterFactory::build('Basic', 'status', self::STATUS_QUEUED))
            ->appendFilter(Classmapper\FilterFactory::build(
                'Basic',
                'sendAttemptsRemaining',
                0,
                \PDO::PARAM_INT,
                Classmapper\Filters\Basic::COMPARISON_OPERATOR_GT,
                Classmapper\AbstractFilter::OPERATOR_AND
            ))
            ->appendFilter(Classmapper\FilterFactory::build(
                'Now',
                'nextSendAttemptDate',
                Classmapper\Filters\Basic::COMPARISON_OPERATOR_LTEQ,
                Classmapper\AbstractFilter::OPERATOR_AND
            ))
            ->filter()
        ;
    }

    public static function addToQueue(string $recipient, Template $template, array $fields = [], $sendDate = 'now', int $sendAttempts = 3, int $jsonEncodeFlags = 207, string $priority = self::PRIORITY_NORMAL): Email
    {
        //JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT

        // Delegate which allows other code to add data to the $fields array
        \Symphony::ExtensionManager()->notifyMembers(
            'PreEmailQueueAddEmailToQueue',
            '/emailqueue/',
            [
                'recipient' => $recipient,
                'template' => $template,
                'fields' => &$fields,
            ]
        );

        $email = (new Email())
            ->recipient($recipient)
            ->templateId($template->id)
            ->dateCreatedAt('now')
            ->data(json_encode($fields, $jsonEncodeFlags))
            ->save()
        ;

        (new self())
            ->emailId($email->id)
            ->nextSendAttemptDate($sendDate)
            ->sendAttemptsRemaining($sendAttempts)
            ->status(self::STATUS_QUEUED)
            ->dateQueuedAt('now')
            ->priority($priority)
            ->save()
        ;

        return $email;
    }

    public function requeue(): bool
    {
        $remainingAttempts = max(0, $this->sendAttemptsRemaining - 1);

        $canRequeue = (
            ($remainingAttempts > 0)
            && ($this->email() instanceof Email)
            && (false == $this->email()->hasBeenSent())
        );

        try {
            $this
                ->nextSendAttemptDate('+10min')
                ->sendAttemptsRemaining($remainingAttempts)
                ->status($canRequeue ? self::STATUS_QUEUED : self::STATUS_FAILED)
                ->save()
            ;
        } catch (\Exception $e) {
            $canRequeue = false;
        }

        return $canRequeue;
    }
}
