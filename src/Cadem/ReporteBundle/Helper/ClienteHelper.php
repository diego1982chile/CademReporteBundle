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
	private $rangoquiebre= null;
	private $muestrarankingempleado = null;
	private $muestrasalasmedidas = null;
	private $tagvariable = null;

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
	
	public function getTagVariable($variable) {
		
		$em = $this->em;
		if($this->user == null) $user = $this->security->getToken()->getUser();
		else $user = $this->user;
		$id_user = $user->getId();
		$id_cliente = $user->getClienteID();		
		
		// $query = $em->createQuery(
			// 'SELECT e,ev FROM CademReporteBundle:Estudio e
			// JOIN e.estudiovariables ev			
			// JOIN ev.variable v
			// WHERE e.clienteid = :id_cliente AND e.activo = 1 AND v.nombre= :variable')
			// ->setParameter('id_cliente', $id_cliente)
			// ->setParameter('variable', $variable);
		// $result = $query->getResult();			
		
		$sql =	"SELECT NOMBREVARIABLE as tag_variable FROM estudio e
		INNER JOIN estudiovariable ev on e.ID=ev.ESTUDIO_ID
		INNER JOIN variable v on ev.VARIABLE_ID=v.ID
		WHERE e.CLIENTE_ID = {$id_cliente} AND e.activo = 1 AND v.nombre= '{$variable}'";
			
		$tag_variable = $em->getConnection()->executeQuery($sql)->fetchAll();				
		
		if(count($tag_variable)>0)				
			$this->tagvariable=$tag_variable[0]['tag_variable'];											
		else		
			$this->tagvariable=$variable;
			
		return ucwords($this->tagvariable);
	}
	
	//OBTIENE EL RANGO DE TOLERANCIA PARA EL PRECIO Y POLITICA
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
		
		if(isset($rangoprecio_q[0])) $this->rangoprecio = intval($rangoprecio_q[0]['valor']);
		else $this->rangoprecio = 0;

		return $this->rangoprecio;
    }

    //OBTIENE EL RANGO DE TOLERANCIA PARA EL QUIEBRE
	public function getRangoQuiebre() {
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
			->setParameter('nombre', 'rango_quiebre');

		$query = $query->getArrayResult();
		
		if(isset($query[0])) $this->rangoquiebre = intval($query[0]['valor']);
		else $this->rangoquiebre = 0;

		return $this->rangoquiebre;
    }

    //RETORNA SI SE MUESTRA EL RANKING DE EMPLEADO PARA EL CLIENTE ACTUAL
    //POR DEFECTO SE MUESTRA
	public function MuestraRankingEmpleado() {
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
			->setParameter('nombre', 'muestra_ranking_empleado');

		$query = $query->getArrayResult();
		
		if(isset($query[0])) $this->muestrarankingempleado = $query[0]['valor'] === 'true'?true:false;
		else $this->muestrarankingempleado = true;

		return $this->muestrarankingempleado;
    }

    //MUESTRA INDICADOR DE SALAS MEDIDAS
    //POR DEFECTO NO SE MUESTRA
	public function MuestraSalasMedidas() {
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
			->setParameter('nombre', 'muestra_salas_medidas');

		$query = $query->getArrayResult();
		
		if(isset($query[0])) $this->muestrasalasmedidas = $query[0]['valor'] === 'true'?true:false;
		else $this->muestrasalasmedidas = false;

		return $this->muestrasalasmedidas;
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