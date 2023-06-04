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

use App\Entity\Dependency;
use App\Entity\Issue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * 'Dependency' entities repository.
 */
class DependencyRepository extends ServiceEntityRepository implements Contracts\DependencyRepositoryInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Dependency::class);
    }

    /**
     * @codeCoverageIgnore Proxy method
     */
    public function persist(Dependency $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @codeCoverageIgnore Proxy method
     */
    public function remove(Dependency $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @see Contracts\DependencyRepositoryInterface::findAllByIssue
     */
    public function findAllByIssue(Issue $issue): array
    {
        $query = $this->createQueryBuilder('dependency')
            ->select('dependency')

            ->innerJoin('dependency.event', 'event')
            ->addSelect('event')

            ->innerJoin('dependency.issue', 'issue')
            ->addSelect('issue')

            ->innerJoin('issue.state', 'state')
            ->addSelect('state')

            ->innerJoin('state.template', 'template')
            ->addSelect('template')

            ->innerJoin('template.project', 'project')
            ->addSelect('project')

            ->innerJoin('issue.author', 'author')
            ->addSelect('author')

            ->leftJoin('issue.responsible', 'responsible')
            ->addSelect('responsible')

            ->where('event.issue = :issue')
            ->addOrderBy('event.createdAt')
            ->setParameter('issue', $issue)
        ;

        /** @var Dependency[] $dependencies */
        $dependencies = $query->getQuery()->getResult();

        return array_map(fn (Dependency $dependency) => $dependency->getIssue(), $dependencies);
    }
}
