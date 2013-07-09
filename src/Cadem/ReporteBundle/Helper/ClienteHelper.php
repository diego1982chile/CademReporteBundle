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
	private $rangoprecio= null;

    public function __construct(EntityManager $entityManager, SecurityContext $security) {
        $this->em = $entityManager;
		$this->security = $security;
		if($security->getToken() != null) $this->user = $security->getToken()->getUser();
		else $this->user = null;
    }

    public function getCantidadNiveles() {
		$em = $this->em;
		if($this->user == null) $user = $this->security->getToken()->getUser();
		else $user = $this->user;
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
		if($this->user == null) $user = $this->security->getToken()->getUser();
		else $user = $this->user;
		$id_user = $user->getId();
		$id_cliente = $user->getClienteID();
				
		//CLIENTE
		$query = $em->createQuery(
			'SELECT v.id, v.nombre FROM CademReporteBundle:variable v	
			JOIN v.estudiovariables ev
			JOIN ev.estudio e
			WHERE e.clienteid = :idcliente')
			->setParameter('idcliente', $id_cliente);

		$variables_q = $query->getArrayResult();
		
		if(count($variables_q) > 0){
			$this->variables=array();
			foreach($variables_q as $variable_q)
				array_push($this->variables,$variable_q['nombre']);			
		}
		return $this->variables;
    }
	
	public function getRangoPrecio() {
		$em = $this->em;
		if($this->user == null) $user = $this->security->getToken()->getUser();
		else $user = $this->user;
		$id_user = $user->getId();
		$id_cliente = $user->getClienteID();
				
		//CLIENTE
		$query = $em->createQuery(
			'SELECT p.valor FROM CademReporteBundle:parametro p			
			WHERE p.clienteid = :idcliente and p.nombre = :nombre ')
			->setParameter('idcliente', $id_cliente)
			->setParameter('nombre', 'rango_precio');

		$rangoprecio_q = $query->getArrayResult();
		
		if(count($rangoprecio_q) > 0){
			$this->rangoprecio=0;
			foreach($rangoprecio_q as $rangoprecioq)
				$this->rangoprecio= $rangoprecioq['valor'];			
		}
		return $this->rangoprecio;
    }
	

  //   public function getVariables1($id_cliente) {
		// $em = $this->em;
				
		// //CLIENTE
		// $query = $em->createQuery(
		// 	'SELECT v.id, v.nombre FROM CademReporteBundle:variable v	
		// 	JOIN v.estudiovariables ev
		// 	JOIN ev.estudio e
		// 	WHERE e.clienteid = :idcliente')
		// 	->setParameter('idcliente', $id_cliente);

		// $variables_q = $query->getArrayResult();
		
		// if(count($variables_q) > 0){
		// 	$this->variables=array();
		// 	foreach($variables_q as $variable_q)
		// 		array_push($this->variables,$variable_q['nombre']);			
		// }
		// return $this->variables;
  //   }


 //    public function __call($method_name, $arguments) {
 
	// 	//la lista de metodos sobrecargados
	// 	$accepted_methods = array("getVariables");
	// 	if(!in_array($method_name, $accepted_methods)) {
	//   		trigger_error("Metodo <strong>$method_name</strong> no existe", E_USER_ERROR);
	// 	}
 
	// 	//metodo sin argumentos
	// 	if(count($arguments) == 0) {
	// 	  $this->$method_name();
 
	// 	  //metodo con 1 argumento
	// 	} elseif(count($arguments) == 1) {
	// 	  $this->${$method_name.'1'}($arguments[0]);
 
	// 	  //metodo con 2 argumentos
	// 	// } elseif(count($arguments) == 2) {
	// 	//   $this->${$method_name.'2'}($arguments[0], $arguments[1]);
 
 
	// 	//error +de 2 parametros, metodo no definido
	// 	} else {
	// 	  return false;
	// 	}
	// }
}