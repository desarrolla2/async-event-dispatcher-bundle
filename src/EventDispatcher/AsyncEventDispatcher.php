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

use Desarrolla2\AsyncEventDispatcherBundle\Entity\State;
use Desarrolla2\AsyncEventDispatcherBundle\Event\Event;
use Desarrolla2\AsyncEventDispatcherBundle\Manager\MessageManager;
use Desarrolla2\AsyncEventDispatcherBundle\Model\Key;

class AsyncEventDispatcher
{
    private $manager;

    public function __construct(MessageManager $manager)
    {
        $this->manager = $manager;
    }

    public function dispatch($eventName, Event $event = null, \DateTime $startAfter = null, int $priority = 0): void
    {
        $data = [];
        if ($event) {
            $data = $event->getData();
        }
        $this->manager->create($eventName, array_merge($this->getDefaultData(), $data), $startAfter, $priority);
    }

    public function dispatchUnlessThatExist(
        string $eventName,
        Event $event = null,
        array $search = [],
        array $states = [State::PENDING]
    ): void {
        $lastMessage = $this->manager->getLastMessageByEventNameSearchAndStates($eventName, $search, $states);
        if ($lastMessage) {
            return;
        }

        $this->dispatch($eventName, $event);
    }

    private function getDefaultData(): array
    {
        return [Key::NUMBER_OF_SLOTS => 1, Key::USER_ID => null];
    }
}
