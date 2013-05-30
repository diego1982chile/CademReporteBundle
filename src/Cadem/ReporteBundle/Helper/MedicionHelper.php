<?php
namespace Cadem\ReporteBundle\Helper;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\SecurityContext;

class MedicionHelper {

    protected $em;
	protected $security;
	protected $user;
	private $id_ultima_medicion = null;
	private $id_medicion_anterior = null;

    public function __construct(EntityManager $entityManager, SecurityContext $security) {
        $this->em = $entityManager;
		$this->security = $security;
		if($security->getToken() != null) $this->user = $security->getToken()->getUser();
		else $this->user = null;
    }

    private function getIdUltimaMedicion_() {
		$em = $this->em;
		$user = $this->user;
		$id_user = $user->getId();
		$id_cliente = $user->getClienteID();
		
		//ULTIMA MEDICION
		$query = $em->createQuery(
			'SELECT m.id FROM CademReporteBundle:Medicion m
			JOIN m.estudio e
			WHERE e.clienteid = :idcliente
			ORDER BY m.fechainicio DESC')
			->setParameter('idcliente', $id_cliente);
		$medicion_q = $query->getArrayResult();
		if(count($medicion_q) > 0){
			$id_ultima_medicion = $medicion_q[0]['id'];
		}
		else $id_ultima_medicion = -1;

		$this->id_ultima_medicion = $id_ultima_medicion;
		return $this->id_ultima_medicion;
    }

    private function getIdMedicionAnterior_($id_medicion_actual) {
		$em = $this->em;
		$user = $this->user;
		$id_user = $user->getId();
		$id_cliente = $user->getClienteID();
		
		//SE BUSCA MEDICION ANTERIOR
		$query = $em->createQuery(
			'SELECT m.id FROM CademReporteBundle:Medicion m
			JOIN m.estudio e
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
	
	public function getIdUltimaMedicion(){
		if($this->id_ultima_medicion !== null) return $this->id_ultima_medicion;
		else  return $this->getIdUltimaMedicion_();
	}

	public function getIdMedicionAnterior($id_medicion_actual){
		if($this->id_medicion_anterior !== null) return $this->id_medicion_anterior;
		else  return $this->getIdMedicionAnterior_($id_medicion_actual);
	}
}