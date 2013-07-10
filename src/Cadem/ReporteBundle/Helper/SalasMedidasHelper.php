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
	protected $clienteHelper;
	private $total_salas_name;
	private $salasmedidas = null;
	private $totalsalas = null;	

    public function __construct(EntityManager $entityManager, SecurityContext $security, MedicionHelper $medicion, $total_salas_name, ClienteHelper $clienteHelper) {
        $this->em = $entityManager;
		$this->security = $security;
		$this->medicion = $medicion;
		$this->clienteHelper = $clienteHelper;

		if($security->getToken() != null) $this->user = $security->getToken()->getUser();
		else $this->user = null;
		$this->total_salas_name = $total_salas_name;
    }

    //MUESTRA INDICADOR DE SALAS MEDIDAS
    //POR DEFECTO NO SE MUESTRA
	public function MuestraSalasMedidas() {
		return $this->clienteHelper->MuestraSalasMedidas();
	}

    private function getSalasmedidas_() {
		$em = $this->em;
		$user = $this->user;
		$id_cliente = $user->getClienteID();
		
		$variables = array_map('strtoupper', $this->clienteHelper->getVariables());
				
		$sql = "SELECT COUNT(DISTINCT A.ID) as numsalas FROM (";

		$haymedicion = true;

		foreach($variables as $variable)
		{							
			$id_ultima_medicion = $this->medicion->getIdUltimaMedicionPorVariable($variable);								
		
			if($id_ultima_medicion !== -1){
				$letravar = substr($variable,0,1);
				$letravar = $variable=='PRESENCIA'?'Q':$letravar;
				$variable = $variable=='PRESENCIA'?'QUIEBRE':$variable;

				$sql .= "(SELECT s.ID FROM PLANOGRAMA{$letravar} p
						INNER JOIN {$variable} q on p.ID = q.PLANOGRAMA{$letravar}_ID AND p.MEDICION_ID = {$id_ultima_medicion}
						INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID AND sc.CLIENTE_ID = {$id_cliente}
						INNER JOIN SALA s on s.ID = sc.SALA_ID
	                    GROUP BY s.ID) UNION ";
			}
			else{
				$haymedicion = false;//NO HAY DATOS
				break;
			}

		}

		if($haymedicion){
			$sql = substr($sql, 0, -6);
			$sql .= ") as A ";
			$query = $em->getConnection()->executeQuery($sql)->fetchAll();
            if(isset($query[0]) && $query[0]['numsalas'] === (string) intval($query[0]['numsalas'])) $total = intval($query[0]['numsalas']);
            else $total = -1;
			$this->salasmedidas = $total;
		}
		else $this->salasmedidas = -1;
			
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