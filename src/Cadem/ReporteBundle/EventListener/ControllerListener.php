<?php

namespace Cadem\ReporteBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\Controller\TraceableControllerResolver;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\SecurityContext;

use Cadem\ReporteBundle\Helper\ClienteHelper;

class ControllerListener
{
    protected $router;
	protected $security;
	protected $clienteHelper;
	protected $resolver;
 
	public function __construct(Router $router, SecurityContext $security, ClienteHelper $clienteHelper, TraceableControllerResolver $resolver) {
		$this->router = $router;		
		$this->security = $security;
		$this->clienteHelper = $clienteHelper;
		$this->resolver = $resolver;
	}
        
    public function onKernelController(FilterControllerEvent $event)
    {        	
		$request = $event->getRequest();
		
		$variable = explode('/',strtoupper($request->getPathInfo('_pattern')))[1];								
		
		if(!in_array($variable,['LOGIN','_WDT','DASHBOARD','H']))
		{
			$variables = $this->clienteHelper->getVariables();	
			if(!in_array($variable,$variables)){	
				$request->attributes->set('_controller', 'CademReporteBundle:Dashboard:index');
				// $request->attributes->set('_route', $this->router->generate('dashboard_index'));
				$event->setController($this->resolver->getController($request));						
			}
		}
    }
}