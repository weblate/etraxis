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

namespace App\Security\Voter;

use App\Entity\Enums\StateResponsibleEnum;
use App\Entity\State;
use App\Entity\Template;
use App\Entity\Transition;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Voter for "State" entities.
 */
class StateVoter extends Voter implements VoterInterface
{
    public const CREATE_STATE           = 'CREATE_STATE';
    public const UPDATE_STATE           = 'UPDATE_STATE';
    public const DELETE_STATE           = 'DELETE_STATE';
    public const SET_INITIAL_STATE      = 'SET_INITIAL_STATE';
    public const GET_STATE_TRANSITIONS  = 'GET_STATE_TRANSITIONS';
    public const SET_STATE_TRANSITIONS  = 'SET_STATE_TRANSITIONS';
    public const GET_RESPONSIBLE_GROUPS = 'GET_RESPONSIBLE_GROUPS';
    public const SET_RESPONSIBLE_GROUPS = 'SET_RESPONSIBLE_GROUPS';

    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(protected readonly EntityManagerInterface $manager)
    {
    }

    /**
     * @see Voter::supports
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        $attributes = [
            self::CREATE_STATE           => Template::class,
            self::UPDATE_STATE           => State::class,
            self::DELETE_STATE           => State::class,
            self::SET_INITIAL_STATE      => State::class,
            self::GET_STATE_TRANSITIONS  => State::class,
            self::SET_STATE_TRANSITIONS  => State::class,
            self::GET_RESPONSIBLE_GROUPS => State::class,
            self::SET_RESPONSIBLE_GROUPS => State::class,
        ];

        return array_key_exists($attribute, $attributes)
            && (null === $attributes[$attribute] || $subject instanceof $attributes[$attribute]);
    }

    /**
     * @see Voter::voteOnAttribute
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        // if the user is anonymous, do not grant access
        if (!$user instanceof User) {
            return false;
        }

        return match ($attribute) {
            self::CREATE_STATE           => $this->isCreateGranted($subject, $user),
            self::UPDATE_STATE           => $this->isUpdateGranted($subject, $user),
            self::DELETE_STATE           => $this->isDeleteGranted($subject, $user),
            self::SET_INITIAL_STATE      => $this->isSetInitialGranted($subject, $user),
            self::GET_STATE_TRANSITIONS  => $this->isGetTransitionsGranted($subject, $user),
            self::SET_STATE_TRANSITIONS  => $this->isSetTransitionsGranted($subject, $user),
            self::GET_RESPONSIBLE_GROUPS => $this->isGetResponsibleGroupsGranted($subject, $user),
            self::SET_RESPONSIBLE_GROUPS => $this->isSetResponsibleGroupsGranted($subject, $user),
            default                      => false,
        };
    }

    /**
     * Whether a new state can be created in the specified template.
     *
     * @param Template $subject Subject template
     * @param User     $user    Current user
     */
    protected function isCreateGranted(Template $subject, User $user): bool
    {
        return $user->isAdmin() && $subject->isLocked();
    }

    /**
     * Whether the specified state can be updated.
     *
     * @param State $subject Subject state
     * @param User  $user    Current user
     */
    protected function isUpdateGranted(State $subject, User $user): bool
    {
        return $user->isAdmin() && $subject->getTemplate()->isLocked();
    }

    /**
     * Whether the specified state can be deleted.
     *
     * @param State $subject Subject state
     * @param User  $user    Current user
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function isDeleteGranted(State $subject, User $user): bool
    {
        // User must be an admin and template must be locked.
        if (!$user->isAdmin() || !$subject->getTemplate()->isLocked()) {
            return false;
        }

        // Can't delete a state if it was used in at least one issue.
        $query = $this->manager->createQueryBuilder();

        $query
            ->select('COUNT(transition.id)')
            ->from(Transition::class, 'transition')
            ->where('transition.state = :state')
            ->setParameter('state', $subject->getId())
        ;

        $result = (int) $query->getQuery()->getSingleScalarResult();

        return 0 === $result;
    }

    /**
     * Whether the specified state can be set as initial one.
     *
     * @param State $subject Subject state
     * @param User  $user    Current user
     */
    protected function isSetInitialGranted(State $subject, User $user): bool
    {
        return $user->isAdmin() && $subject->getTemplate()->isLocked();
    }

    /**
     * Whether transitions of the specified state can be retrieved.
     *
     * @param State $subject Subject state
     * @param User  $user    Current user
     */
    protected function isGetTransitionsGranted(State $subject, User $user): bool
    {
        return $user->isAdmin() && !$subject->isFinal();
    }

    /**
     * Whether transitions of the specified state can be changed.
     *
     * @param State $subject Subject state
     * @param User  $user    Current user
     */
    protected function isSetTransitionsGranted(State $subject, User $user): bool
    {
        return $user->isAdmin() && !$subject->isFinal();
    }

    /**
     * Whether responsible groups of the specified state can be retrieved.
     *
     * @param State $subject Subject state
     * @param User  $user    Current user
     */
    protected function isGetResponsibleGroupsGranted(State $subject, User $user): bool
    {
        return $user->isAdmin() && StateResponsibleEnum::Assign === $subject->getResponsible();
    }

    /**
     * Whether responsible groups of the specified state can be changed.
     *
     * @param State $subject Subject state
     * @param User  $user    Current user
     */
    protected function isSetResponsibleGroupsGranted(State $subject, User $user): bool
    {
        return $user->isAdmin() && StateResponsibleEnum::Assign === $subject->getResponsible();
    }
}
