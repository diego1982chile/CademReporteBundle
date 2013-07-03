<?php

namespace Cadem\ReporteBundle\EventListener;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\SecurityContext;
use Cadem\ReporteBundle\Helper\ClienteHelper;

class RequestListener
{
    protected $router;
	protected $security;
	protected $clienteHelper;
 
	public function __construct(Router $router, SecurityContext $security, ClienteHelper $clienteHelper) {
		$this->router = $router;
		$this->security = $security;
		$this->clienteHelper = $clienteHelper;
	}
        
    public function onKernelRequest(GetResponseEvent $event)
    {
        
		if (HttpKernel::MASTER_REQUEST != $event->getRequestType()) {
				// don't do anything if it's not the master request
				return;
		}

		$request = $event->getRequest();
		$routeName = $request->attributes->get('_route');
		$variable = $request->attributes->has('variable')?$request->attributes->get('variable'):null;
		if($routeName === "fos_user_security_login" && $this->security->isGranted('ROLE_ADMIN')){
			$url = $this->router->generate('admin_carga_item');
			$event->setResponse(new RedirectResponse($url));
		}
		else if($routeName === "fos_user_security_login" && $this->security->isGranted('ROLE_USER')){
			
			$url = $this->router->generate('dashboard_index');
			$event->setResponse(new RedirectResponse($url));
		}
		else if ($variable !== null && in_array($variable, array('quiebre','precio','presencia')) && $this->security->isGranted('ROLE_USER')) {
			$variables = array_map('strtolower', $this->clienteHelper->getVariables());
			if (!in_array($variable, $variables)) $event->setResponse(new RedirectResponse($this->router->generate('dashboard_index')));
		}
    }
}