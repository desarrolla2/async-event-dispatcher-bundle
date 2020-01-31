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
use Desarrolla2\AsyncEventDispatcherBundle\Entity\State;
use Desarrolla2\AsyncEventDispatcherBundle\Event\Event;
use Desarrolla2\AsyncEventDispatcherBundle\Manager\MessageManager;

class AsyncEventDispatcher
{
    private $manager;

    public function __construct(MessageManager $manager)
    {
        $this->manager = $manager;
    }

    public function dispatch($eventName, Event $event = null): void
    {
        $data = [];
        if ($event) {
            $data = $event->getData();
        }

        $this->manager->create($eventName, $data);
    }

    public function dispatchUnlessThatExist($eventName, Event $event = null, array $search = []): void
    {
        if ($this->getMessageByNameDataAndState($eventName, $search, [State::PENDING, State::EXECUTING])) {
            return;
        }

        $this->dispatch($eventName, $event);
    }

    protected function getMessageByNameDataAndState($eventName, array $search, array $states): ?Message
    {
        $repository = $this->manager->getRepository(Message::class);
        $messages = $repository->findByEventNameSearchAndStates($eventName, $search, $states, 1);

        return count($messages) ? array_values($messages)[0] : null;
    }
}
