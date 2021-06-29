<?php

namespace App\Repository;

use App\Entity\Vocabulary;
use App\Pagination\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Vocabulary|null find($id, $lockMode = null, $lockVersion = null)
 * @method Vocabulary|null findOneBy(array $criteria, array $orderBy = null)
 * @method Vocabulary[]    findAll()
 * @method Vocabulary[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VocabularyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Vocabulary::class);
    }

    public function findLatest(int $page = 1): Paginator
    {
        $qb = $this
            ->createQueryBuilder("vocabulary");
        return (new Paginator($qb))->paginate($page);
    }

    public function getAll()
    {
        return $this->createQueryBuilder('words')
            ->getQuery()
            ->getResult()
            ;
    }

//    public function count(): int
//    {
//        $qb = $this->createQueryBuilder('t');
//        return $qb
//            ->select('count(t.id)')
//            ->getQuery()
//            ->getSingleScalarResult();
//    }

    // /**
    //  * @return Vocabulary[] Returns an array of Vocabulary objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('v.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Vocabulary
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
