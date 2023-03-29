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

namespace App\Utils\OpenApi;

use App\Entity\Change;
use App\Entity\Comment;
use App\Entity\Event;
use App\Entity\FieldValue;
use App\Entity\File;
use App\Entity\Issue;
use App\Entity\State;
use App\Entity\Transition;
use App\Entity\User;
use App\Utils\OpenApiInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as API;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;

/**
 * @internal A dummy class for OpenAPI documentation
 */
abstract class IssueExtended extends Issue
{
    // Extra properties.
    public const PROPERTY_EVENTS       = 'events';
    public const PROPERTY_TRANSITIONS  = 'transitions';
    public const PROPERTY_STATES       = 'states';
    public const PROPERTY_ASSIGNEES    = 'assignees';
    public const PROPERTY_VALUES       = 'values';
    public const PROPERTY_CHANGES      = 'changes';
    public const PROPERTY_WATCHERS     = 'watchers';
    public const PROPERTY_COMMENTS     = 'comments';
    public const PROPERTY_FILES        = 'files';
    public const PROPERTY_DEPENDENCIES = 'dependencies';
    public const PROPERTY_RELATED      = 'related';

    // Available actions.
    public const ACTION_CLONE               = 'clone';
    public const ACTION_UPDATE              = 'update';
    public const ACTION_DELETE              = 'delete';
    public const ACTION_CHANGE_STATE        = 'changeState';
    public const ACTION_REASSIGN            = 'reassign';
    public const ACTION_SUSPEND             = 'suspend';
    public const ACTION_RESUME              = 'resume';
    public const ACTION_ADD_PUBLIC_COMMENT  = 'addPublicComment';
    public const ACTION_ADD_PRIVATE_COMMENT = 'addPrivateComment';
    public const ACTION_ATTACH_FILE         = 'attachFile';
    public const ACTION_DELETE_FILE         = 'deleteFile';
    public const ACTION_ADD_DEPENDENCY      = 'addDependency';
    public const ACTION_REMOVE_DEPENDENCY   = 'removeDependency';
    public const ACTION_ADD_RELATED         = 'addRelated';
    public const ACTION_REMOVE_RELATED      = 'removeRelated';

    /**
     * List of events (ordered).
     */
    #[Groups('info')]
    #[SerializedName(self::PROPERTY_EVENTS)]
    #[API\Property(type: OpenApiInterface::TYPE_ARRAY, items: new API\Items(ref: new Model(type: Event::class, groups: ['info'])))]
    abstract public function getAllEvents();

    /**
     * List of transitions (ordered).
     */
    #[Groups('info')]
    #[SerializedName(self::PROPERTY_TRANSITIONS)]
    #[API\Property(type: OpenApiInterface::TYPE_ARRAY, items: new API\Items(ref: new Model(type: Transition::class, groups: ['info'])))]
    abstract public function getAllTransitions();

    /**
     * List of states the issue can be moved to (ordered).
     */
    #[Groups('info')]
    #[SerializedName(self::PROPERTY_STATES)]
    #[API\Property(type: OpenApiInterface::TYPE_ARRAY, items: new API\Items(ref: new Model(type: State::class, groups: ['info'])))]
    abstract public function getAllStates();

    /**
     * List of possible assignees (ordered).
     */
    #[Groups('info')]
    #[SerializedName(self::PROPERTY_ASSIGNEES)]
    #[API\Property(type: OpenApiInterface::TYPE_ARRAY, items: new API\Items(ref: new Model(type: User::class, groups: ['info'])))]
    abstract public function getAllAssignees();

    /**
     * List of current values for all editable fields (ordered).
     */
    #[Groups('info')]
    #[SerializedName(self::PROPERTY_VALUES)]
    #[API\Property(type: OpenApiInterface::TYPE_ARRAY, items: new API\Items(ref: new Model(type: FieldValue::class, groups: ['info'])))]
    abstract public function getAllValues();

    /**
     * List of field changes (ordered).
     */
    #[Groups('info')]
    #[SerializedName(self::PROPERTY_CHANGES)]
    #[API\Property(type: OpenApiInterface::TYPE_ARRAY, items: new API\Items(ref: new Model(type: Change::class, groups: ['info'])))]
    abstract public function getAllChanges();

    /**
     * List of watchers (unordered).
     */
    #[Groups('info')]
    #[SerializedName(self::PROPERTY_WATCHERS)]
    #[API\Property(type: OpenApiInterface::TYPE_ARRAY, items: new API\Items(ref: new Model(type: User::class, groups: ['info'])))]
    abstract public function getAllWatchers();

    /**
     * List of comments (ordered).
     */
    #[Groups('info')]
    #[SerializedName(self::PROPERTY_COMMENTS)]
    #[API\Property(type: OpenApiInterface::TYPE_ARRAY, items: new API\Items(ref: new Model(type: Comment::class, groups: ['info'])))]
    abstract public function getAllComments();

    /**
     * List of files (ordered).
     */
    #[Groups('info')]
    #[SerializedName(self::PROPERTY_FILES)]
    #[API\Property(type: OpenApiInterface::TYPE_ARRAY, items: new API\Items(ref: new Model(type: File::class, groups: ['info'])))]
    abstract public function getAllFiles();

    /**
     * List of dependencies (ordered).
     */
    #[Groups('info')]
    #[SerializedName(self::PROPERTY_DEPENDENCIES)]
    #[API\Property(type: OpenApiInterface::TYPE_ARRAY, items: new API\Items(ref: new Model(type: Issue::class, groups: ['info'])))]
    abstract public function getAllDependencies();

    /**
     * List of related issues (ordered).
     */
    #[Groups('info')]
    #[SerializedName(self::PROPERTY_RELATED)]
    #[API\Property(type: OpenApiInterface::TYPE_ARRAY, items: new API\Items(ref: new Model(type: Issue::class, groups: ['info'])))]
    abstract public function getAllRelatedIssues();

    /**
     * List of actions currently available to the user.
     */
    #[Groups('info')]
    #[API\Property(type: OpenApiInterface::TYPE_OBJECT, properties: [
        new API\Property(property: self::ACTION_CLONE, type: OpenApiInterface::TYPE_BOOLEAN, description: 'Whether the issue can be cloned.'),
        new API\Property(property: self::ACTION_UPDATE, type: OpenApiInterface::TYPE_BOOLEAN, description: 'Whether the issue can be updated.'),
        new API\Property(property: self::ACTION_DELETE, type: OpenApiInterface::TYPE_BOOLEAN, description: 'Whether the issue can be deleted.'),
        new API\Property(property: self::ACTION_CHANGE_STATE, type: OpenApiInterface::TYPE_BOOLEAN, description: 'Whether the current state can be changed.'),
        new API\Property(property: self::ACTION_REASSIGN, type: OpenApiInterface::TYPE_BOOLEAN, description: 'Whether the issue can be reassigned.'),
        new API\Property(property: self::ACTION_SUSPEND, type: OpenApiInterface::TYPE_BOOLEAN, description: 'Whether the issue can be suspended.'),
        new API\Property(property: self::ACTION_RESUME, type: OpenApiInterface::TYPE_BOOLEAN, description: 'Whether the issue can be resumed.'),
        new API\Property(property: self::ACTION_ADD_PUBLIC_COMMENT, type: OpenApiInterface::TYPE_BOOLEAN, description: 'Whether a public comment can be added.'),
        new API\Property(property: self::ACTION_ADD_PRIVATE_COMMENT, type: OpenApiInterface::TYPE_BOOLEAN, description: 'Whether a private comment can be added.'),
        new API\Property(property: self::ACTION_ATTACH_FILE, type: OpenApiInterface::TYPE_BOOLEAN, description: 'Whether a file can be attached.'),
        new API\Property(property: self::ACTION_DELETE_FILE, type: OpenApiInterface::TYPE_BOOLEAN, description: 'Whether a file can be deleted.'),
        new API\Property(property: self::ACTION_ADD_DEPENDENCY, type: OpenApiInterface::TYPE_BOOLEAN, description: 'Whether a dependency issue can be added.'),
        new API\Property(property: self::ACTION_REMOVE_DEPENDENCY, type: OpenApiInterface::TYPE_BOOLEAN, description: 'Whether a dependency issue can be removed.'),
        new API\Property(property: self::ACTION_ADD_RELATED, type: OpenApiInterface::TYPE_BOOLEAN, description: 'Whether a related issue can be added.'),
        new API\Property(property: self::ACTION_REMOVE_RELATED, type: OpenApiInterface::TYPE_BOOLEAN, description: 'Whether a related issue can be removed.'),
    ])]
    abstract public function getActions();
}
