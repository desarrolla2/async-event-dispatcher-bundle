<?php

/*
 * This file is part of the she crm package
 *
 * Copyright (c) 2016-2019 Aston Herencia && Devtia Soluciones
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author Daniel González <daniel@devtia.com>
 * @author Carlos Garbajosa <carlos@devtia.com>
 * @author Jaime Martínez <jaime@devtia.com>
 */

namespace Desarrolla2\AsyncEventDispatcherBundle\Twig;

use Desarrolla2\AsyncEventDispatcherBundle\Entity\Message;
use Desarrolla2\AsyncEventDispatcherBundle\Entity\State;
use Desarrolla2\AsyncEventDispatcherBundle\Manager\MessageManager;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Environment;
use Twig_Extension;
use Twig_SimpleFunction;

class DefaultExtension extends Twig_Extension
{
    protected $manager;
    protected $em;

    public function __construct(MessageManager $manager, EntityManagerInterface $entityManager)
    {
        $this->manager = $manager;
        $this->em = $entityManager;
    }

    public function canBePaused(Message $message): bool
    {
        if ($this->manager->isReady($message)) {
            return true;
        }

        return false;
    }

    public function canBePlayed(Message $message): bool
    {
        if ($this->manager->isPaused($message)) {
            return true;
        }

        return false;
    }

    public function canBeRemoved(Message $message): bool
    {
        return true;
    }

    public function countPending(): int
    {
        return $this->em->getRepository(Message::class)->count(
            ['state' => [State::PENDING, State::EXECUTING]]
        );
    }

    public function formatSize(int $size): string
    {
        if ($size < 1000) {
            return sprintf('%dB', $size);
        }
        if ($size < 1000 ^ 2) {
            return sprintf('%dKB', round($size / 1000));
        }

        return sprintf('%dMB', round($size / 1000 / 1000));
    }

    public function getExecutionTime(Message $message): string
    {
        if (!$message->getStartedAt()) {
            return '~';
        }
        $time = new \DateTime();
        if ($message->getFinishAt()) {
            $time = $message->getFinishAt();
        }
        $difference = $time->getTimestamp() - $message->getStartedAt()->getTimestamp();

        return $this->formatTime($difference);
    }

    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('async_event_message_json_encode', [$this, 'jsonEncode']),
            new \Twig_SimpleFilter('async_event_message_format_size', [$this, 'formatSize']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new Twig_SimpleFunction('async_event_message_get_time_to_start', [$this, 'getTimeToStart']),
            new Twig_SimpleFunction('async_event_message_get_execution_time', [$this, 'getExecutionTime']),
            new Twig_SimpleFunction('async_event_message_can_be_removed', [$this, 'canBeRemoved']),
            new Twig_SimpleFunction('async_event_message_can_be_played', [$this, 'canBePlayed']),
            new Twig_SimpleFunction('async_event_message_can_be_paused', [$this, 'canBePaused']),
            new Twig_SimpleFunction('async_event_message_is_failed', [$this, 'isFailed']),
            new Twig_SimpleFunction('async_event_message_is_paused', [$this, 'isPaused']),
            new Twig_SimpleFunction('async_event_message_is_executing', [$this, 'isExecuting']),
            new Twig_SimpleFunction('async_event_count_pending', [$this, 'countPending']),
            new Twig_SimpleFunction(
                'async_event_render_pending',
                [$this, 'renderPending'],
                [
                    'needs_environment' => true,
                    'is_safe' => ['html'],
                ]
            ),
            new Twig_SimpleFunction(
                'async_event_render_latest',
                [$this, 'renderLatest'],
                [
                    'needs_environment' => true,
                    'is_safe' => ['html'],
                ]
            ),
        ];
    }

    public function getTimeToStart(Message $message): string
    {
        if (!$message->getStartedAt()) {
            return '~';
        }
        $difference = $message->getStartedAt()->getTimestamp() - $message->getCreatedAt()->getTimestamp();

        return $this->formatTime($difference);
    }

    public function isExecuting(Message $message): bool
    {
        return $this->manager->isExecuting($message);
    }

    public function isFailed(Message $message): bool
    {
        return $this->manager->isFailed($message);
    }

    public function isPaused(Message $message): bool
    {
        return $this->manager->isPaused($message);
    }

    public function jsonEncode($value)
    {
        return json_encode($value, JSON_PRETTY_PRINT);
    }

    public function renderLatest(Environment $twig): string
    {
        return $twig->render('@AsyncEventDispatcher/table.html.twig', ['messages' => $this->getLatestMessages()]);
    }

    public function renderPending(Environment $twig): string
    {
        return $twig->render('@AsyncEventDispatcher/table.html.twig', ['messages' => $this->getPendingMessages()]);
    }

    private function formatTime(int $time): string
    {
        if ($time < 0) {
            return sprintf('-%s', $this->formatTime(-$time));
        }
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

    private function getLatestMessages(): array
    {
        return $this->em->getRepository(Message::class)->findBy(
            ['state' => [State::FINISH, State::FAILED]],
            ['updatedAt' => 'DESC'],
            10
        );
    }

    private function getPendingMessages(): array
    {
        return $this->em->getRepository(Message::class)->findBy(
            ['state' => [State::PENDING, State::PAUSED, State::EXECUTING]],
            ['priority' => 'DESC', 'startAfter' => 'ASC', 'createdAt' => 'ASC'],
            10
        );
    }
}
