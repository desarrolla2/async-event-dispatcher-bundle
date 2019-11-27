<?php

/*
 * This file is part of the she crm package.
 *
 * Copyright (c) 2016-2019 Devtia Soluciones.
 * All rights reserved.
 *
 * @author Daniel González <daniel@devtia.com>
 */

namespace Desarrolla2\AsyncEventDispatcherBundle\Entity\Repository;

use Desarrolla2\AsyncEventDispatcherBundle\Entity\Message;
use Desarrolla2\AsyncEventDispatcherBundle\Entity\State;
use Doctrine\ORM\EntityRepository;

/**
 * MessageRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class MessageRepository extends EntityRepository
{
    /**
     * @param string   $name
     * @param array    $search
     * @param array    $states
     * @param int|null $limit
     * @return Message[]
     */
    public function findByEventNameSearchAndStates(string $name, array $search, array $states, int $limit = null): array
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $expr = $queryBuilder->expr();

        $queryBuilder
            ->select('message')
            ->from(Message::class, 'message')
            ->where('message.name = :name')
            ->andWhere('message.state IN(:states)')
            ->setParameter('states', $states)
            ->setParameter('name', $name)
            ->addOrderBy('message.createdAt', 'DESC');

        $count = 0;
        $andX = $expr->andX();
        foreach ($search as $field => $value) {
            if (is_array($value)){
                $value = json_encode($value);
            }

            $andX->add('message.data LIKE :data'.$count);
            $queryBuilder->setParameter('data'.$count, sprintf('%%"%s": %s%%', $field, $value));
            $count++;
        }
        $queryBuilder->andWhere($andX);

        if ($limit) {
            $queryBuilder->setMaxResults($limit);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function findInDataByNameFieldValueAndState(
        string $name,
        string $field,
        $value,
        string $state = State::PENDING,
        int $limit = null
    ): array {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder
            ->select('message')
            ->from(Message::class, 'message')
            ->where('message.name = :name')
            ->andWhere('message.data LIKE :data')
            ->andWhere('message.state = :state')
            ->setParameter('state', $state)
            ->setParameter('name', $name)
            ->setParameter('data', sprintf('%%"%s": %s%%', $field, $value))
            ->addOrderBy('message.createdAt', 'DESC');

        if ($limit) {
            $queryBuilder->setMaxResults($limit);
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
