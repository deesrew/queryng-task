<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\Vocabulary;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use App\Repository\VocabularyRepository;
use App\Service\RabbitMQ;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/task")
 */
class TaskController extends AbstractController
{
    /**
     * @Route("/", name="task_index", defaults={"page": "1", "_format"="html"}, methods={"GET"})
     * @Route("/page/{page<[1-9]\d*>}", defaults={"_format"="html"}, methods="GET", name="task_index_paginated")
     */
    public function index(TaskRepository $taskRepository, int $page): Response
    {
        $latestWords = $taskRepository->findLatest($page);

        $count = $taskRepository->createQueryBuilder('task')
            ->select('count(task.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('task/index.html.twig', [
            'paginator' => $latestWords,
            'info' => [
                'count' => $count,
            ]
        ]);

    }

    /**
     * @Route ("/clear", name="task_clear")
     */
    public function clear(): Response
    {

        $em = $this->getDoctrine()->getManager();

        $repository = $em->getRepository(Task::class);
        $entities = $repository->findAll();

        foreach ($entities as $entity) {
            $em->remove($entity);
        }
        $em->flush();
        $em->clear();

        return $this->redirectToRoute('task_index');
    }

    /**
     * @Route("/new", name="task_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $task = new Task();
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($task);
            $entityManager->flush();

            return $this->redirectToRoute('task_index');
        }

        return $this->render('task/new.html.twig', [
            'task' => $task,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/send-in-queue", name="task_send_one", methods={"GET","POST"})
     */
    public function sendInQueue(Request $request): Response
    {
        $task = new Task();
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $queueName = Task::queuesArray[rand(0, 1)];

            $rabbit = new RabbitMQ();
            $rabbit->setConnection();
            $rabbit->setChanel();

            $data = array(
                'field1' => $task->getField1(),
                'field2' => $task->getField2(),
                'date_created' => date_create('now')
            );

            $message = json_encode($data);

            $rabbit->sendMessage($message, $queueName);
            $rabbit->close();

            return $this->render('task/send_in_queue.html.twig', [
                'task' => $task,
                'form' => $form->createView(),
                'info' => "Sent in ${queueName}!"
            ]);
        }

        return $this->render('task/send_in_queue.html.twig', [
            'task' => $task,
            'form' => $form->createView(),
            'info' => false
        ]);
    }

    /**
     * @Route("/generate", name="task_generate", methods={"GET","POST"})
     */
    public function generateMassages(Request $request, VocabularyRepository $vocabularyRepository): Response
    {
        $defaultData = ['hasDictionaryDef' => 'false'];
        $form = $this->createFormBuilder($defaultData)
            ->add('massagesNumber', IntegerType::class, ['data' => 100])
            ->add('wordsNumber', IntegerType::class, ['data' => 10])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $rabbit = new RabbitMQ();
            $rabbit->setConnection();
            $rabbit->setChanel();

            $data = $form->getData();

            $words = $vocabularyRepository->findAll();

            $counter = 0;
            $wordArr = [];

            foreach ($words as $wordKey => $word) {
                $counter++;
                $wordArr[] = $word->getWord();

                if ($counter > $data['wordsNumber']) {
                    break;
                }
            }

            for ($j = 0; $j < $data['massagesNumber']; $j++) {

                $queueName = Task::queuesArray[$j % 2];

                $filed1 = "";
                $filed2 = "";

                for ($i = 0; $i < Task::wordsInField; ++$i) {
                    $filed1 .= $wordArr[mt_rand(0, $counter - 1)] . ' ';
                    $filed2 .= $wordArr[mt_rand(0, $counter - 1)] . ' ';
                }

                $taskRow = array(
                    'field1' => $filed1,
                    'field2' => $filed2,
                    'date_created' => date_create('now')
                );

                $message = json_encode($taskRow, true);
                $rabbit->sendMessage($message, $queueName);
            }

            $rabbit->close();

            return $this->render('task/generate.html.twig',
                array(
                    'form' => $form->createView(),
                    'info' => "massages sent"
                )
            );
        }

        return $this->render('task/generate.html.twig',
            array(
                'form' => $form->createView(),
                'info' => false
            )
        );
    }

    /**
     * @Route("/{id}", name="task_show", methods={"GET"})
     */
    public function show(Task $task): Response
    {
        return $this->render('task/show.html.twig', [
            'task' => $task,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="task_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Task $task): Response
    {
        $form = $this->createForm(TaskType::class, $task);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('task_index');
        }

        return $this->render('task/edit.html.twig', [
            'task' => $task,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/delete/{id}", name="task_delete", methods={"GET","POST"})
     */
    public function delete(Request $request, Task $task, int $id): Response
    {
        if ($this->isCsrfTokenValid('delete' . $task->getId(), $request->request->get('_token'))) {

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($task);
            $entityManager->flush();
        } elseif (isset($id)) {

            $entityManager = $this->getDoctrine()->getManager();
            $task = $entityManager
                ->getRepository(Task::class)
                ->find($id);

            if (!$id) {
                throw $this->createNotFoundException('word not found');
            }

            $entityManager->remove($task);
            $entityManager->flush();
        }

        return $this->redirectToRoute('task_index');
    }
}
