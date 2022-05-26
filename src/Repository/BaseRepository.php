<?php

declare(strict_types=1);

namespace Solcloud\Utils\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Exception;

class BaseRepository
{

    protected EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param string|string[] $value
     * @param callable|null $criteria function(\Doctrine\ORM\QueryBuilder $qb): void {} Entity alias is '$entity::ALIAS'
     * @return array<mixed,mixed>
     */
    public function findPairs(string $entity, string $key, $value, ?callable $criteria = null): array
    {
        $qb = $this->em->createQueryBuilder();
        $qb
            ->addSelect("{$key} AS key")
            ->from($entity, $entity::ALIAS)
            ->orderBy('value')
        ;
        if (is_array($value)) {
            $qb->addSelect($qb->expr()->concat(...$value) . ' AS value');
        } else {
            $qb->addSelect("{$value} AS value");
        }

        if ($criteria) {
            call_user_func($criteria, $qb);
        }

        $output = [];
        foreach ($qb->getQuery()->getArrayResult() as $row) {
            $output[$row['key']] = $row['value'];
        }
        return $output;
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->em;
    }

    /**
     * @param string $entityName
     * @phpstan-template T
     * @phpstan-param class-string<T> $entityName
     */
    public function getRepository(string $entityName): ObjectRepository
    {
        return $this->getEntityManager()->getRepository($entityName);
    }

    /**
     * @template T
     * @param class-string<T> $class
     * @return T|null
     */
    public function findEntityByPrimary(string $class, int $id)
    {
        /** @var T|null $entity */
        $entity = $this->getEntityManager()->find($class, $id);
        return $entity;
    }

    /**
     * @template T
     * @param class-string<T> $class
     * @return T
     */
    public function findOrFailEntityById(string $class, int $id)
    {
        $entity = $this->findEntityByPrimary($class, $id);
        if ($entity) {
            return $entity;
        }

        $this->error("Entity '{$class}' with id '{$id}' not found");
    }

    /**
     * @template T
     * @param class-string<T> $class
     * @return T
     */
    public function findOrFailEntityByUuId(string $class, string $uuid, string $columnName = 'uuid')
    {
        return $this->findOrFailEntityBy(
            $class,
            [
                $columnName => $uuid,
            ], "Entity '{$class}' with uuid '{$uuid}' not found in column '{$columnName}'"
        );
    }

    /**
     * @template T
     * @param class-string<T> $class
     * @param array<string,scalar> $conditions
     * @return T|null
     */
    public function findEntityBy(string $class, array $conditions)
    {
        /** @var T|null $entity */
        $entity = $this->getEntityManager()
                       ->getRepository($class)
                       ->findOneBy($conditions);
        return $entity;
    }

    /**
     * @template T
     * @param class-string<T> $class
     * @param array<string,scalar> $conditions
     * @return T
     */
    public function findOrFailEntityBy(string $class, array $conditions, ?string $error = null)
    {
        /** @var T|null $entity */
        $entity = $this->findEntityBy($class, $conditions);
        if ($entity) {
            return $entity;
        }

        $this->error($error ?? "Entity '{$class}' not found (using conditions params)");
    }

    protected final function error(string $errorMsg = ''): never
    {
        throw new Exception($errorMsg);
    }

}
