<?php

/*
 * This file is part of the she crm package.
 *
 * Copyright (c) 2016-2019 Devtia Soluciones.
 * All rights reserved.
 *
 * @author Daniel González <daniel@devtia.com>
 */

namespace Desarrolla2\AsyncEventDispatcherBundle\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="async_event_dispatcher_message")
 * @ORM\Entity(repositoryClass="Desarrolla2\AsyncEventDispatcherBundle\Entity\Repository\MessageRepository")
 */
class Message
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string")
     */
    private $hash;

    /**
     * @ORM\Column(type="string")
     */
    private $name;

    /**
     * @ORM\Column(type="json_array")
     */
    private $data;

    /**
     * @ORM\Column(type="string")
     */
    private $state;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $startedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $finishedAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * Message constructor.
     */
    public function __construct()
    {
        $this->hash = hash('sha256', uniqid(get_called_class(), true));
        $this->createdAt = new DateTime();
        $this->state = State::PENDING;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getExecutionTime()
    {
        if ($this->state == State::PENDING) {
            return false;
        }

        $timeToUse = $this->updatedAt->getTimestamp();
        if ($this->state == State::EXECUTING) {
            $timeToUse = (new \DateTime())->getTimestamp();
        }

        return $this->time($timeToUse - $this->startedAt->getTimestamp());
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStartedAt(): ?DateTime
    {
        return $this->startedAt;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getTimeFromCreateToFinalized()
    {
        if ($this->state != State::FINALIZED) {
            return false;
        }

        $update = $this->updatedAt->getTimestamp();
        $create = $this->createdAt->getTimestamp();

        return $this->time($update - $create);
    }

    public function getTimeFromCreateToNow()
    {
        $now = (new \DateTime())->getTimestamp();
        $create = $this->createdAt->getTimestamp();

        return $this->time($now - $create);
    }

    public function getTimeFromCreateToStart()
    {
        if ($this->state == State::PENDING) {
            return false;
        }

        $start = $this->startedAt->getTimestamp();
        $create = $this->createdAt->getTimestamp();

        return $this->time($start - $create);
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public function setCreatedAt($createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function setHash($hash): void
    {
        $this->hash = (string) $hash;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setStartedAt($startedAt): void
    {
        $this->startedAt = $startedAt;
    }

    public function setState($state): void
    {
        $this->state = $state;
        $this->updatedAt = new \DateTime();

        if ($state == State::EXECUTING) {
            $this->startedAt = new \DateTime();
        }
    }

    public function setUpdatedAt($updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    private function time($time)
    {
        if ($time < 90) {
            return round($time, 2).'s';
        }

        if ($time < 3600) {
            $seconds = $time % 60;
            $minutes = ($time - $seconds) / 60;

            return sprintf('%dm%ss', $minutes, round($seconds, 2));
        }

        $seconds = $time % 3600;
        $hours = ($time - $seconds) / 3600;

        $time = $time - $hours * 3600;
        $seconds = $time % 60;
        $minutes = ($time - $seconds) / 60;

        return sprintf('%dh %dm %ss', $hours, $minutes, round($seconds, 2));
    }
}
