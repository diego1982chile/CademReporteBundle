<?php

namespace Cadem\ReporteBundle\EventListener;

use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LogoutHandler implements LogoutHandlerInterface
{
	
	public function __construct()
	{
	}
	
	public function logout(Request $request, Response $response, TokenInterface $token)
	{
		$request->getSession()->clear();
		$request->getSession()->invalidate();
	}
	
}