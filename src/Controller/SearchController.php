<?php

namespace App\Controller;

use App\Repository\TaskRepository;
use App\Service\ElasticSearchService;
use App\Service\MemcachedService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class SearchController extends AbstractController
{

    const SEARCH_DB_PREFIX = 'JUST_IN_DB_';
    const SEARCH_ELK_PREFIX = 'EL_DB_';

    public $isCached = '';

    /**
     * @Route("/search", name="search_index")
     * @throws \Symfony\Component\Cache\Exception\CacheException
     * @throws \ErrorException
     */
    public function index(Request $request, TaskRepository $taskRepository): Response
    {

        $form = $this->createFormBuilder()
            ->add('search_in_db', TextType::class, array('label' => 'Search with native sql query', 'required' => false))
            ->add('search_in_el', TextType::class, array('label' => 'Search with Elastic Search', 'required' => false))
            ->add('search_in_sphinx', TextType::class, array('label' => 'Search with Sphinx', 'required' => false))
            ->getForm();

        $form->handleRequest($request);

        $tasks = [];

        $duration = false;
        $cached = false;

        if ($form->isSubmitted() && $form->isValid()) {

            $data = $form->getData();

            if (strlen($data['search_in_db']) > 2) {

                $start = explode(' ', microtime())[0];
                $tasks = $this->getItemsFromDB($data['search_in_db'], $taskRepository);
                $end = explode(' ', microtime())[0];

                $duration = $end - $start;
            }

            if (strlen($data['search_in_el']) > 2) {

                $start = explode(' ', microtime())[0];
                $tasks = $this->getItemsFromElasticSearch($data['search_in_el'], $taskRepository);
                $end = explode(' ', microtime())[0];

                $duration = $end - $start;
            }

        }

        return $this->render('search/index.html.twig', [
            'form' => $form->createView(),
            'last' => $tasks,
            'duration' => $duration,
            'cached' => $this->isCached
        ]);
    }

    /**
     * @throws \ErrorException
     * @throws \Symfony\Component\Cache\Exception\CacheException
     */
    public function getItemsFromDB(string $searchString, TaskRepository $taskRepository)
    {
        $cache = new MemcachedService(SearchController::SEARCH_DB_PREFIX . $searchString);

        if ($cache->checkItemCacheKey()) {
            $items = $cache->getItemCacheKey();
        } else {
            $items = $taskRepository->findByFieldsInDB($searchString);
            $cache->saveCache($items);
        }

        $this->isCached = $cache->getIsCached();

        return $items;
    }

    /**
     * @throws \ErrorException
     * @throws \Symfony\Component\Cache\Exception\CacheException
     */
    public function getItemsFromElasticSearch(string $searchString, TaskRepository $taskRepository)
    {
        $elasticSearch = new ElasticSearchService($searchString);
        $cache = new MemcachedService(SearchController::SEARCH_ELK_PREFIX . $searchString);

        if ($cache->checkItemCacheKey()) {

            $items = $cache->getItemCacheKey();

        } else {

            if ($elasticSearch->dataInElkExists()){
                $items = $elasticSearch->getData();
            } else {
                $items = $elasticSearch
                    ->getDataFromTasksObjects(
                        $this
                            ->getItemsFromDB($searchString, $taskRepository)
                    );

                $elasticSearch->indexDate($items);
            }

            $cache->saveCache($items);

        }

        $this->isCached = $cache->getIsCached();

        return $items;
    }

}