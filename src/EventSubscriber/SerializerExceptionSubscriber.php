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

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Exception\ValidationFailedException;
use Symfony\Component\Serializer\Exception\MissingConstructorArgumentsException;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Converts any deserialization exception, thrown during an API request, into validation exception.
 */
class SerializerExceptionSubscriber implements EventSubscriberInterface
{
    protected const ERROR_BLANK_VALUE   = 'This value should not be blank.';
    protected const ERROR_INVALID_VALUE = 'This value is not valid.';

    /**
     * @codeCoverageIgnore Dependency Injection constructor
     */
    public function __construct(protected readonly TranslatorInterface $translator)
    {
    }

    /**
     * @see EventSubscriberInterface
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => [
                ['onNotEncodableValueException'],
                ['onMissingConstructorArgumentsException'],
                ['onNotNormalizableValueException'],
            ],
        ];
    }

    /**
     * The submitted JSON cannot be decoded.
     */
    public function onNotEncodableValueException(ExceptionEvent $event): void
    {
        $request   = $event->getRequest();
        $throwable = $event->getThrowable();

        if (str_starts_with($request->getPathInfo(), '/api/') && $throwable instanceof NotEncodableValueException) {
            $event->setThrowable(new BadRequestHttpException('The request body is not valid JSON.'));
        }
    }

    /**
     * A value for the required property is not submitted.
     */
    public function onMissingConstructorArgumentsException(ExceptionEvent $event): void
    {
        $request   = $event->getRequest();
        $throwable = $event->getThrowable();

        if (str_starts_with($request->getPathInfo(), '/api/') && $throwable instanceof MissingConstructorArgumentsException) {
            $missingArguments = $throwable->getMissingConstructorArguments();

            $violations = new ConstraintViolationList(
                array_map(fn (string $missingArgument) => new ConstraintViolation(
                    $this->translator->trans(self::ERROR_BLANK_VALUE, [], 'validators'),
                    self::ERROR_BLANK_VALUE,
                    [],
                    $request,
                    $missingArgument,
                    null
                ), $missingArguments)
            );

            $event->setThrowable(new ValidationFailedException($request, $violations));
        }
    }

    /**
     * Type of submitted value is not compatible with the type of the target property.
     */
    public function onNotNormalizableValueException(ExceptionEvent $event): void
    {
        $request   = $event->getRequest();
        $throwable = $event->getThrowable();

        if (str_starts_with($request->getPathInfo(), '/api/') && $throwable instanceof NotNormalizableValueException) {
            $message = 'null' === $throwable->getCurrentType() ? self::ERROR_BLANK_VALUE : self::ERROR_INVALID_VALUE;

            $violations = new ConstraintViolationList([
                new ConstraintViolation(
                    $this->translator->trans($message, [], 'validators'),
                    $message,
                    [],
                    $request,
                    $throwable->getPath(),
                    $throwable->getCurrentType()
                ),
            ]);

            $event->setThrowable(new ValidationFailedException($request, $violations));
        }
    }
}
