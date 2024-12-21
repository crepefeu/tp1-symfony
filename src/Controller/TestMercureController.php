<?php
// src/Controller/TestMercureController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class TestMercureController extends AbstractController
{
    #[Route('/test/publish', name: 'test_publish')]
    public function publish(HubInterface $hub): Response
    {
        $update = new Update(
            'test',
            json_encode(['message' => 'Hello Mercure!'])
        );
        
        $hub->publish($update);
        return new Response('Published!');
    }

    #[Route('/test/subscribe', name: 'test_subscribe')]
    public function subscribe(): Response
    {
        return $this->render('test/subscribe.html.twig');
    }
}