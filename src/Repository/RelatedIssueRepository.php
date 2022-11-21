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

use App\Entity\Issue;
use App\Entity\RelatedIssue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * 'RelatedIssue' entities repository.
 */
class RelatedIssueRepository extends ServiceEntityRepository implements Contracts\RelatedIssueRepositoryInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RelatedIssue::class);
    }

    /**
     * @codeCoverageIgnore Proxy method
     */
    public function persist(RelatedIssue $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @codeCoverageIgnore Proxy method
     */
    public function remove(RelatedIssue $entity, bool $flush = false): void
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
        $query = $this->createQueryBuilder('relatedIssue')
            ->select('relatedIssue')

            ->innerJoin('relatedIssue.event', 'event')
            ->addSelect('event')

            ->innerJoin('relatedIssue.issue', 'issue')
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

        /** @var RelatedIssue[] $relatedIssues */
        $relatedIssues = $query->getQuery()->getResult();

        return array_map(fn (RelatedIssue $relatedIssue) => $relatedIssue->getIssue(), $relatedIssues);
    }
}
