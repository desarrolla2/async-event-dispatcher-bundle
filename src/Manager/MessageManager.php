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

    public function create(string $name, array $data = [], \DateTime $startAfter = null): Message
    {
        $message = new Message();

        $message->setHash($this->hash = hash('sha256', uniqid(get_called_class(), true)));
        $message->setName($name);
        $message->setData($data);
        $message->setStartAfter($startAfter);
        $message->setSize(strlen(json_encode($data)));
        $message->setState(State::PENDING);
        $message->setCreatedAt(new\DateTime());

        $this->em->persist($message);
        $this->em->flush();

        return $message;
    }

    public function getLastMessageByEventNameSearchAndStates(string $eventName, array $search, array $states): ?Message
    {
        $repository = $this->em->getRepository(Message::class);
        $messages = $repository->findByEventNameSearchAndStates($eventName, $search, $states, 1);

        return count($messages) ? array_values($messages)[0] : null;
    }

    public function isExecuting(Message $message): bool
    {
        return State::EXECUTING === $message->getState();
    }

    public function isFailed(Message $message): bool
    {
        return State::FAILED === $message->getState();
    }

    public function isFinish(Message $message): bool
    {
        return State::FINISH === $message->getState();
    }

    public function isPaused(Message $message): bool
    {
        return State::PAUSED === $message->getState();
    }

    public function isReady(Message $message): bool
    {
        return State::PENDING === $message->getState();
    }

    public function remove(Message $message): void
    {
        $this->em->remove($message);
        $this->em->flush();
    }

    public function update(Message $message, string $state): void
    {
        $message->setState($state);
        $message->setUpdatedAt(new \DateTime());
        if ($state == State::EXECUTING) {
            $message->setStartedAt(new \DateTime());
        }
        if ($state === State::FINISH) {
            $message->setFinishAt(new \DateTime());
        }
        if ($state === State::FAILED) {
            $message->setFinishAt(new \DateTime());
        }
        $this->em->flush();
    }
}
