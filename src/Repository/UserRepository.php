<?php

declare(strict_types=1);

namespace Solcloud\Utils\Repository;

use App\Models\Entity\User;

class UserRepository extends BaseRepository
{

    public function findByUsername(string $username): ?User
    {
        $username = mb_strtolower($username);
        $entity = $this->getRepository(User::class)->findOneBy([
            User::COL_USERNAME => $username,
        ]);
        return $entity;
    }

    public function findById(int $id): User
    {
        return $this->findOrFailEntityById(User::class, $id);
    }

}
