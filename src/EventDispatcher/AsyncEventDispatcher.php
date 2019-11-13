<?php

/*
 * This file is part of the she crm package.
 *
 * Copyright (c) 2016-2019 Devtia Soluciones.
 * All rights reserved.
 *
 * @author Daniel González <daniel@devtia.com>
 */

namespace Desarrolla2\AsyncEventDispatcherBundle\EventDispatcher;

use Desarrolla2\AsyncEventDispatcherBundle\Entity\Message;
use Desarrolla2\AsyncEventDispatcherBundle\Event\Event;
use Doctrine\ORM\EntityManager;

class AsyncEventDispatcher
{
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function dispatch($eventName, Event $event = null)
    {
        $message = new Message();
        $message->setName($eventName);
        if ($event) {
            $message->setData($event->getData());
        }

        $this->entityManager->persist($message);
        $this->entityManager->flush();
    }

    public function dispatchUnlessIssued($eventName, Event $event = null, array $states = [], array $search = [])
    {
        if ($this->getMessageByNameDataAndState($eventName, $search, $states)) {
            return;
        }

        $this->dispatch($eventName, $event);
    }

    public function getMessageByNameDataAndState($eventName, array $search, array $states): ?Message
    {
        $repository = $this->entityManager->getRepository(Message::class);
        $messages = $repository->findByEventNameSearchAndStates($eventName, $search, $states, 1);

        return count($messages) ? array_values($messages)[0] : null;
    }
}
