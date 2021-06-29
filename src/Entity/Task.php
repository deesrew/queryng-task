<?php

namespace App\Entity;

use App\Repository\TaskRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TaskRepository::class)
 */
class Task
{
    /**
     * Массив с названиями очередей для RabbitMQ
     */
    const queuesArray = array(
        'queueForCron',
        'queueForConsumer'
    );

    const wordsInField = 4;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $field1;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $field2;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date_created;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getField1(): ?string
    {
        return $this->field1;
    }

    public function setField1(string $field1): self
    {
        $this->field1 = $field1;

        return $this;
    }

    public function getField2(): ?string
    {
        return $this->field2;
    }

    public function setField2(string $field2): self
    {
        $this->field2 = $field2;

        return $this;
    }

    public function getDateCreated(): ?\DateTimeInterface
    {
        return $this->date_created;
    }

    public function setDateCreated(\DateTimeInterface $date_created): self
    {
        $this->date_created = $date_created;

        return $this;
    }
}
