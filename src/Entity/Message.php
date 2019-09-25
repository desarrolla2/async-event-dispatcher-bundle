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
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $startedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    /**
     * Message constructor.
     */
    public function __construct()
    {
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

    public function getId()
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getStartedAt(): DateTime
    {
        return $this->startedAt;
    }

    public function getUpdatedAt(): DateTime
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

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setState($state): void
    {
        $this->state = $state;
        $this->updatedAt = new \DateTime();

        if ($state == State::EXECUTING) {
            $this->startedAt = new \DateTime();
        }
    }

    public function setStartedAt($startedAt): void
    {
        $this->startedAt = $startedAt;
    }

    public function setUpdatedAt($updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getExecutionTime()
    {
        if ($this->state != State::FINALIZED) {
            return false;
        }
        
        return $this->updatedAt - $this->startedAt;
    }

    public function getFromCreateToStartTime()
    {
        if ($this->state == State::PENDING) {
            return false;
        }
        
        return $this->startedAt - $this->createdAt;
    }

    public function getFromCreateToFinalizedTime()
    {
        if ($this->state != State::FINALIZED) {
            return false;
        }
        
        return $this->updatedAt - $this->createdAt;
    }
}
