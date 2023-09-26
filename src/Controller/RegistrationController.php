<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
	#[Route('/user/create', name: 'create_user')]
	public function index(EntityManagerInterface $entityManager): JsonResponse
	{
		$user = new User();
        $user->setPassword('123123');
		$user->setEmail('test@test.test');
		$user->setRoles((array)'ROLE_ADMIN');

		$entityManager->persist($user);
		$entityManager->flush();

		return $this->json([
			'OK'
		]);
    }
}