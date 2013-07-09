<?php
namespace Cadem\ReporteBundle\Helper;

// use Doctrine\ORM\EntityManager;
// use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MedicionHelper {

 //    protected $em;
	// protected $security;
	// protected $user;
	private $container;
	private $id_ultima_medicion = null;
	private $id_medicion_anterior = null;
	private $nombre_medicion;

    public function __construct(ContainerInterface $container) {
		$this->container = $container;
		// if($security->getToken() != null) $this->user = $security->getToken()->getUser();
		// else $this->user = null;
    }

    private function getNombreMedicion_($id_medicion) {
		$em = $this->container->get('doctrine.orm.entity_manager');
		$security = $this->container->get('security.context');
		if($security->getToken() != null) $user = $security->getToken()->getUser();
		$id_user = $user->getId();
		$id_cliente = $user->getClienteID();
		
		//MEDICION
		$query = $em->createQuery(
			'SELECT m.nombre FROM CademReporteBundle:Medicion m
			JOIN m.estudiovariable ev
			JOIN ev.estudio e
			WHERE e.clienteid = :idcliente AND m.id = :idmedicion')
			->setParameter('idcliente', $id_cliente)
			->setParameter('idmedicion', $id_medicion);
		$medicion_q = $query->getArrayResult();
		if(count($medicion_q) > 0){
			$nombre_medicion = $medicion_q[0]['nombre'];
		}
		else $nombre_medicion = 'null';

		$this->nombre_medicion = $nombre_medicion;
		return $this->nombre_medicion;
    }

    private function getIdUltimaMedicion_() {
		$em = $this->container->get('doctrine.orm.entity_manager');
		$security = $this->container->get('security.context');
		if($security->getToken() != null) $user = $security->getToken()->getUser();
		$id_user = $user->getId();
		$id_cliente = $user->getClienteID();
		$request = $this->container->get('request');
		$variable = $request->attributes->get('variable');
		
		//ULTIMA MEDICION
		$query = $em->createQuery(
			"SELECT m.id FROM CademReporteBundle:Medicion m
			JOIN m.estudiovariable ev
			JOIN ev.estudio e
			JOIN ev.variable v
			WHERE e.clienteid = :idcliente AND v.nombre = :variable
			ORDER BY m.fechainicio DESC")
			->setParameter('idcliente', $id_cliente)
			->setParameter('variable', $variable);
		$medicion_q = $query->getArrayResult();
		if(count($medicion_q) > 0){
			$id_ultima_medicion = $medicion_q[0]['id'];
		}
		else $id_ultima_medicion = -1;

		$this->id_ultima_medicion = $id_ultima_medicion;
		return $this->id_ultima_medicion;
    }
	
	private function getIdUltimaMedicionPorVariable_($variable) {
		$em = $this->container->get('doctrine.orm.entity_manager');
		$security = $this->container->get('security.context');
		if($security->getToken() != null) $user = $security->getToken()->getUser();
		$id_user = $user->getId();
		$id_cliente = $user->getClienteID();		
		
		//ULTIMA MEDICION
		$query = $em->createQuery(
			"SELECT m.id FROM CademReporteBundle:Medicion m
			JOIN m.estudiovariable ev
			JOIN ev.estudio e
			JOIN ev.variable v
			WHERE e.clienteid = :idcliente AND v.nombre = :variable
			ORDER BY m.fechainicio DESC")
			->setParameter('idcliente', $id_cliente)
			->setParameter('variable', strtolower($variable));
		$medicion_q = $query->getArrayResult();
		if(count($medicion_q) > 0){
			$id_ultima_medicion = $medicion_q[0]['id'];
		}
		else $id_ultima_medicion = -1;

		$this->id_ultima_medicion = $id_ultima_medicion;
		return $this->id_ultima_medicion;
    }	

    private function getIdMedicionAnterior_($id_medicion_actual) {
		$em = $this->container->get('doctrine.orm.entity_manager');
		$security = $this->container->get('security.context');
		if($security->getToken() != null) $user = $security->getToken()->getUser();
		$id_user = $user->getId();
		$id_cliente = $user->getClienteID();
		
		//SE BUSCA MEDICION ANTERIOR
		$query = $em->createQuery(
			'SELECT m.id FROM CademReporteBundle:Medicion m
			JOIN m.estudiovariable ev
			JOIN ev.estudio e
			JOIN e.cliente c
			WHERE c.id = :idc
			ORDER BY m.fechainicio DESC')
			->setParameter('idc', $id_cliente);
		$mediciones = $query->getArrayResult();
		$listo = false;
		if(count($mediciones) > 1){
			foreach($mediciones as $m)
			{
				if($listo)
				{
					$id_medicion_anterior = $m['id'];
					break;
				}
				if($m['id'] === $id_medicion_actual) $listo = true;
			}
			if($listo === false) $id_medicion_anterior = $id_medicion_actual;
		}
		else if(count($mediciones) == 1) $id_medicion_anterior = $id_medicion_actual;//SOLO HAY UNA MEDICION
		else $id_medicion_anterior = -1;//NO HAY MEDICIONES
		
		$this->id_medicion_anterior = $id_medicion_anterior;
		return $this->id_medicion_anterior;
    }
	
	public function getNombreMedicion($id_medicion){
		if($this->nombre_medicion !== null) return $this->nombre_medicion;
		else  return $this->getNombreMedicion_($id_medicion);
	}

	public function getIdUltimaMedicion(){
		if($this->id_ultima_medicion !== null) return $this->id_ultima_medicion;
		else  return $this->getIdUltimaMedicion_();
	}
	
	public function getIdUltimaMedicionPorVariable($variable){
		// if($this->id_ultima_medicion !== null) return $this->id_ultima_medicion;
		// else 
		return $this->getIdUltimaMedicionPorVariable_($variable);
	}

	public function getIdMedicionAnterior($id_medicion_actual){
		if($this->id_medicion_anterior !== null) return $this->id_medicion_anterior;
		else  return $this->getIdMedicionAnterior_($id_medicion_actual);
	}
}