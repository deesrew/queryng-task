<?php

namespace App\Controller;

use App\Entity\Vocabulary;
use App\Form\VocabularyType;
use App\Repository\VocabularyRepository;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/vocabulary")
 */
class VocabularyController extends AbstractController
{
    /**
     * @Route("/", name="vocabulary_index", defaults={"page": "1", "_format"="html"}, methods={"GET"})
     * @Route("/page/{page<[1-9]\d*>}", defaults={"_format"="html"}, methods="GET", name="vocabulary_index_paginated")
     */
    public function index(VocabularyRepository $vocabularyRepository, int $page): Response
    {
        $latestWords = $vocabularyRepository->findLatest($page);

        $count = $vocabularyRepository->createQueryBuilder('word')
            ->select('count(word.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('vocabulary/index.html.twig', [
            'paginator' => $latestWords,
            'info' => [
                'count' => $count,
            ]
        ]);
    }

    /**
     * @Route ("/generate", name="vocabulary_generate")
     */
    public function generate(Request $request): Response
    {
        $defaultData = ['hasDictionaryDef' => 'false'];
        $form = $this->createFormBuilder($defaultData)
            ->add('hasDictionaryDef', ChoiceType::class, ['choices' => ['true' => true, 'false' => false], 'data' => false])
            ->add('minCorpusCount', IntegerType::class, ['data' => 0])
            ->add('minLength', IntegerType::class, ['data' => 5])
            ->add('maxLength', IntegerType::class, ['data' => 15])
            ->add('limit', IntegerType::class, ['data' => 10])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();

            $url = "https://api.wordnik.com/v4/words.json/randomWords?" .
            "hasDictionaryDef=" . ($data['hasDictionaryDef'] ? 'true' : 'false') . "&" .
            "minCorpusCount=" . $data['minCorpusCount'] . "&" .
            "minLength=" . $data['minLength'] . "&" .
            "maxLength=" . $data['maxLength'] . "&" .
            "limit=" . $data['limit'] . "&" .
            "api_key=a2a73e7b926c924fad7001ca3111acd55af2ffabf50eb4ae5";

            if ($words = json_decode(file_get_contents($url), true)) {
                $em = $this->getDoctrine()->getManager();
                $batchSize = 20;

                foreach ($words as $i => $item) {
                    $product = new Vocabulary();
                    $product->setWord(preg_replace('/\PL/u', '', $item['word']));
                    $em->persist($product);

                    // flush everything to the database every 20 inserts
                    if (($i % $batchSize) == 0) {
                        $em->flush();
                        $em->clear();
                    }
                }

                // flush the remaining objects
                $em->flush();
                $em->clear();
            }

            return $this->redirectToRoute('vocabulary_index');
        }

        return $this->render('vocabulary/generate.html.twig',
            array(
                'form' => $form->createView(),
            )
        );
    }

    /**
     * @Route ("/clear", name="vocabulary_clear")
     */
    public function clear(): Response
    {

        $em = $this->getDoctrine()->getManager();

        $repository = $em->getRepository(Vocabulary::class);
        $entities = $repository->findAll();

        foreach ($entities as $entity) {
            $em->remove($entity);
        }
        $em->flush();
        $em->clear();

        return $this->redirectToRoute('vocabulary_index');
    }

    /**
     * @Route("/new", name="vocabulary_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $vocabulary = new Vocabulary();
        $form = $this->createForm(VocabularyType::class, $vocabulary);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($vocabulary);
            $entityManager->flush();

            return $this->redirectToRoute('vocabulary_index');
        }

        return $this->render('vocabulary/new.html.twig', [
            'vocabulary' => $vocabulary,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/show/{id}", name="vocabulary_show", methods={"GET"})
     */
    public function show(Vocabulary $vocabulary): Response
    {
        return $this->render('vocabulary/show.html.twig', [
            'vocabulary' => $vocabulary,
        ]);
    }

    /**
     * @Route("/edit/{id}", name="vocabulary_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Vocabulary $vocabulary): Response
    {
        $form = $this->createForm(VocabularyType::class, $vocabulary);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('vocabulary_index');
        }

        return $this->render('vocabulary/edit.html.twig', [
            'vocabulary' => $vocabulary,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/delete/{id}", name="vocabulary_delete", methods={"GET","POST"})
     */
    public function delete(Request $request, Vocabulary $vocabulary, int $id): Response
    {
        if ($this->isCsrfTokenValid('delete' . $vocabulary->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($vocabulary);
            $entityManager->flush();
        } elseif (isset($id)) {
            $entityManager = $this->getDoctrine()->getManager();
            $word = $entityManager
                ->getRepository(Vocabulary::class)
                ->find($id);

            if (!$id) {
                throw $this->createNotFoundException('word not found');
            }

            $entityManager->remove($word);
            $entityManager->flush();
        }
        return $this->redirectToRoute('vocabulary_index');
    }
}
