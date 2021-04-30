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

namespace pointybeard\Symphony\Extensions\EmailQueue\Providers;

use pointybeard\Helpers\Cli\Colour;
use pointybeard\Helpers\Cli\Message;
use pointybeard\Symphony\Extensions\Console\Commands\Console;
use pointybeard\Symphony\Extensions\EmailQueue;
use pointybeard\Symphony\Extensions\Settings;
use Postmark\PostmarkClient;

final class Postmark extends EmailQueue\AbstractProvider
{
    public function send(Settings\SettingsResultIterator $credentials, EmailQueue\Models\Template $template, string $recipient, array $data = [], $attachments = [], string $replyTo = null, string $cc = null): void
    {
        try {
            $this->broadcast(
                Console\Symphony::BROADCAST_MESSAGE,
                E_NOTICE,
                (new Message\Message())
                    ->message('Creating Postmark client with apiKey provided...')
                    ->flags(null)
            );

            $client = new PostmarkClient($credentials->find('apikey'));

            $this->broadcast(
                Console\Symphony::BROADCAST_MESSAGE,
                E_NOTICE,
                (new Message\Message())
                    ->message('Done')
                    ->foreground(Colour\Colour::FG_GREEN)
                    ->flags(Message\Message::FLAG_APPEND_NEWLINE)
            );

            $this->broadcast(
                Console\Symphony::BROADCAST_MESSAGE,
                E_NOTICE,
                (new Message\Message())
                    ->message("Attempting to send email to recipient {$recipient} ...")
                    ->flags(Message\Message::FLAG_NONE)
            );

            $client->sendEmailWithTemplate(
                $credentials->find('from'),
                $recipient,
                $template->externalTemplateUid(),
                $data,
                true,
                null,
                true,
                $replyTo,
                $cc,
                null,
                null,
                $attachments
            );

            $this->broadcast(
                Console\Symphony::BROADCAST_MESSAGE,
                E_NOTICE,
                (new Message\Message())
                    ->message('Done')
                    ->foreground(Colour\Colour::FG_GREEN)
                    ->flags(Message\Message::FLAG_APPEND_NEWLINE)
            );
        } catch (\Exception $ex) {
            $this->broadcast(
                Console\Symphony::BROADCAST_MESSAGE,
                E_NOTICE,
                (new Message\Message())
                    ->message('Failed to send email! Returned - '.$ex->getMessage())
                    ->foreground(Colour\Colour::FG_RED)
                    ->flags(Message\Message::FLAG_APPEND_NEWLINE)
            );

            // Rethrow the exception so it can bubble up
            throw $ex;
        }
    }

    public function register(): void
    {
        parent::register();

        // Create the 'apikey' setting
        if (false == (Settings\Models\Setting::loadFromNameFilterByGroup('apikey', 'Postmark') instanceof Settings\Models\Setting)) {
            (new Settings\Models\Setting())
                ->name('apikey')
                ->group(['Credentials', 'Postmark'])
                ->dateCreatedAt('now')
                ->value('*** ENTER POSTMARK API KEY HERE ***')
                ->save()
            ;
        }

        // Create the 'from' address setting
        if (false == (Settings\Models\Setting::loadFromNameFilterByGroup('from', 'Postmark') instanceof Settings\Models\Setting)) {
            (new Settings\Models\Setting())
                ->name('from')
                ->group(['Postmark'])
                ->dateCreatedAt('now')
                ->value(sprintf('"%s" <%s>', \Symphony::Author()->getFullName(), \Symphony::Author()->get('email')))
                ->save()
            ;
        }
    }
}
