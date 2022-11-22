<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2017-2022 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <https://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace App\Repository;

use App\Entity\Enums\EventTypeEnum;
use App\Entity\Event;
use App\Entity\Issue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * 'Event' entities repository.
 */
class EventRepository extends ServiceEntityRepository implements Contracts\EventRepositoryInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * @codeCoverageIgnore Proxy method
     */
    public function persist(Event $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @codeCoverageIgnore Proxy method
     */
    public function remove(Event $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findAllByIssue(Issue $issue, bool $hidePrivateComments): array
    {
        $query = $this->createQueryBuilder('event')
            ->innerJoin('event.user', 'user')
            ->addSelect('user')
            ->where('event.issue = :issue')
            ->orderBy('event.createdAt', 'ASC')
            ->addOrderBy('event.id', 'ASC')
            ->setParameter('issue', $issue)
        ;

        if ($hidePrivateComments) {
            $query->andWhere('event.type != :private');
            $query->setParameter('private', EventTypeEnum::PrivateComment);
        }

        return $query->getQuery()->getResult();
    }
}
