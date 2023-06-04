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

namespace App\Serializer\Normalizer;

use App\Entity\Change;
use App\Entity\Comment;
use App\Entity\Enums\FieldTypeEnum;
use App\Entity\Event;
use App\Entity\FieldValue;
use App\Entity\File;
use App\Entity\Issue;
use App\Entity\State;
use App\Entity\Transition;
use App\Entity\User;
use App\Repository\Contracts\ChangeRepositoryInterface;
use App\Repository\Contracts\CommentRepositoryInterface;
use App\Repository\Contracts\DecimalValueRepositoryInterface;
use App\Repository\Contracts\DependencyRepositoryInterface;
use App\Repository\Contracts\FieldValueRepositoryInterface;
use App\Repository\Contracts\FileRepositoryInterface;
use App\Repository\Contracts\IssueRepositoryInterface;
use App\Repository\Contracts\ListItemRepositoryInterface;
use App\Repository\Contracts\RelatedIssueRepositoryInterface;
use App\Repository\Contracts\StringValueRepositoryInterface;
use App\Repository\Contracts\TextValueRepositoryInterface;
use App\Repository\Contracts\WatcherRepositoryInterface;
use App\Security\Voter\CommentVoter;
use App\Security\Voter\DependencyVoter;
use App\Security\Voter\FileVoter;
use App\Security\Voter\IssueVoter;
use App\Security\Voter\RelatedIssueVoter;
use App\Utils\OpenApi\IssueExtended;
use App\Utils\OpenApiInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * 'Issue' entity normalizer.
 */
class IssueEntityNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(
        protected readonly AuthorizationCheckerInterface $security,
        protected readonly TokenStorageInterface $tokenStorage,
        protected readonly IssueRepositoryInterface $issueRepository,
        protected readonly FieldValueRepositoryInterface $fieldValueRepository,
        protected readonly DecimalValueRepositoryInterface $decimalValueRepository,
        protected readonly StringValueRepositoryInterface $stringValueRepository,
        protected readonly TextValueRepositoryInterface $textValueRepository,
        protected readonly ListItemRepositoryInterface $listItemRepository,
        protected readonly ChangeRepositoryInterface $changeRepository,
        protected readonly WatcherRepositoryInterface $watcherRepository,
        protected readonly CommentRepositoryInterface $commentRepository,
        protected readonly FileRepositoryInterface $fileRepository,
        protected readonly DependencyRepositoryInterface $dependencyRepository,
        protected readonly RelatedIssueRepositoryInterface $relatedIssueRepository
    ) {
    }

    /**
     * @see NormalizerInterface::normalize
     */
    public function normalize(mixed $object, string $format = null, array $context = []): array
    {
        // Setting this to flag that the normalizer has been already called before.
        $context[self::class] = true;

        $localContext = array_merge($context, [OpenApiInterface::ACTIONS => false]);

        /** @var Issue $object */
        $json = $this->normalizer->normalize($object, $format, $localContext);

        if ($context[OpenApiInterface::ACTIONS] ?? false) {
            // Get current user.
            $user = $this->tokenStorage->getToken()->getUser();

            // Get extra properties data.
            $states        = $this->issueRepository->getTransitionsByUser($object, $user);
            $assignees     = $this->issueRepository->getResponsiblesByState($object->getState());
            $values        = $this->fieldValueRepository->getLatestValues($object, $user);
            $changes       = $this->changeRepository->findAllByIssue($object, $user);
            $watchers      = $this->watcherRepository->findAllByIssue($object);
            $comments      = $this->commentRepository->findAllByIssue($object, !$this->security->isGranted(CommentVoter::READ_PRIVATE_COMMENT, $object));
            $files         = $this->fileRepository->findAllByIssue($object);
            $dependencies  = $this->dependencyRepository->findAllByIssue($object);
            $relatedIssues = $this->relatedIssueRepository->findAllByIssue($object);

            // Get existing transitions.
            $allValues   = $this->fieldValueRepository->findAllByIssue($object, $user);
            $transitions = array_values(array_unique(
                array_map(fn (FieldValue $value) => $value->getTransition(), $allValues)
            ));

            // Get IDs of all field values which are foreign keys to other entities.
            $getIds = function (array $values, array $changes, FieldTypeEnum $type): array {
                $values  = array_filter($values, fn (FieldValue $value) => $value->getField()->getType() === $type);
                $changes = array_filter($changes, fn (Change $change) => (
                    null === $change->getField() && FieldTypeEnum::String === $type || $change->getField()?->getType() === $type
                ));

                $ids = array_merge(
                    array_map(fn (FieldValue $value) => $value->getValue(), $values),
                    array_map(fn (Change $change) => $change->getOldValue(), $changes),
                    array_map(fn (Change $change) => $change->getNewValue(), $changes),
                );

                return array_unique(array_filter($ids, fn (?int $id) => null !== $id));
            };

            // Warmup repositories with field values.
            $this->decimalValueRepository->warmup($getIds($allValues, $changes, FieldTypeEnum::Decimal));
            $this->issueRepository->warmup($getIds($allValues, $changes, FieldTypeEnum::Issue));
            $this->listItemRepository->warmup($getIds($allValues, $changes, FieldTypeEnum::List));
            $this->stringValueRepository->warmup($getIds($allValues, $changes, FieldTypeEnum::String));
            $this->textValueRepository->warmup($getIds($allValues, $changes, FieldTypeEnum::Text));

            // Append extra properties.
            $json[IssueExtended::PROPERTY_EVENTS]       = array_map(fn (Event $event) => $this->normalizer->normalize($event, $format, $localContext), $object->getEvents()->toArray());
            $json[IssueExtended::PROPERTY_TRANSITIONS]  = array_map(fn (Transition $transition) => $this->normalizer->normalize($transition, $format, $localContext), $transitions);
            $json[IssueExtended::PROPERTY_STATES]       = array_map(fn (State $state) => $this->normalizer->normalize($state, $format, $localContext), $states);
            $json[IssueExtended::PROPERTY_ASSIGNEES]    = array_map(fn (User $user) => $this->normalizer->normalize($user, $format, $localContext), $assignees);
            $json[IssueExtended::PROPERTY_VALUES]       = array_map(fn (FieldValue $value) => $this->normalizer->normalize($value, $format, $localContext), $values);
            $json[IssueExtended::PROPERTY_CHANGES]      = array_map(fn (Change $change) => $this->normalizer->normalize($change, $format, $localContext), $changes);
            $json[IssueExtended::PROPERTY_WATCHERS]     = array_map(fn (User $watcher) => $this->normalizer->normalize($watcher, $format, $localContext), $watchers);
            $json[IssueExtended::PROPERTY_COMMENTS]     = array_map(fn (Comment $comment) => $this->normalizer->normalize($comment, $format, $localContext), $comments);
            $json[IssueExtended::PROPERTY_FILES]        = array_map(fn (File $file) => $this->normalizer->normalize($file, $format, $localContext), $files);
            $json[IssueExtended::PROPERTY_DEPENDENCIES] = array_map(fn (Issue $dependency) => $this->normalizer->normalize($dependency, $format, $localContext), $dependencies);
            $json[IssueExtended::PROPERTY_RELATED]      = array_map(fn (Issue $issue) => $this->normalizer->normalize($issue, $format, $localContext), $relatedIssues);

            // Append available actions.
            $json[OpenApiInterface::ACTIONS] = [
                IssueExtended::ACTION_CLONE               => $this->security->isGranted(IssueVoter::CREATE_ISSUE, $object->getTemplate()),
                IssueExtended::ACTION_UPDATE              => $this->security->isGranted(IssueVoter::UPDATE_ISSUE, $object),
                IssueExtended::ACTION_DELETE              => $this->security->isGranted(IssueVoter::DELETE_ISSUE, $object),
                IssueExtended::ACTION_CHANGE_STATE        => $this->security->isGranted(IssueVoter::CHANGE_STATE, $object),
                IssueExtended::ACTION_REASSIGN            => $this->security->isGranted(IssueVoter::REASSIGN_ISSUE, $object),
                IssueExtended::ACTION_SUSPEND             => $this->security->isGranted(IssueVoter::SUSPEND_ISSUE, $object),
                IssueExtended::ACTION_RESUME              => $this->security->isGranted(IssueVoter::RESUME_ISSUE, $object),
                IssueExtended::ACTION_ADD_PUBLIC_COMMENT  => $this->security->isGranted(CommentVoter::ADD_PUBLIC_COMMENT, $object),
                IssueExtended::ACTION_ADD_PRIVATE_COMMENT => $this->security->isGranted(CommentVoter::ADD_PRIVATE_COMMENT, $object),
                IssueExtended::ACTION_ATTACH_FILE         => $this->security->isGranted(FileVoter::ATTACH_FILE, $object),
                IssueExtended::ACTION_DELETE_FILE         => $this->security->isGranted(FileVoter::DELETE_FILE, $object),
                IssueExtended::ACTION_ADD_DEPENDENCY      => $this->security->isGranted(DependencyVoter::ADD_DEPENDENCY, $object),
                IssueExtended::ACTION_REMOVE_DEPENDENCY   => $this->security->isGranted(DependencyVoter::REMOVE_DEPENDENCY, $object),
                IssueExtended::ACTION_ADD_RELATED         => $this->security->isGranted(RelatedIssueVoter::ADD_RELATED_ISSUE, $object),
                IssueExtended::ACTION_REMOVE_RELATED      => $this->security->isGranted(RelatedIssueVoter::REMOVE_RELATED_ISSUE, $object),
            ];
        }

        return $json;
    }

    /**
     * @see NormalizerInterface::supportsNormalization
     */
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof Issue && !($context[self::class] ?? false);
    }
}
