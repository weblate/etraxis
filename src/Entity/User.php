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

namespace App\Entity;

use App\Entity\Enums\AccountProviderEnum;
use App\Entity\Enums\LocaleEnum;
use App\Entity\Enums\ThemeEnum;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints as Assert;
use Symfony\Component\Mime\Address;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

/**
 * User.
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\UniqueConstraint(fields: ['email'])]
#[ORM\UniqueConstraint(fields: ['resetToken'])]
#[ORM\UniqueConstraint(fields: ['accountProvider', 'accountUid'])]
#[Assert\UniqueEntity(fields: ['email'], message: 'user.conflict.email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    // Roles.
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_USER  = 'ROLE_USER';

    // Constraints.
    public const MAX_EMAIL       = 254;
    public const MAX_FULLNAME    = 50;
    public const MAX_DESCRIPTION = 100;

    /**
     * Unique ID.
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected int $id;

    /**
     * Email address.
     */
    #[ORM\Column(length: 254)]
    protected string $email;

    /**
     * Password.
     */
    #[ORM\Column(nullable: true)]
    protected ?string $password = null;

    /**
     * Password reset token.
     */
    #[ORM\Column(length: 32, nullable: true)]
    protected ?string $resetToken = null;

    /**
     * Unix Epoch timestamp when the reset token expires.
     */
    #[ORM\Column(nullable: true)]
    protected ?int $resetTokenExpiresAt = null;

    /**
     * Full name.
     */
    #[ORM\Column(length: 50)]
    protected string $fullname;

    /**
     * Optional description of the user.
     */
    #[ORM\Column(length: 100, nullable: true)]
    protected ?string $description = null;

    /**
     * Whether the user has administration privileges.
     */
    #[ORM\Column]
    protected bool $admin;

    /**
     * Whether the account is disabled.
     */
    #[ORM\Column]
    protected bool $disabled;

    /**
     * Account provider (@see AccountProviderEnum enum).
     */
    #[ORM\Column(length: 20)]
    protected string $accountProvider;

    /**
     * Account UID as in the external provider's system.
     */
    #[ORM\Column]
    protected string $accountUid;

    /**
     * User's settings.
     */
    #[ORM\Column(nullable: true, options: ['jsonb' => true])]
    protected array $settings = [];

    /**
     * List of groups the user is a member of.
     */
    #[ORM\ManyToMany(targetEntity: Group::class, mappedBy: 'members')]
    #[ORM\OrderBy(['name' => 'ASC', 'project' => 'ASC'])]
    protected Collection $groups;

    /**
     * Creates new user.
     */
    public function __construct()
    {
        $this->admin    = false;
        $this->disabled = false;

        $this->accountProvider = AccountProviderEnum::eTraxis->value;
        $this->accountUid      = Uuid::v4()->toRfc4122();

        $this->groups = new ArrayCollection();
    }

    /**
     * {@inheritDoc}
     */
    public function getRoles(): array
    {
        return [
            $this->admin ? self::ROLE_ADMIN : self::ROLE_USER,
        ];
    }

    /**
     * {@inheritDoc}
     *
     * @codeCoverageIgnore Empty implementation
     */
    public function eraseCredentials(): void
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    /**
     * Property getter.
     */
    #[Groups(['api', 'info'])]
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Property getter.
     */
    #[Groups(['api', 'info', 'profile', 'login'])]
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Property setter.
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Returns user's email address.
     */
    public function getEmailAddress(): Address
    {
        return new Address($this->email, $this->fullname);
    }

    /**
     * Property getter.
     *
     * {@inheritDoc}
     */
    #[Groups('login')]
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Property setter.
     */
    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Generates new password reset token which expires in specified period of time.
     *
     * @param \DateInterval $interval Expiration period
     *
     * @return string Generated token
     */
    public function generateResetToken(\DateInterval $interval): string
    {
        $now = new \DateTime();

        $this->resetToken          = str_replace('-', '', Uuid::v4()->toRfc4122());
        $this->resetTokenExpiresAt = $now->add($interval)->getTimestamp();

        return $this->resetToken;
    }

    /**
     * Clears current reset token.
     */
    public function clearResetToken(): self
    {
        $this->resetToken          = null;
        $this->resetTokenExpiresAt = null;

        return $this;
    }

    /**
     * Checks whether specified reset token is valid.
     */
    public function isResetTokenValid(string $token): bool
    {
        return !$this->isAccountExternal()
            && $this->resetToken === $token
            && null !== $this->resetTokenExpiresAt
            && time() < $this->resetTokenExpiresAt;
    }

    /**
     * Property getter.
     */
    #[Groups(['api', 'info', 'profile'])]
    public function getFullname(): string
    {
        return $this->fullname;
    }

    /**
     * Property setter.
     */
    public function setFullname(string $fullname): self
    {
        $this->fullname = $fullname;

        return $this;
    }

    /**
     * Property getter.
     */
    #[Groups('api')]
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Property setter.
     */
    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Property getter.
     */
    #[Groups('api')]
    public function isAdmin(): bool
    {
        return $this->admin;
    }

    /**
     * Property setter.
     */
    public function setAdmin(bool $admin): self
    {
        $this->admin = $admin;

        return $this;
    }

    /**
     * Property getter.
     */
    #[Groups('api')]
    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    /**
     * Property setter.
     */
    public function setDisabled(bool $disabled): self
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Property getter.
     */
    #[Groups(['api'])]
    public function getAccountProvider(): AccountProviderEnum
    {
        return AccountProviderEnum::tryFrom($this->accountProvider) ?? AccountProviderEnum::eTraxis;
    }

    /**
     * Property setter.
     */
    public function setAccountProvider(AccountProviderEnum $provider): self
    {
        $this->accountProvider = $provider->value;

        return $this;
    }

    /**
     * Property getter.
     */
    public function getAccountUid(): string
    {
        return $this->accountUid;
    }

    /**
     * Property setter.
     */
    public function setAccountUid(string $uid): self
    {
        $this->accountUid = $uid;

        return $this;
    }

    /**
     * Checks whether the account is loaded from a 3rd party provider.
     */
    public function isAccountExternal(): bool
    {
        return AccountProviderEnum::eTraxis->value !== $this->accountProvider;
    }

    /**
     * Returns user's locale.
     */
    #[Groups(['api', 'settings'])]
    public function getLocale(): LocaleEnum
    {
        return LocaleEnum::tryFrom($this->settings['locale'] ?? '') ?? LocaleEnum::FALLBACK;
    }

    /**
     * Sets user's locale.
     */
    public function setLocale(LocaleEnum $locale): self
    {
        $this->settings['locale'] = $locale->value;

        return $this;
    }

    /**
     * Returns user's theme.
     */
    #[Groups('settings')]
    public function getTheme(): ThemeEnum
    {
        return ThemeEnum::tryFrom($this->settings['theme'] ?? '') ?? ThemeEnum::FALLBACK;
    }

    /**
     * Sets user's timezone.
     */
    public function setTheme(ThemeEnum $theme): self
    {
        $this->settings['theme'] = $theme->value;

        return $this;
    }

    /**
     * Returns user's timezone.
     */
    #[Groups(['api', 'settings'])]
    public function getTimezone(): string
    {
        $timezone = $this->settings['timezone'] ?? 'UTC';

        return in_array($timezone, timezone_identifiers_list(), true) ? $timezone : 'UTC';
    }

    /**
     * Sets user's timezone.
     */
    public function setTimezone(string $timezone): self
    {
        if (in_array($timezone, timezone_identifiers_list(), true)) {
            $this->settings['timezone'] = $timezone;
        }

        return $this;
    }

    /**
     * Property getter.
     */
    public function getGroups(): Collection
    {
        return $this->groups;
    }
}
