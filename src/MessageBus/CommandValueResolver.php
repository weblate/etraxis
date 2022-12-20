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

namespace App\MessageBus;

use App\Entity\Enums\SystemRoleEnum;
use App\Message\Comments;
use App\Message\Dependencies;
use App\Message\Fields;
use App\Message\Files;
use App\Message\Groups;
use App\Message\Issues;
use App\Message\ListItems;
use App\Message\Projects;
use App\Message\RelatedIssues;
use App\Message\States;
use App\Message\Templates;
use App\Message\Users;
use App\Message\UserSettings;
use App\MessageBus\Contracts\CommandInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * Converts request into command.
 */
class CommandValueResolver implements ValueResolverInterface
{
    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(protected readonly SerializerInterface $serializer)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (is_subclass_of($argument->getType(), CommandInterface::class)) {
            yield $this->serializer->deserialize($request->getContent() ?: '{}', $argument->getType(), 'json', [
                AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS => [
                    // Users API
                    Users\UpdateUserCommand::class  => ['user' => $request->get('id')],
                    Users\DeleteUserCommand::class  => ['user' => $request->get('id')],
                    Users\DisableUserCommand::class => ['user' => $request->get('id')],
                    Users\EnableUserCommand::class  => ['user' => $request->get('id')],
                    // Groups API
                    Groups\UpdateGroupCommand::class => ['group' => $request->get('id')],
                    Groups\DeleteGroupCommand::class => ['group' => $request->get('id')],
                    // Projects API
                    Projects\UpdateProjectCommand::class  => ['project' => $request->get('id')],
                    Projects\DeleteProjectCommand::class  => ['project' => $request->get('id')],
                    Projects\SuspendProjectCommand::class => ['project' => $request->get('id')],
                    Projects\ResumeProjectCommand::class  => ['project' => $request->get('id')],
                    // Templates API
                    Templates\CloneTemplateCommand::class       => ['template' => $request->get('id')],
                    Templates\UpdateTemplateCommand::class      => ['template' => $request->get('id')],
                    Templates\DeleteTemplateCommand::class      => ['template' => $request->get('id')],
                    Templates\LockTemplateCommand::class        => ['template' => $request->get('id')],
                    Templates\UnlockTemplateCommand::class      => ['template' => $request->get('id')],
                    Templates\SetRolesPermissionCommand::class  => ['template' => $request->get('id')],
                    Templates\SetGroupsPermissionCommand::class => ['template' => $request->get('id')],
                    // States API
                    States\UpdateStateCommand::class          => ['state'     => $request->get('id')],
                    States\DeleteStateCommand::class          => ['state'     => $request->get('id')],
                    States\SetInitialStateCommand::class      => ['state'     => $request->get('id')],
                    States\SetResponsibleGroupsCommand::class => ['state'     => $request->get('id')],
                    States\SetRolesTransitionCommand::class   => ['fromState' => $request->get('id')],
                    States\SetGroupsTransitionCommand::class  => ['fromState' => $request->get('id')],
                    // Fields API
                    Fields\UpdateFieldCommand::class         => ['field' => $request->get('id')],
                    Fields\DeleteFieldCommand::class         => ['field' => $request->get('id')],
                    Fields\SetFieldPositionCommand::class    => ['field' => $request->get('id')],
                    Fields\SetRolesPermissionCommand::class  => ['field' => $request->get('id')],
                    Fields\SetGroupsPermissionCommand::class => ['field' => $request->get('id')],
                    ListItems\CreateListItemCommand::class   => ['field' => $request->get('id')],
                    // User Settings API
                    UserSettings\SetPasswordCommand::class => ['user' => $request->get('id')],
                    // Issues API
                    Issues\CloneIssueCommand::class    => ['issue' => $request->get('id')],
                    Issues\UpdateIssueCommand::class   => ['issue' => $request->get('id')],
                    Issues\DeleteIssueCommand::class   => ['issue' => $request->get('id')],
                    Issues\ChangeStateCommand::class   => ['issue' => $request->get('id'), 'state' => $request->get('state')],
                    Issues\ReassignIssueCommand::class => ['issue' => $request->get('id'), 'responsible' => $request->get('user')],
                    Issues\SuspendIssueCommand::class  => ['issue' => $request->get('id')],
                    Issues\ResumeIssueCommand::class   => ['issue' => $request->get('id')],
                    // Watchers API
                    Issues\WatchIssueCommand::class   => ['issue' => $request->get('id')],
                    Issues\UnwatchIssueCommand::class => ['issue' => $request->get('id')],
                    // Comments API
                    Comments\AddCommentCommand::class => ['issue' => $request->get('id')],
                    // Files API
                    Files\AttachFileCommand::class => ['issue' => $request->get('id'), 'file' => $request->files->get('file')],
                    // Dependencies API
                    Dependencies\AddDependencyCommand::class    => ['issue' => $request->get('id'), 'dependency' => $request->get('issue')],
                    Dependencies\RemoveDependencyCommand::class => ['issue' => $request->get('id'), 'dependency' => $request->get('issue')],
                    // Related Issues API
                    RelatedIssues\AddRelatedIssueCommand::class    => ['issue' => $request->get('id'), 'relatedIssue' => $request->get('issue')],
                    RelatedIssues\RemoveRelatedIssueCommand::class => ['issue' => $request->get('id'), 'relatedIssue' => $request->get('issue')],
                ],
                AbstractNormalizer::CALLBACKS => [
                    'roles' => fn ($innerObject, $outerObject) => match ($outerObject) {
                        Templates\SetRolesPermissionCommand::class => array_map(fn (string $role) => SystemRoleEnum::tryFrom($role), $innerObject),
                        States\SetRolesTransitionCommand::class    => array_map(fn (string $role) => SystemRoleEnum::tryFrom($role), $innerObject),
                        Fields\SetRolesPermissionCommand::class    => array_map(fn (string $role) => SystemRoleEnum::tryFrom($role), $innerObject),
                        default                                    => $innerObject
                    },
                ],
            ]);
        }
    }
}
