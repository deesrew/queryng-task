<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;

class MainController extends AbstractController
{
	private $cache;

	public function __construct(CacheInterface $cache)
	{
		$this->cache = $cache;
	}

    /**
     * @Route("/", name="main")
     */
    public function index(): Response
    {
	    // Кеширование повторяющейся тяжолой логики с помощью средств symfony
	    $this->cache->delete('app.current_simple_example_cache');
	    $simpleCacheExample = $this->cache->get('app.current_simple_example_cache', function () {
		    $string = '';
		    for ($i = 0;$i < 1000;$i++)
		    {
			    $string .= rand (1,100) . ' ';
			    if ($i % 10 == 0) $string .= "\n";
		    }

		    return $string;
	    });

        return $this->render('main/index.html.twig', [
            'controller_name' => 'MainController',
	        'simpleCacheExample' => $simpleCacheExample
        ])->setSharedMaxAge(20); // закешируем главную страницу на один час HTTP-кеширование
    }

	/**
	 * @Route("/getDateNow", name="datenow")
	 */
	public function getDateNow(): Response
	{
		return $this->render('main/date.html.twig', [
			'date' => date('Y-m-d H:i:s')
		]);
	}
}
