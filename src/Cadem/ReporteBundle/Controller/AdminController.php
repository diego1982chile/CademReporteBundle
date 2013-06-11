<?php

namespace Cadem\ReporteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class AdminController extends Controller
{
    
	public function indexAction()
    {
		

		
		//RESPONSE
		$response = $this->render('CademReporteBundle:Admin:index.html.twig',
		array()
		);

		//CACHE
		$response->setPrivate();
		$response->setMaxAge(1);


		return $response;
    }
}
