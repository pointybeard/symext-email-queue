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

namespace pointybeard\Symphony\Extensions\EmailQueue\Providers;

use pointybeard\Helpers\Cli\Colour;
use pointybeard\Helpers\Cli\Message;
use pointybeard\Symphony\Extensions\Console\Commands\Console;
use pointybeard\Symphony\Extensions\EmailQueue;
use pointybeard\Symphony\Extensions\Settings;
use MailchimpMarketing\ApiClient;

final class Mailchimp extends EmailQueue\AbstractProvider
{
    public function send(Settings\SettingsResultIterator $credentials, EmailQueue\Models\Template $template, string $recipient, array $data = [], $attachments = [], string $replyTo = null, string $cc = null): void
    {
        try {
            $this->broadcast(
                Console\Symphony::BROADCAST_MESSAGE,
                E_NOTICE,
                (new Message\Message())
                    ->message('Creating Mailchimp client with api Key and server prefix provided...')
                    ->flags(null)
            );

            //$client = new PostmarkClient($credentials->find('apikey'));
            $client = new MailchimpTransactional\ApiClient();
            $mailchimp->setApiKey($credentials->find('apikey'));

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

            // $client->sendEmailWithTemplate(
            //     $credentials->find('from'),
            //     $recipient,
            //     $template->externalTemplateUid(),
            //     $data,
            //     true,
            //     null,
            //     true,
            //     $replyTo,
            //     $cc,
            //     null,
            //     null,
            //     $attachments
            // );


            $message = [
                "from_email" => $credentials->find('from'),
                "subject" => "Hello world",
                "text" => "Welcome to Mailchimp Transactional!",
                "to" => [
                    [
                        "email" => $recipient,
                        "type" => "to"
                    ]
                ]
            ];

            // $response = $mailchimp->users->ping();
            $response = $mailchimp->messages->send(["message" => $message]);

            $this->broadcast(
                Console\Symphony::BROADCAST_MESSAGE,
                E_NOTICE,
                (new Message\Message())
                    ->message('Done')
                    ->foreground(Colour\Colour::FG_GREEN)
                    ->flags(Message\Message::FLAG_APPEND_NEWLINE)
            );
        } catch (\Error | \Exception $ex) {
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
        if (false == (Settings\Models\Setting::loadFromNameFilterByGroup('apikey', 'Mailchimp') instanceof Settings\Models\Setting)) {
            (new Settings\Models\Setting())
                ->name('apikey')
                ->group(['Credentials', 'Mailchimp'])
                ->dateCreatedAt('now')
                ->value('*** ENTER MAILCHIMP API KEY HERE ***')
                ->save()
            ;
        }
        
        // Create the 'server' setting
        if (false == (Settings\Models\Setting::loadFromNameFilterByGroup('server', 'Mailchimp') instanceof Settings\Models\Setting)) {
            (new Settings\Models\Setting())
                ->name('server')
                ->group(['Credentials', 'Mailchimp'])
                ->dateCreatedAt('now')
                ->value('*** ENDER YOUR MAILCHIMP SERVER PREFIX HERE ***')
                ->save()
            ;
        }

        // Create the 'from' address setting
        if (false == (Settings\Models\Setting::loadFromNameFilterByGroup('from', 'Mailchimp') instanceof Settings\Models\Setting)) {
            (new Settings\Models\Setting())
                ->name('from')
                ->group(['Mailchimp'])
                ->dateCreatedAt('now')
                ->value(sprintf('"%s" <%s>', \Symphony::Author()->getFullName(), \Symphony::Author()->get('email')))
                ->save()
            ;
        }
    }
}
