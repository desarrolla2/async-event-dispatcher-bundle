<?php

namespace Desarrolla2\AsyncEventDispatcherBundle\Manager;

use Desarrolla2\AsyncEventDispatcherBundle\Entity\Message;
use Desarrolla2\AsyncEventDispatcherBundle\Entity\State;
use Doctrine\ORM\EntityManager;

class MessageManager
{
    /** @var EntityManager */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function create(string $name, array $data = []): Message
    {
        $message = new Message();

        $message->setHash($this->hash = hash('sha256', uniqid(get_called_class(), true)));
        $message->setName($name);
        $message->setData($data);
        $message->setSize(strlen(json_encode($data)));
        $message->setState(State::PENDING);
        $message->setCreatedAt(new\DateTime());

        $this->em->persist($message);
        $this->em->flush();

        return $message;
    }

    public function isFinish(Message $message): bool
    {
        if ($message->getState() == State::FINISH) {
            return true;
        }

        return false;
    }

    public function isPaused(Message $message): bool
    {
        if ($message->getState() == State::PAUSED) {
            return true;
        }

        return false;
    }

    public function isReady(Message $message): bool
    {
        if ($message->getState() == State::PENDING) {
            return true;
        }

        return false;
    }

    public function remove(Message $message)
    {
        $this->em->remove($message);
        $this->em->flush();
    }

    public function update(Message $message, string $state)
    {
        $message->setState($state);
        $message->setUpdatedAt(new \DateTime());
        if ($state == State::EXECUTING) {
            $message->setStartedAt(new \DateTime());
        }
        if ($state === State::FINISH) {
            $message->setFinishAt(new \DateTime());
        }
        $this->em->flush();
    }
}