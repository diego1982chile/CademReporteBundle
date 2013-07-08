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
	private $total_salas_name;
	private $salasmedidas = null;
	private $totalsalas = null;

    public function __construct(EntityManager $entityManager, SecurityContext $security, MedicionHelper $medicion, $total_salas_name) {
        $this->em = $entityManager;
		$this->security = $security;
		$this->medicion = $medicion;
		if($security->getToken() != null) $this->user = $security->getToken()->getUser();
		else $this->user = null;
		$this->total_salas_name = $total_salas_name;
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
			
			$this->salasmedidas = $total;
		}
		else $this->salasmedidas = -1;//NO HAY DATOS

		return $this->salasmedidas;
    }
	
	private function getTotalsalas_() {
		$em = $this->em;
		$user = $this->user;
		$id_cliente = $user->getClienteID();

		//TOTAL DE SALAS
		$sql = "SELECT TOP (1) 
				       VALOR
				  FROM PARAMETRO
				  WHERE CLIENTE_ID = ? AND NOMBRE = ?";
        $param = array($id_cliente, $this->total_salas_name);
        $tipo_param = array(\PDO::PARAM_INT, \PDO::PARAM_STR);
        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();
        if(isset($query[0])) $total = intval($query[0]['VALOR']);
        else $total = -1;

		
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