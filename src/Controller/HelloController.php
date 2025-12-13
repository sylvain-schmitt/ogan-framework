<?php

namespace App\Controller;

use Ogan\Controller\AbstractController;
use Ogan\Router\Attributes\Route;

class HelloController extends AbstractController
{
    #[Route(path: '/', methods: ['GET'], name: 'hello_index')]
    public function index()
    {
        return $this->render('hello/index.ogan', [
            'title' => 'Bienvenue'
        ]);
    }
}
