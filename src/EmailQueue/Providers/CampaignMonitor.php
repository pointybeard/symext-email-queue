<?php

declare(strict_types=1);

/*
 * This file is part of the "Email Queue Extension for Symphony CMS" repository.
 *
 * Copyright 2021 Alannah Kearney <hi@alannahkearney.com>
 *
 * For the full copyright and license information, please view the LICENCE
 * file that was distributed with this source code.
 */

namespace pointybeard\Symphony\Extensions\EmailQueue\Providers;

use pointybeard\Helpers\Cli\Colour;
use pointybeard\Helpers\Cli\Message;
use pointybeard\Symphony\Extensions\Console\Commands\Console;
use pointybeard\Symphony\Extensions\EmailQueue;
use pointybeard\Symphony\Extensions\EmailQueue\Models;
use pointybeard\Symphony\Extensions\Settings;

final class CampaignMonitor extends EmailQueue\AbstractProvider
{
    public function send(Settings\SettingsResultIterator $credentials, EmailQueue\Models\Template $template, string $recipient, array $data = [], $attachments = [], string $replyTo = null, string $cc = null): void
    {
        try {
            $this->broadcast(
                Console\Symphony::BROADCAST_MESSAGE,
                E_NOTICE,
                (new Message\Message())
                    ->message('Creating Campaign Monitor client with apiKey provided...')
                    ->flags(null)
            );

            //$client = new PostmarkClient($credentials->find('apikey'));
            $client = new \CS_REST_Transactional_ClassicEmail(
                ["api_key" => $credentials->find('apikey')],
                $credentials->find('clientid')
            );

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

            // https://www.campaignmonitor.com/api/transactional/#send-classic-email
            // https://github.com/campaignmonitor/createsend-php/blob/12a4c1f33b940775bde80ae1494036df06ed36d4/csrest_transactional_classicemail.php#L108
            $result = $client->send(
                [
                    'From' => $credentials->find('from'),
                    'ReplyTo' => $credentials->find('replyTo'),
                    'To' => [$recipient],
                    'CC' => $data['cc'],
                    'BCC' => $data['bcc'],
                    'Subject' => $data['subject'],
                    'Html' => $data['message'],
                    'Attachments' => $data['attachments'],
                ],
                null, // Group
                "Yes", // ConsentToTrack
                null, // AddRecipientsToListID
                [
                    "TrackOpens" => true,
                    "TrackClicks" => true,
                    "InlineCSS" => true,
                    "AddRecipientsToListID" => null
                ]
            );

            if(false == $result->was_successful()) {
                throw new \Exception($result->response->Code . ": " . $result->response->Message);
            }

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
        if (false == (Settings\Models\Setting::loadFromNameFilterByGroup('apikey', 'CampaignMonitor') instanceof Settings\Models\Setting)) {
            (new Settings\Models\Setting())
                ->name('apikey')
                ->group(['Credentials', 'CampaignMonitor'])
                ->dateCreatedAt('now')
                ->value('*** ENTER CAMPAIGN MONITOR API KEY HERE ***')
                ->save()
            ;
        }

        // Create the 'clientid' setting
        if (false == (Settings\Models\Setting::loadFromNameFilterByGroup('clientid', 'CampaignMonitor') instanceof Settings\Models\Setting)) {
            (new Settings\Models\Setting())
                ->name('clientid')
                ->group(['Credentials', 'CampaignMonitor'])
                ->dateCreatedAt('now')
                ->value('*** ENTER CAMPAIGN MONITOR CLIENT ID KEY HERE ***')
                ->save()
            ;
        }

        // Create the 'from' address setting
        if (false == (Settings\Models\Setting::loadFromNameFilterByGroup('from', 'CampaignMonitor') instanceof Settings\Models\Setting)) {
            (new Settings\Models\Setting())
                ->name('from')
                ->group(['CampaignMonitor'])
                ->dateCreatedAt('now')
                ->value(sprintf('"%s" <%s>', \Symphony::Author()->getFullName(), \Symphony::Author()->get('email')))
                ->save()
            ;
        }

        // Create the 'replyTo' address setting
        if (false == (Settings\Models\Setting::loadFromNameFilterByGroup('replyTo', 'CampaignMonitor') instanceof Settings\Models\Setting)) {
            (new Settings\Models\Setting())
                ->name('replyTo')
                ->group(['CampaignMonitor'])
                ->dateCreatedAt('now')
                ->value(sprintf('"%s" <%s>', \Symphony::Author()->getFullName(), \Symphony::Author()->get('email')))
                ->save()
            ;
        }

        if(false == (Models\Template::loadFromName("Campaign Monitor Default") instanceof Settings\Models\Template)) {
            (new Models\Template)
                ->name("Campaign Monitor Default")
                ->providerId((int)Models\Provider::loadFromClassname("\\pointybeard\\Symphony\\Extensions\\EmailQueue\\Providers\\CampaignMonitor")->id())
                ->save()
            ;
        }
    }
}
