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

use App\Entity\TextValue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * 'TextValue' entities repository.
 */
class TextValueRepository extends ServiceEntityRepository implements Contracts\TextValueRepositoryInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TextValue::class);
    }

    /**
     * @codeCoverageIgnore Proxy method
     */
    public function persist(TextValue $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @codeCoverageIgnore Proxy method
     */
    public function remove(TextValue $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $value): TextValue
    {
        $entity = $this->findOneBy([
            'hash' => md5($value),
        ]);

        // If value doesn't exist yet, create it.
        if (null === $entity) {
            $entity = new TextValue($value);

            $this->getEntityManager()->persist($entity);
            $this->getEntityManager()->flush();
        }

        return $entity;
    }
}
