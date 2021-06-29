<?php

namespace App\Repository;

use App\Entity\Task;
use App\Pagination\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Task|null find($id, $lockMode = null, $lockVersion = null)
 * @method Task|null findOneBy(array $criteria, array $orderBy = null)
 * @method Task[]    findAll()
 * @method Task[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    public function findLatest(int $page = 1): Paginator
    {
        $qb = $this
            ->createQueryBuilder("task");
        return (new Paginator($qb))->paginate($page);
    }

    public function insertOne($arr)
    {

        if (is_array($arr['date_created'])) {
            $dateFormatted = $arr['date_created']['date'];
        } else {
            $dateFormatted = date_format($arr['date_created'], 'Y-m-d H:i:s');
        }

        $em = $this->getEntityManager();

        $sql = "INSERT INTO task (field1, field2, date_created) VALUES ('{$arr['field1']}', '{$arr['field1']}', '{$dateFormatted}')";
        $stmt = $em->getConnection()->prepare($sql);
        $result = $stmt->execute();
    }

    // /**
    //  * @return Task[] Returns an array of Task objects
    //  */
    public function findByFieldsInDB($value)
    {
        return $this->createQueryBuilder('task')
            ->andWhere('task.field1 LIKE :val OR task.field2 LIKE :val')
            ->setParameter('val', "%".$value."%")
            ->orderBy('task.id', 'ASC')
            ->setMaxResults(15)
            ->getQuery()
            ->getResult()
        ;
    }

    /*
    public function findOneBySomeField($value): ?Task
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
