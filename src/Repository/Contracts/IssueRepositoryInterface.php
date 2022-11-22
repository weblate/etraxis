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

namespace App\Repository\Contracts;

use App\Entity\Enums\FieldPermissionEnum;
use App\Entity\Issue;
use App\Entity\State;
use App\Entity\User;
use Doctrine\Common\Collections\Selectable;
use Doctrine\Persistence\ObjectRepository;

/**
 * Interface to the 'Issue' entities repository.
 */
interface IssueRepositoryInterface extends ObjectRepository, Selectable
{
    /**
     * @see \Doctrine\Persistence\ObjectManager::persist()
     */
    public function persist(Issue $entity, bool $flush = false): void;

    /**
     * @see \Doctrine\Persistence\ObjectManager::remove()
     */
    public function remove(Issue $entity, bool $flush = false): void;

    /**
     * @see \Doctrine\Persistence\ObjectManager::refresh()
     */
    public function refresh(Issue $entity): void;

    /**
     * Returns all field values of the specified issue, which the user (if specified) has access.
     *
     * @param Issue               $issue      Target issue
     * @param null|User           $user       User
     * @param FieldPermissionEnum $permission Required access (ignored when user is not specified)
     *
     * @return \App\Entity\FieldValue[]
     */
    public function getAllValues(Issue $issue, ?User $user, FieldPermissionEnum $permission = FieldPermissionEnum::ReadOnly): array;

    /**
     * Returns the latest field values of the specified issue, which the user (if specified) has access.
     *
     * @param Issue               $issue      Target issue
     * @param null|User           $user       User
     * @param FieldPermissionEnum $permission Required access (ignored when user is not specified)
     *
     * @return \App\Entity\FieldValue[]
     */
    public function getLatestValues(Issue $issue, ?User $user, FieldPermissionEnum $permission = FieldPermissionEnum::ReadOnly): array;

    /**
     * Whether the specified issue has any active dependency.
     */
    public function hasOpenedDependencies(Issue $issue): bool;

    /**
     * Returns list of all states which the issue can be moved to by specified user.
     *
     * @param Issue $issue Issue which current state is to be changed
     * @param User  $user  User who's changing current state of the issue
     *
     * @return State[]
     */
    public function getTransitionsByUser(Issue $issue, User $user): array;

    /**
     * Returns list of all possible assignees available in specified state.
     *
     * @param State $state State where the issue will be assigned
     *
     * @return User[]
     */
    public function getResponsiblesByState(State $state): array;

    /**
     * Reduces specified list of issues to those issues the user is allowed to see.
     *
     * @param User          $user   User who is trying to access the specified issues
     * @param int[]|Issue[] $issues Original list of issues
     *
     * @return Issue[]
     */
    public function reduceByUser(User $user, array $issues): array;

    /**
     * Assigns the issue to specified user.
     *
     * The function only updates specified entities,
     * it is caller's responsibility to persist the issue.
     *
     * @param User  $user        User who is assigning the issue
     * @param Issue $issue       Issue to be assigned
     * @param User  $responsible New responsible for the issue
     *
     * @return bool Whether the issue was successfully assigned
     */
    public function assignIssue(User $user, Issue $issue, User $responsible): bool;

    /**
     * Reassigns assigned issue to another user.
     *
     * The function only updates specified entities,
     * it is caller's responsibility to persist the issue.
     *
     * @param User  $user        User who is reassigning the issue
     * @param Issue $issue       Issue to be reassigned
     * @param User  $responsible New responsible for the issue
     *
     * @return bool Whether the issue was successfully reassigned
     */
    public function reassignIssue(User $user, Issue $issue, User $responsible): bool;
}
