<?php
namespace App\EventSubscriber;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
class HashPasswordSubscriber
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof User) {
            return;
        }

        $this->hashPassword($entity);
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof User || !$entity->getPlainPassword()) {
            return;
        }

        // Hash password before setting it
        $hashedPassword = $this->passwordHasher->hashPassword(
            $entity,
            $entity->getPlainPassword()
        );

        // Set the new password directly in the changeset
        $entity->setPassword($hashedPassword);
        $entity->eraseCredentials();
    }

    private function hashPassword(User $user): void
    {
        if (!$user->getPlainPassword()) {
            return;
        }

        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $user->getPlainPassword()
        );

        $user->setPassword($hashedPassword);
        $user->eraseCredentials();
    }
}