<?php

namespace Cadem\ReporteBundle\EventListener;

use Symfony\Component\Security\Http\Authentication\LogoutSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Router;

class LoginSuccessHandler implements LogoutSuccessHandlerInterface
{
	
	protected $router;
	protected $security;
	
	public function __construct(Router $router, SecurityContext $security)
	{
		$this->router = $router;
		$this->security = $security;
	}
	
	public function onLogoutSuccess(Request $request, TokenInterface $token)
	{
		
		if ($this->security->isGranted('ROLE_SUPER_ADMIN') || $this->security->isGranted('ROLE_USER'))
		{
			// $response = new RedirectResponse($this->router->generate('algo'));
			$response = new Response("NO PERMITIDO");
			//SE DESLOGEA
			$this->security->setToken(null);
			$request->getSession()->invalidate();
		}
		$referer_url = $this->router->generate('login');
						
		$response = new RedirectResponse($referer_url);
	
		return $response;
	}
	
}