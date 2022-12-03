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

use App\Entity\File;
use App\Entity\Issue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * 'File' entities repository.
 */
class FileRepository extends ServiceEntityRepository implements Contracts\FileRepositoryInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     *
     * @param string $storage Path to files storage directory
     */
    public function __construct(ManagerRegistry $registry, protected string $storage)
    {
        parent::__construct($registry, File::class);

        $this->storage = realpath($this->storage) ?: $this->storage;
    }

    /**
     * @codeCoverageIgnore Proxy method
     */
    public function persist(File $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @codeCoverageIgnore Proxy method
     */
    public function remove(File $entity, bool $flush = false): void
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
        $query = $this->createQueryBuilder('file')
            ->innerJoin('file.event', 'event')
            ->addSelect('event')
            ->innerJoin('event.user', 'user')
            ->addSelect('user')
            ->where('event.issue = :issue')
            ->andWhere('file.removedAt IS NULL')
            ->orderBy('event.createdAt', 'ASC')
            ->setParameter('issue', $issue)
        ;

        return $query->getQuery()->getResult();
    }

    /**
     * {@inheritDoc}
     */
    public function findOneByUid(string $uid): ?File
    {
        return $this->findOneBy([
            'uid'       => $uid,
            'removedAt' => null,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getFullPath(File $file): string
    {
        return $this->storage.\DIRECTORY_SEPARATOR.$file->getUid();
    }
}
