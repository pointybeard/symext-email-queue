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

namespace pointybeard\Symphony\Extensions\Console\Commands\EmailQueue;

use pointybeard\Helpers\Cli\Colour\Colour;
use pointybeard\Helpers\Cli\Input;
use pointybeard\Helpers\Cli\Message\Message;
use pointybeard\Helpers\Foundation\BroadcastAndListen;
use pointybeard\Symphony\Extensions\Console;
use pointybeard\Symphony\Extensions\Console\Commands\Console\Symphony;
use pointybeard\Symphony\Extensions\EmailQueue\Models;

class ProcessQueue extends Console\AbstractCommand implements Console\Interfaces\AuthenticatedCommandInterface, BroadcastAndListen\Interfaces\AcceptsListenersInterface
{
    use Console\Traits\hasCommandRequiresAuthenticateTrait;
    use BroadcastAndListen\Traits\HasListenerTrait;
    use BroadcastAndListen\Traits\HasBroadcasterTrait;

    public function __construct()
    {
        parent::__construct();

        $this
            ->description('Process the email queue, sending emails that are ready.')
            ->version('1.0.0')
            ->example('symphony emailqueue processqueue')
        ;
    }

    public function init(): void
    {
        parent::init();

        $this
            ->addInputToCollection(
                Input\InputTypeFactory::build('LongOption')
                    ->name('dry')
                    ->flags(Input\AbstractInputType::FLAG_OPTIONAL)
                    ->description('will not actually send any emails')
                    ->default(false)
            )
            ->addInputToCollection(
                Input\InputTypeFactory::build('LongOption')
                    ->name('now')
                    ->flags(Input\AbstractInputType::FLAG_OPTIONAL)
                    ->description('skip the 5 second safety delay')
                    ->default(false)
            )
            ->addInputToCollection(
                Input\InputTypeFactory::build('LongOption')
                    ->name('limit')
                    ->flags(Input\AbstractInputType::FLAG_OPTIONAL | Input\AbstractInputType::FLAG_VALUE_REQUIRED)
                    ->description('limit the total number of emails to send')
                    ->validator(function (Input\AbstractInputType $input, Input\AbstractInputHandler $context) {
                        if (false == is_numeric($context->find('limit')) || (int) $context->find('limit') <= 0) {
                            throw new Console\Exceptions\ConsoleException('limit must be a positive integer');
                        }

                        return (int) $context->find('limit');
                    })
                    ->default(null)
            )
        ;
    }

    public function execute(Input\Interfaces\InputHandlerInterface $input): bool
    {
        $skipLiveModeWarning = $input->find('now');
        $isDryRun = $input->find('dry');
        $limit = $input->find('limit');

        if (true == $isDryRun) {
            $this->broadcast(
                Symphony::BROADCAST_MESSAGE,
                E_WARNING,
                (new Message('Dry run mode active. NO EMAILS WILL BE SENT...'))
                    ->foreground(Colour::FG_YELLOW)
                    ->flags(Message::FLAG_APPEND_NEWLINE)
            );
        } elseif (false == $skipLiveModeWarning) {
            $this->broadcast(
                Symphony::BROADCAST_MESSAGE,
                E_WARNING,
                (new Message('Running in live mode. EMAILS WILL BE SENT!! ... '))
                    ->foreground(Colour::FG_YELLOW)
                    ->flags(Message::FLAG_NONE)
            );

            $isDryRun = true;

            for ($ii = 5; $ii >= 1; --$ii) {
                sleep(1);
                $this->broadcast(
                    Symphony::BROADCAST_MESSAGE,
                    E_WARNING,
                    (new Message('.'))
                        ->foreground(Colour::FG_YELLOW)
                        ->flags(Message::FLAG_NONE)
                );
            }

            $this->broadcast(
                Symphony::BROADCAST_MESSAGE,
                E_WARNING,
                (new Message(''))
                    ->foreground(Colour::FG_YELLOW)
                    ->flags(Message::FLAG_APPEND_NEWLINE)
            );
        }

        if (null !== $limit && $total > $limit) {
            $this->broadcast(
                Symphony::BROADCAST_MESSAGE,
                E_WARNING,
                (new Message())
                    ->message("Limit has been set to {$limit}. Stopping once {$limit} emails have been processed.")
                    ->foreground(Colour::FG_YELLOW)
            );
        }

        $queue = Models\Queue::fetchEmailsReadyToSend();
        $total = 0;

        foreach ($queue as $q) {
            ++$total;

            if (null !== $limit && $total > $limit) {
                break;
            }

            $email = $q->email();

            if (false == ($email instanceof Models\Email) || true == $email->hasBeenSent()) {
                // This queue entry has no Email associated or the email has
                // already been sent. Delete it and move on
                $q->delete();
                ++$count['skipped'];

                continue;
            }

            try {
                if (false == $isDryRun) {
                    $email->send();

                    // Remove it from the queue
                    $q->delete();
                }
                ++$count['sent'];
            } catch (Exceptions\EmailAlreadySentException $ex) {
                $this->broadcast(
                    Symphony::BROADCAST_MESSAGE,
                    E_WARNING,
                    (new Message())
                        ->message("Email with ID {$email->id} has already been sent. Skipping.")
                        ->foreground(Colour::FG_YELLOW)
                );

                // Remove it from the queue
                $q->delete();

                ++$count['skipped'];

                continue;
            } catch (\Exception $ex) {
                $wasRequeued = false;

                if (true == ($email instanceof Models\Email)) {
                    $q = Models\Queue::loadFromEmailId($email->id);

                    if (true == ($q instanceof Models\Queue)) {
                        $wasRequeued = $q->requeue();
                    }
                }

                $this->broadcast(
                    Symphony::BROADCAST_MESSAGE,
                    E_ERROR,
                    (new Message())
                        ->message(sprintf(
                            'Email with ID %s failed to send. %s. Returned: %s',
                            $email->id,
                            $wasRequeued ? 'Queued again' : 'Unable to requeue',
                            $ex->getMessage()
                        ))
                        ->foreground(Colour::FG_RED)
                );

                ++$count['failed'];
            }
        }

        $this->broadcast(
            Symphony::BROADCAST_MESSAGE,
            E_NOTICE,
            (new Message())
                ->message(sprintf(
                    'Completed (%d total, %d sent, %d skipped, %d failed)',
                    $total,
                    $count['sent'],
                    $count['skipped'],
                    $count['failed']
                ))
                ->foreground(Colour::FG_GREEN)
        );

        return true;
    }
}
