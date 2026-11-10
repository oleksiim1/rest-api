<?php

declare(strict_types=1);

namespace App\Recipes\Infrastructure\Persistence\Doctrine;

use App\Recipes\Domain\Recipe;
use App\Recipes\Domain\RecipeId;
use App\Recipes\Domain\RecipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

final class DoctrineRecipeRepository implements RecipeRepository
{
    public function __construct(private readonly ManagerRegistry $registry)
    {
    }

    public function nextIdentity(): RecipeId
    {
        return RecipeId::fromString(Uuid::v7()->toRfc4122());
    }

    public function save(Recipe $recipe): void
    {
        $em = $this->em();
        $em->persist($recipe);
        $em->flush();
    }

    public function remove(Recipe $recipe): void
    {
        $em = $this->em();
        $em->remove($recipe);
        $em->flush();
    }

    public function ofId(RecipeId $id): ?Recipe
    {
        return $this->em()->find(Recipe::class, $id);
    }

    public function page(int $offset, int $limit): array
    {
        return $this->em()->createQuery(
            'SELECT r FROM '.Recipe::class.' r ORDER BY r.title.value ASC, r.id ASC'
        )
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getResult();
    }

    private function em(): EntityManagerInterface
    {
        $em = $this->registry->getManagerForClass(Recipe::class);
        \assert($em instanceof EntityManagerInterface);

        return $em;
    }
}
