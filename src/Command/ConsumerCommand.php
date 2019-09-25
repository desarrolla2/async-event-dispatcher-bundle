<?php

/*
 * This file is part of the she crm package.
 *
 * Copyright (c) 2016-2019 Devtia Soluciones.
 * All rights reserved.
 *
 * @author Daniel González <daniel@devtia.com>
 */

namespace Desarrolla2\AsyncEventDispatcherBundle\Command;

use DateTime;
use Desarrolla2\AsyncEventDispatcherBundle\Entity\Message;
use Desarrolla2\AsyncEventDispatcherBundle\Entity\State;
use Desarrolla2\AsyncEventDispatcherBundle\Event\Event;
use Desarrolla2\Timer\Formatter\Human;
use Desarrolla2\Timer\Timer;
use Doctrine\ORM\EntityManager;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsumerCommand extends AbstractCommand
{
    /** @var OutputInterface */
    protected $output;

    /** @var LoggerInterface */
    protected $logger;

    /** @var EntityManager */
    protected $em;

    /** @var Timer */
    protected $timer;

    protected function configure()
    {
        $this->setName('async-event-consumer:consume');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        try {
            $this->executeSecure($input, $output);
        } catch (Exception $exception) {
            $this->notifyError($exception);
        }

        $this->finalize();
    }

    protected function executeSecure(InputInterface $input, OutputInterface $output)
    {
        $messages = $this->em->getRepository(Message::class)->findBy(
            [
                'state' => State::PENDING
            ],
            [
                'createdAt' => 'ASC'
            ],
            $this->getParameter('async_event_dispatcher.num_messages_per_execution')
        );

        foreach ($messages as $message) {
            $this->checkAndExecuteMessage($message);
        }
    }

    private function checkAndExecuteMessage(Message $message)
    {
        $this->changeMessageState($message, State::EXECUTING);

        $eventDispatcher = $this->get('event_dispatcher');
        $eventDispatcher->dispatch(
            $message->getName(),
            new Event($message->getData())
        );

        $this->changeMessageState($message, State::FINALIZED);
    }

    private function changeMessageState(Message $message, string $state)
    {
        $message->setState($state);
        $this->em->flush();
    }
}
