<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use App\Entity\User;

class ApiController extends AbstractController
{
    /**
     * curl -X POST -H "Content-Type: application/json" localhost/api/login -d '{"email":"test@test.test","password":"123123"}'
     *
     * @Route("/api/login", name="api_login")
     */
    public function login(#[CurrentUser] ?User $user, Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
		if ($request) {

			$request = json_decode($request->getContent());
			$userRepository = $entityManager->getRepository(User::class);

			if (isset($request->email) && isset($request->password))
			{
				$user = $userRepository->findBy(
					[
						'email' => $request->email,
						'password' => $request->password
					]
				);

				if ($user) {

					$token = "29373932d2bf3a7ae06472c3e42cb817"; //md5('asdasd123' . date('Y-m-d H:i:s'));
					return $this->json([
						'token' => $token
					]);
				}
			}
		}

	    return $this->json([
		    'message' => 'missing credentials',
	    ], Response::HTTP_UNAUTHORIZED);
    }

	/**
	 * curl -X POST -H "Content-Type: application/json" localhost/api/getMessages -d '{"token":"29373932d2bf3a7ae06472c3e42cb817"}'
	 *
	 * @Route("/api/getMessages", name="get_messages")
	 */
	public function getMessages(Request $request): JsonResponse
	{
		if ($request)
		{
			$request = json_decode($request->getContent());
			if (isset($request->token)) {
				$token = $request->token;
				if ($token == "29373932d2bf3a7ae06472c3e42cb817") {
					return $this->json([
						'message' => 'your messages',
					]);
				}
			}
		}

		return $this->json([
			'message' => 'missing credentials',
		], Response::HTTP_UNAUTHORIZED);
	}
}
