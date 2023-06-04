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

namespace App;

use App\Doctrine\SortableNullsWalker;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\Query;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    /**
     * @see \Symfony\Component\HttpKernel\KernelInterface::boot
     */
    public function boot(): void
    {
        parent::boot();

        /** @var \Doctrine\ORM\EntityManagerInterface $manager */
        $manager = $this->container->get('doctrine.orm.entity_manager');

        if ($manager->getConnection()->getDatabasePlatform() instanceof PostgreSQLPlatform) {
            $manager->getConfiguration()->setDefaultQueryHint(Query::HINT_CUSTOM_OUTPUT_WALKER, SortableNullsWalker::class);
        }
    }
}
