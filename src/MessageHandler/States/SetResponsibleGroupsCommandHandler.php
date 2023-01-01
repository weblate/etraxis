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

namespace App\MessageHandler\States;

use App\Entity\Group;
use App\Entity\StateResponsibleGroup;
use App\Message\States\SetResponsibleGroupsCommand;
use App\MessageBus\Contracts\CommandHandlerInterface;
use App\Repository\Contracts\StateRepositoryInterface;
use App\Security\Voter\StateVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Command handler.
 */
final class SetResponsibleGroupsCommandHandler implements CommandHandlerInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        private readonly AuthorizationCheckerInterface $security,
        private readonly StateRepositoryInterface $repository,
        private readonly EntityManagerInterface $manager
    ) {
    }

    /**
     * Handles the given command.
     *
     * @throws AccessDeniedHttpException
     * @throws NotFoundHttpException
     */
    public function __invoke(SetResponsibleGroupsCommand $command): void
    {
        /** @var null|\App\Entity\State $state */
        $state = $this->repository->find($command->getState());

        if (!$state) {
            throw new NotFoundHttpException('Unknown state.');
        }

        if (!$this->security->isGranted(StateVoter::SET_RESPONSIBLE_GROUPS, $state)) {
            throw new AccessDeniedHttpException('You are not allowed to set responsible groups.');
        }

        $query = $this->manager->createQueryBuilder();

        $query
            ->select('grp')
            ->from(Group::class, 'grp')
            ->where($query->expr()->in('grp.id', ':groups'))
        ;

        $requestedGroups = $query->getQuery()->execute([
            'groups' => $command->getGroups(),
        ]);

        foreach ($state->getResponsibleGroups() as $responsibleGroup) {
            if (!in_array($responsibleGroup->getGroup(), $requestedGroups, true)) {
                $this->manager->remove($responsibleGroup);
            }
        }

        $existingGroups = array_map(
            fn (StateResponsibleGroup $responsibleGroup) => $responsibleGroup->getGroup(),
            $state->getResponsibleGroups()->toArray()
        );

        foreach ($requestedGroups as $group) {
            if (!in_array($group, $existingGroups, true)) {
                $responsibleGroup = new StateResponsibleGroup($state, $group);
                $this->manager->persist($responsibleGroup);
            }
        }
    }
}
