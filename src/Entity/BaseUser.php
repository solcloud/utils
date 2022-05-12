<?php

namespace Solcloud\Utils\Entity;

use Doctrine\ORM\Mapping as ORM;

class BaseUser
{
    public const COL_ID = 'id';
    public const COL_USERNAME = 'username';
    public const COL_PASSWORD = 'password';
    public const COL_NAME = 'name';
    public const COL_LAST_NAME = 'last_name';
    public const COL_ROLES = 'roles';
    public const COL_AVATAR = 'avatar';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer", name=self::COL_ID)
     */
    protected int $id;
    /**
     * @ORM\Column(type="string", name=self::COL_USERNAME, length=100)
     */
    protected string $username;
    /**
     * @ORM\Column(type="string", name=self::COL_PASSWORD, length=100)
     */
    protected string $password = '';
    /**
     * @ORM\Column(type="string", name=self::COL_NAME, length=100)
     */
    protected string $name = '';
    /**
     * @ORM\Column(type="string", name=self::COL_LAST_NAME, length=100)
     */
    protected string $lastName = '';
    /**
     * @ORM\Column(type="array", name=self::COL_ROLES)
     * @var string[] $roles
     */
    protected array $roles = [];
    /**
     * @ORM\Column(type="string", name=self::COL_AVATAR, length=100, nullable=true)
     */
    protected ?string $avatar = null;

    public function __construct(string $username)
    {
        $this->username = $username;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setLastName(string $lastName): void
    {
        $this->lastName = $lastName;
    }

    /**
     * @param string[] $roles
     */
    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    public function getAvatar(): string
    {
        $key = $this->avatar;
        if ($key === null) {
            $key = md5(
                sprintf('%s,%s,%s',
                    $this->getUsername(),
                    $this->getName(),
                    $this->getLastName(),
                )
            );
        }

        return sprintf('https://www.gravatar.com/avatar/%s?s=32&d=identicon', $key);
    }

    public function setAvatar(string $avatar): void
    {
        $this->avatar = $avatar;
    }

}
