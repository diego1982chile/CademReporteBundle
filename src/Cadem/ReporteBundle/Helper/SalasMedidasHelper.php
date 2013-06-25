<?php
namespace Cadem\ReporteBundle\Helper;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\SecurityContext;
use Cadem\ReporteBundle\Helper\MedicionHelper;
// use Symfony\Component\HttpFoundation\Session\Session;

class SalasMedidasHelper {

    protected $em;
	protected $security;
	protected $user;
	protected $medicion;
	// protected $session; //SE USARA PARA GUARDAR LAS MEDICION ENTRE VISTAS
	private $salasmedidas = null;
	private $totalsalas = null;

    public function __construct(EntityManager $entityManager, SecurityContext $security, MedicionHelper $medicion) {
        $this->em = $entityManager;
		$this->security = $security;
		$this->medicion = $medicion;
		// $this->session = $session;
		if($security->getToken() != null) $this->user = $security->getToken()->getUser();
		else $this->user = null;
    }

    private function getSalasmedidas_() {
		$em = $this->em;
		$user = $this->user;
		$id_cliente = $user->getClienteID();
		
		$id_ultima_medicion = $this->medicion->getIdUltimaMedicion();
		if($id_ultima_medicion !== -1){
			//SALAS MEDIDAS
			$sql = "SELECT COUNT(p.ID) FROM PLANOGRAMAQ p
					INNER JOIN QUIEBRE q on p.ID = q.PLANOGRAMAQ_ID
					INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID
                    WHERE sc.CLIENTE_ID = ? AND p.MEDICION_ID = ?
                    GROUP BY sc.SALA_ID";
            $param = array($id_cliente, $id_ultima_medicion);
            $tipo_param = array(\PDO::PARAM_INT, \PDO::PARAM_INT);
            $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();
            if(isset($query[0])) $total = count($query);
            else $total = -1;



			// $query = $em->createQuery(
			// 	'SELECT COUNT(p) FROM CademReporteBundle:Planograma p
			// 	JOIN p.quiebres q
			// 	WHERE p.medicionid = :idmedicion
			// 	GROUP BY p.salaclienteid')
			// 	->setParameter('idmedicion', $id_ultima_medicion);
				
			// try {
			// 	$total = count($query->getArrayResult());
			// } catch (\Doctrine\ORM\NoResultException $e) {//SI NO HAY RESULTADOS PQ LA MEDICION NO TIENE QUIEBRES
			// 	$total = 0;
			// }
			
			$this->salasmedidas = $total;
		}
		else $this->salasmedidas = -1;//NO HAY DATOS

		return $this->salasmedidas;
    }
	
	private function getTotalsalas_() {
		$em = $this->em;
		$user = $this->user;
		$id_cliente = $user->getClienteID();
		
		$id_ultima_medicion = $this->medicion->getIdUltimaMedicion();
		if($id_ultima_medicion !== -1){
			//TOTAL DE SALAS
			$sql = "SELECT COUNT(p.SALACLIENTE_ID) FROM PLANOGRAMAQ p
					INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID
                    WHERE sc.CLIENTE_ID = ? AND p.MEDICION_ID = ?
                    GROUP BY sc.SALA_ID";
            $param = array($id_cliente, $id_ultima_medicion);
            $tipo_param = array(\PDO::PARAM_INT, \PDO::PARAM_INT);
            $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();
            if(isset($query[0])) $total = count($query);
            else $total = -1;

			// $query = $em->createQuery(
			// 	'SELECT COUNT(p.salaclienteid) FROM CademReporteBundle:Planograma p
			// 	WHERE p.medicionid = :idmedicion
			// 	GROUP BY p.salaclienteid')
			// 	->setParameter('idmedicion', $id_ultima_medicion);
			
			// try {
			// 	$total = count($query->getArrayResult());
			// } catch (\Doctrine\ORM\NoResultException $e) {//SI NO HAY RESULTADOS PQ LA MEDICION NO TIENE QUIEBRES
			// 	$total = 0;
			// }
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