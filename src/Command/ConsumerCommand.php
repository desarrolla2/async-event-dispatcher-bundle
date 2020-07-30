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

use Desarrolla2\AsyncEventDispatcherBundle\Entity\Message;
use Desarrolla2\AsyncEventDispatcherBundle\Entity\State;
use Desarrolla2\AsyncEventDispatcherBundle\Event\Event;
use Desarrolla2\AsyncEventDispatcherBundle\Model\Key;
use Desarrolla2\Timer\Timer;
use Doctrine\ORM\EntityManager;
use Exception;
use Psr\Log\LoggerInterface;
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

    private function executeMessage(Message $message, OutputInterface $output): void
    {
        $manager = $this->get('desarrolla2_async_event_dispatcher.manager.message_manager');
        if (!$manager->isReady($message)) {
            return;
        }
        $manager->update($message, State::EXECUTING);
        $output->writeln(
            sprintf(' - executing "%s" with "%s" data', $message->getName(), $this->formatSize($message->getSize()))
        );
        $eventDispatcher = $this->get('event_dispatcher');
        $eventDispatcher->dispatch(
            $message->getName(),
            new Event($message->getData())
        );

        $manager->update($message, State::FINISH);
    }

    private function executeSecure(InputInterface $input, OutputInterface $output)
    {
        $availableSlots = $this->getMaxExecutingSlots();
        $executingSlots = $this->getExecutingSlots();
        $messages = $this->getPendingMessages();
        $output->writeln(
            [
                sprintf(' - "%d" slots available.', $availableSlots,),
                sprintf(' - "%d" slots in execution.', $executingSlots,),
                '',
            ]
        );
        foreach ($messages as $message) {
            $messageSlots = $this->getMessageSlots($message);
            $output->writeln(
                [
                    sprintf(' - we will try to execute message "%s".', $message->getHash(),),
                    sprintf(' - need "%d" slots for current message.', $messageSlots,),
                ]
            );
            if (($executingSlots + $messageSlots) > $availableSlots) {
                $output->writeln(' - there aren\'t  enough slot available.');
                break;
            }

            $this->executeMessage($message, $output);
        }
        $this->markAsFailedNotFinalized($output);
    }

    private function formatSize(int $size): string
    {
        if ($size < 1000) {
            return sprintf(' % dB', $size);
        }
        if ($size < 1000 ^ 2) {
            return sprintf(' % dKB', round($size / 1000));
        }

        return sprintf(' % dMB', round($size / 1000 / 1000));
    }

    private function getExecutingSlots(): int
    {
        $totalSlots = 0;
        $repository = $this->getContainer()->get('doctrine.orm.entity_manager')->getRepository(Message::class);
        $messages = $repository->findBy(['state' => State::EXECUTING]);
        foreach ($messages as $message) {
            $messageSlots = $this->getMessageSlots($message);
            $totalSlots += (int) $messageSlots;
        }

        return $totalSlots;
    }

    private function getMaxExecutingSlots(): int
    {
        return $this->getContainer()->getParameter('async_event_dispatcher.maximum_num_of_consumers');
    }

    private function getMessageSlots($message): int
    {
        $messageSlots = 1;
        $data = $message->getData();
        if (array_key_exists(Key::NUMBER_OF_SLOTS, $data)) {
            $messageSlots = $data[Key::NUMBER_OF_SLOTS];
        }
        if ($messageSlots <= 1) {
            $messageSlots = 1;
        }

        return $messageSlots;
    }

    private function getPendingMessages()
    {
        $messages = $this->em->getRepository(Message::class)->findBy(
            [
                'state' => State::PENDING,
            ],
            [
                'createdAt' => 'ASC',
            ],
            $this->getParameter('async_event_dispatcher.num_messages_per_execution')
        );

        return $messages;
    }

    private function markAsFailedNotFinalized(OutputInterface $output): void
    {
        $manager = $this->get('desarrolla2_async_event_dispatcher.manager.message_manager');
        $messages = $this->em->getRepository(Message::class)->findBy(
            [
                'state' => State::EXECUTING,
            ],
            [
                'createdAt' => 'ASC',
            ]
        );
        $maxExecutionTime = 30 * 60;
        foreach ($messages as $message) {
            if (!$message->getStartedAt()) {
                continue;
            }
            $difference = (new \DateTime())->getTimestamp() - $message->getStartedAt()->getTimestamp();
            if ($difference < $maxExecutionTime) {
                continue;
            }
            $manager->update($message, State::FAILED);
        }
    }
}
