<?php
namespace Cadem\ReporteBundle\Helper;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\SecurityContext;

class ClienteHelper {

    protected $em;
	protected $security;
	protected $user;
	private $cantidadniveles = null;		
	private $variables= null;

    public function __construct(EntityManager $entityManager, SecurityContext $security) {
        $this->em = $entityManager;
		$this->security = $security;
		if($security->getToken() != null) $this->user = $security->getToken()->getUser();
		else $this->user = null;
    }

    public function getCantidadNiveles() {
		$em = $this->em;
		$user = $this->user;
		$id_user = $user->getId();
		$id_cliente = $user->getClienteID();
		
		//CLIENTE
		$query = $em->createQuery(
			'SELECT c.cantidadniveles FROM CademReporteBundle:Cliente c			
			WHERE c.id = :idcliente')
			->setParameter('idcliente', $id_cliente);

		$cantidadniveles_q = $query->getArrayResult();
		if(count($cantidadniveles_q) > 0){
			$this->cantidadniveles = $cantidadniveles_q[0]['cantidadniveles'];
		}		
		return $this->cantidadniveles;
    }
	
	public function getVariables() {
		$em = $this->em;
		$user = $this->user;
		// print_r($user);
		
		// if($user=='anon.')				
			// return array();
				
		$id_user = $user->getId();
		$id_cliente = $user->getClienteID();
				
		//CLIENTE
		$query = $em->createQuery(
			'SELECT v.id, v.nombre FROM CademReporteBundle:variable v	
			JOIN v.estudiovariables ev
			JOIN ev.estudio e
			JOIN e.cliente c
			WHERE c.id = :idcliente')
			->setParameter('idcliente', $id_cliente);

		$variables_q = $query->getArrayResult();
		
		if(count($variables_q) > 0){
			$this->variables=array();
			foreach($variables_q as $variable_q)
				array_push($this->variables,$variable_q['nombre']);			
		}		
		return $this->variables;
    }	
	
}