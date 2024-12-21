<?php

namespace App\Repository;

use App\Entity\LoyaltyTransaction;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<LoyaltyTransaction>
 *
 * @method LoyaltyTransaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method LoyaltyTransaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method LoyaltyTransaction[]    findAll()
 * @method LoyaltyTransaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LoyaltyTransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoyaltyTransaction::class);
    }

    public function getUserTransactions(User $user): array
    {
        return $this->createQueryBuilder('lt')
            ->andWhere('lt.user = :user')
            ->setParameter('user', $user)
            ->orderBy('lt.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getUserTransactionsForPeriod(User $user, \DateTime $startDate, \DateTime $endDate): array
    {
        return $this->createQueryBuilder('lt')
            ->andWhere('lt.user = :user')
            ->andWhere('lt.createdAt BETWEEN :start AND :end')
            ->setParameter('user', $user)
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('lt.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
