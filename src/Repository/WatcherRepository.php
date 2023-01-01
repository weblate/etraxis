<?php

//----------------------------------------------------------------------
//
//  Copyright (C) 2017-2023 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <https://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

namespace App\Repository;

use App\Entity\Issue;
use App\Entity\Watcher;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * 'Watcher' entities repository.
 */
class WatcherRepository extends ServiceEntityRepository implements Contracts\WatcherRepositoryInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Watcher::class);
    }

    /**
     * @codeCoverageIgnore Proxy method
     */
    public function persist(Watcher $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @codeCoverageIgnore Proxy method
     */
    public function remove(Watcher $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findAllByIssue(Issue $issue): array
    {
        $query = $this->createQueryBuilder('watcher')
            ->innerJoin('watcher.user', 'user')
            ->addSelect('user')
            ->where('watcher.issue = :issue')
            ->setParameter('issue', $issue)
        ;

        /** @var Watcher[] $watchers */
        $watchers = $query->getQuery()->getResult();

        return array_map(fn (Watcher $watcher) => $watcher->getUser(), $watchers);
    }
}
