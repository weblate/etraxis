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

namespace App\MessageHandler\UserSettings;

use App\Entity\Enums\SystemRoleEnum;
use App\Entity\Enums\TemplatePermissionEnum;
use App\Message\UserSettings\GetTemplatesQuery;
use App\MessageBus\Contracts\QueryHandlerInterface;
use App\Repository\Contracts\TemplateRepositoryInterface;
use App\Repository\Contracts\UserRepositoryInterface;
use App\Repository\TemplateRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Query handler.
 */
final class GetTemplatesQueryHandler implements QueryHandlerInterface
{
    private TemplateRepository $templateRepository;
    private UserRepository     $userRepository;

    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(TemplateRepositoryInterface $templateRepository, UserRepositoryInterface $userRepository)
    {
        $this->templateRepository = $templateRepository;
        $this->userRepository     = $userRepository;
    }

    /**
     * Query handler.
     *
     * @return \App\Entity\Template[]
     *
     * @throws NotFoundHttpException
     */
    public function __invoke(GetTemplatesQuery $query): array
    {
        /** @var null|\App\Entity\User $user */
        $user = $this->userRepository->find($query->getUser());

        if (!$user) {
            throw new NotFoundHttpException('Unknown user.');
        }

        $dql = $this->templateRepository->createQueryBuilder('template');

        $dql
            ->distinct()
            ->innerJoin('template.project', 'project', Join::WITH, 'project.suspended = :suspended')
            ->addSelect('project')
            ->leftJoin('template.rolePermissions', 'trp', Join::WITH, 'trp.permission = :permission')
            ->leftJoin('template.groupPermissions', 'tgp', Join::WITH, 'tgp.permission = :permission')
            ->where('template.locked = :locked')
            ->andWhere($dql->expr()->orX(
                'trp.role = :role',
                $dql->expr()->in('tgp.group', ':groups')
            ))
            ->orderBy('project.name')
            ->addOrderBy('template.name')
            ->setParameters([
                'suspended'  => false,
                'locked'     => false,
                'permission' => TemplatePermissionEnum::CreateIssues,
                'role'       => SystemRoleEnum::Anyone,
                'groups'     => $user->getGroups(),
            ])
        ;

        return $dql->getQuery()->getResult();
    }
}
