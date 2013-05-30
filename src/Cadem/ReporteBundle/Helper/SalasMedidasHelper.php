<?php
namespace Cadem\ReporteBundle\Helper;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\SecurityContext;
// use Symfony\Component\HttpFoundation\Session\Session;

class SalasMedidasHelper {

    protected $em;
	protected $security;
	protected $user;
	// protected $session; //SE USARA PARA GUARDAR LAS MEDICION ENTRE VISTAS
	private $salasmedidas = null;
	private $totalsalas = null;

    public function __construct(EntityManager $entityManager, SecurityContext $security) {
        $this->em = $entityManager;
		$this->security = $security;
		// $this->session = $session;
		if($security->getToken() != null) $this->user = $security->getToken()->getUser();
		else $this->user = null;
    }

    private function getSalasmedidas_() {
		$em = $this->em;
		$user = $this->user;
		$id_user = $user->getId();
		
		//CLIENTE
		$query = $em->createQuery(
			'SELECT c FROM CademReporteBundle:Cliente c
			JOIN c.usuarios u
			WHERE u.id = :id AND c.activo = 1')
			->setParameter('id', $id_user);
		$clientes = $query->getResult();
		$cliente = $clientes[0];
		$id_cliente = $cliente->getId();
		
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
			//SALAS MEDIDAS
			$query = $em->createQuery(
				'SELECT COUNT(p) FROM CademReporteBundle:Planograma p
				JOIN p.quiebres q
				WHERE p.medicionid = :idmedicion
				GROUP BY p.salaclienteid')
				->setParameter('idmedicion', $id_ultima_medicion);
				
			try {
				$total = count($query->getArrayResult());
			} catch (\Doctrine\ORM\NoResultException $e) {//SI NO HAY RESULTADOS PQ LA MEDICION NO TIENE QUIEBRES
				$total = 0;
			}
			
			$this->salasmedidas = $total;
		}
		else $this->salasmedidas = -1;//NO HAY DATOS

		return $this->salasmedidas;
    }
	
	private function getTotalsalas_() {
		$em = $this->em;
		$user = $this->user;
		$id_user = $user->getId();
		
		//CLIENTE
		$query = $em->createQuery(
			'SELECT c FROM CademReporteBundle:Cliente c
			JOIN c.usuarios u
			WHERE u.id = :id AND c.activo = 1')
			->setParameter('id', $id_user);
		$clientes = $query->getResult();
		$cliente = $clientes[0];
		$id_cliente = $cliente->getId();
		
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
			//TOTAL DE SALAS
			$query = $em->createQuery(
				'SELECT COUNT(p.salaclienteid) FROM CademReporteBundle:Planograma p
				WHERE p.medicionid = :idmedicion
				GROUP BY p.salaclienteid')
				->setParameter('idmedicion', $id_ultima_medicion);
			
			try {
				$total = count($query->getArrayResult());
			} catch (\Doctrine\ORM\NoResultException $e) {//SI NO HAY RESULTADOS PQ LA MEDICION NO TIENE QUIEBRES
				$total = 0;
			}
		}
		else $this->totalsalas = -1;//NO HAY DATOS
		
		$this->totalsalas = $total;
		return $this->totalsalas;
    }
	
	public function getTotalsalas(){
		if($this->totalsalas !== null) return $this->totalsalas;
		else return $this->getTotalsalas_();
	}
	
	public function getSalasmedidas(){
		if($this->salasmedidas !== null) return $this->salasmedidas;
		else  return $this->getSalasmedidas_();
	}
	
	public function getPorcentaje($dec = 1) {
		if($this->getTotalsalas() !== 0 && $this->getTotalsalas() !== -1 && $this->getSalasmedidas() !== -1) return round($this->getSalasmedidas()/$this->getTotalsalas()*100,$dec);
		else return -1;
    }
}