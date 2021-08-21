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
     * @ORM\Column(type="string", unique=true)
     */
    private $hash = '';

    /**
     * @ORM\Column(type="string")
     */
    private $name = '';

    /**
     * @ORM\Column(type="json_array")
     */
    private $data = [];

    /**
     * @ORM\Column(type="string")
     */
    private $state = '';

    /**
     * @ORM\Column(type="integer")
     */
    private $priority = 0;

    /**
     * @ORM\Column(type="integer")
     */
    private $size = 0;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $startAfter;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $startedAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $finishAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $updatedAt;

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getFinishAt(): ?\DateTime
    {
        return $this->finishAt;
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

    public function getSize(): int
    {
        return $this->size;
    }

    public function getStartAfter(): ?\DateTime
    {
        return $this->startAfter;
    }

    public function getStartedAt(): ?DateTime
    {
        return $this->startedAt;
    }

    public function getState(): string
    {
        return $this->state;
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
        ksort($data);
        $this->data = $data;
    }

    public function setFinishAt(\DateTime $finishAt): void
    {
        $this->finishAt = $finishAt;
    }

    public function setHash($hash): void
    {
        $this->hash = (string)$hash;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setSize(int $size): void
    {
        $this->size = $size;
    }

    public function setStartAfter(\DateTime $startAfter = null): void
    {
        $this->startAfter = $startAfter;
    }

    public function setStartedAt($startedAt): void
    {
        $this->startedAt = $startedAt;
    }

    public function setState($state): void
    {
        $this->state = $state;
    }

    public function setUpdatedAt($updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
