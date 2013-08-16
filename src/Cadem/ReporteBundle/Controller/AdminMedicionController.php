<?php

namespace Cadem\ReporteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;


class AdminMedicionController extends Controller
{

	public function indexAction()
    {
		$em = $this->getDoctrine()->getManager();
		// CLIENTES
		$query = $em->createQuery(
			'SELECT c FROM CademReporteBundle:Cliente c ORDER BY c.nombrefantasia');			
		$clientesq = $query->getResult();						
		$clientes=array();
		
		foreach ($clientesq as $cliente)
		{
			$obj=array();
			$obj['id_cliente']=$cliente->getId();
			$obj['nombre_cliente']=$cliente->getNombrefantasia();
			array_push($clientes,$obj);			
		}				
		
		// ESTUDIOS
		$query = $em->createQuery(
			'SELECT e FROM CademReporteBundle:Estudio e ORDER BY e.nombre');			
		$estudiosq = $query->getResult();						
		$estudios=array();
		
		foreach ($estudiosq as $estudio)
		{			
			$obj=array();
			$obj['id_estudio']=$estudio->getId();
			$obj['id_cliente']=$estudio->getCliente()->getId();
			$obj['nombre_estudio']=$estudio->getNombre();
			array_push($estudios,$obj);
		}					

		// VARIABLES
		$query = $em->createQuery(
			'SELECT ev FROM CademReporteBundle:Estudiovariable ev ORDER BY ev.nombrevariable');			
		$variablesq = $query->getResult();						
		$variables=array();
		
		foreach ($variablesq as $variable)
		{			
			$obj=array();
			$obj['id_variable']=$variable->getId();
			$obj['id_estudio']=$variable->getEstudio()->getId();
			$obj['nombre_variable']=$variable->getNombreVariable();
			array_push($variables,$obj);
		}						
		
		//RESPONSE
		$response = $this->render('CademReporteBundle:Admin:adminmedicion.html.twig',
		array(			
			'clientes' => $clientes,			
			'estudios' => $estudios,
			'variables' => $variables
		));

		return $response;
	}
	
	public function cargarmedicionesAction(Request $request)
    {
		$em = $this->getDoctrine()->getManager();
		$parametros = $request->query->all();
		
		$cliente=$parametros['clientes'];
		$estudio=$parametros['estudios'];
		$variable=$parametros['variables'];
		
			
		$sql = "SELECT m.ID as id, m.NOMBRE as nombre, tm.NOMBRE as tipo, m.FECHAINICIO as fecha_inicio, m.FECHAFIN as fecha_fin FROM CLIENTE c
				INNER JOIN ESTUDIO e on c.ID = e.CLIENTE_ID AND c.NOMBREFANTASIA='{$cliente}' AND e.NOMBRE='{$estudio}'
				INNER JOIN ESTUDIOVARIABLE ev on e.ID = ev.ESTUDIO_ID and ev.nombrevariable = '{$variable}'												
				INNER JOIN MEDICION m on ev.ID = m.ESTUDIOVARIABLE_ID
				INNER JOIN TIPOMEDICION tm on m.TIPOMEDICION_ID = tm.ID
				ORDER BY m.FECHAINICIO";			
		
		$medicionesq = $em->getConnection()->executeQuery($sql)->fetchAll();									
		$aaData=array();
		
		foreach ($medicionesq as $medicion)
		{			
			$fila=array();
						
			$fila[0]="<label style='font-style:italic' id='{$medicion['id']}'>".$medicion['nombre']."</label>";
			$fila[1]=$medicion['tipo'];
			$fila[2]=date('d/m/Y',strtotime($medicion['fecha_inicio']));			
			$fila[3]=date('d/m/Y',strtotime($medicion['fecha_fin']));						
			$fila[4]="<button class='btn eliminar' type='button'><i class='icon-trash'></i></button><button class='btn editar' type='button'><i class='icon-pencil'></i></button>";
			array_push($aaData,$fila);						
		}											
		
		$output = array(			
			'aaData' => $aaData,					
		);		
		return new JsonResponse($output);				
	}
	
	public function actualizarmedicionAction(Request $request)
    {
		$em = $this->getDoctrine()->getManager();
		$parametros = $request->request->all();																
		
		$timezone = new \DateTimeZone("UTC");				
		
		$id=$parametros['id'];
		$estudiovariable_id=$parametros['estudiovariable'];
		$tipomedicion_id=$parametros['tipo'];
		$nombremedicion=$parametros['nombre'];
		$fechainicio=new \DateTime(str_replace('/','-',$parametros['inicio']),$timezone);
		$fechafin=new \DateTime(str_replace('/','-',$parametros['fin']),$timezone);					
		
		$row_affected = 0;
        $conn = $em->getConnection();
		
		$conn->beginTransaction(); 
		
		try{
			$start_insert = microtime(true);			
			
			$sql = "UPDATE MEDICION
				    SET				   
				    [TIPOMEDICION_ID] = ?
				   ,[NOMBRE] = ?
				   ,[FECHAINICIO] = ?
				   ,[FECHAFIN] = ?				   
				   WHERE 
				   [ID] = ?";				   			
					   
			$param = array($tipomedicion_id , $nombremedicion , $fechainicio->format("Y-m-d\TH:i:s") , $fechafin->format("Y-m-d\TH:i:s"), $id);				
			$tipo_param = array(
				\PDO::PARAM_INT,				
				\PDO::PARAM_STR,					
				\PDO::PARAM_STR,
				\PDO::PARAM_STR,
				\PDO::PARAM_INT
				);																						
				
			$row_affected += $conn->executeUpdate($sql,$param,$tipo_param);
			set_time_limit(10);
			$time_taken = microtime(true) - $start_insert;
			if($time_taken >= 600){
				$conn->rollback();
				return new JsonResponse(array(
					'status' => false,
					'mensaje' => 'TIEMPO EXCEDIDO ('.round($time_taken,1).' SEG) EN UPDATE. EL TIEMPO MAX ES DE 600 SEG. LO QUE DEBERIA ALCANZAR PARA PROCESAR APROX 45 MIL FILAS. SI SU ARCHIVO TIENE MAS, POR FAVOR SAQUE LAS SUFICIENTES FILAS.'
				));
			}			
			$conn->commit();
			
			$time_taken = microtime(true) - $start_insert;

			return new JsonResponse(array(
				'status' => true,
				'row_affected' => $row_affected,
				'time_taken' => $time_taken*1000
			));    
		} catch(Exception $e) {
			$conn->rollback();
			return new JsonResponse(array(
				'status' => false, 
				'mensaje' => 'ERROR EN EL UPDATE DE DATOS. NO SE INGRESO NADA'
			));
		}											
	}
	
	public function eliminarmedicionAction(Request $request)
    {
		$em = $this->getDoctrine()->getManager();
		$parametros = $request->request->all();																
		
		$timezone = new \DateTimeZone("UTC");				
		
		$id=$parametros['id'];
		
		$row_affected = 0;
        $conn = $em->getConnection();
		
		$conn->beginTransaction(); 
		
		try{
			$start_insert = microtime(true);			
			
			$sql = "DELETE MEDICION				    
					WHERE 
				    [ID] = ?";				   			
					   
			$param = array($id);				
			$tipo_param = array(
				\PDO::PARAM_INT,								
				);																						
				
			$row_affected += $conn->executeUpdate($sql,$param,$tipo_param);
			set_time_limit(10);
			$time_taken = microtime(true) - $start_insert;
			if($time_taken >= 600){
				$conn->rollback();
				return new JsonResponse(array(
					'status' => false,
					'mensaje' => 'TIEMPO EXCEDIDO ('.round($time_taken,1).' SEG) EN DELETE. EL TIEMPO MAX ES DE 600 SEG. LO QUE DEBERIA ALCANZAR PARA PROCESAR APROX 45 MIL FILAS. SI SU ARCHIVO TIENE MAS, POR FAVOR SAQUE LAS SUFICIENTES FILAS.'
				));
			}			
			$conn->commit();
			
			$time_taken = microtime(true) - $start_insert;

			return new JsonResponse(array(
				'status' => true,
				'row_affected' => $row_affected,
				'time_taken' => $time_taken*1000
			));    
		} catch(Exception $e) {
			$conn->rollback();
			return new JsonResponse(array(
				'status' => false, 
				'mensaje' => 'ERROR EN EL DELETE DE DATOS. NO SE INGRESO NADA'
			));
		}	
	}
	
	public function crearmedicionAction(Request $request)
    {
		$em = $this->getDoctrine()->getManager();
		$parametros = $request->request->all();																
		
		$timezone = new \DateTimeZone("UTC");
		
		$estudiovariable_id=$parametros['estudiovariable'];
		$tipomedicion_id=$parametros['tipo'];
		$nombremedicion=$parametros['nombre'];
		$fechainicio=new \DateTime($parametros['inicio'],$timezone);
		$fechafin=new \DateTime($parametros['fin'],$timezone);			
		
		$row_affected = 0;
        $conn = $em->getConnection();
		
		$conn->beginTransaction(); 

		//OBTENEMOS EL ULTIMO ID INGRESADO
		$sql = "SELECT TOP(1) m.ID as id FROM MEDICION m
				ORDER BY m.ID DESC";
		$query = $em->getConnection()->executeQuery($sql)->fetchAll();
		$id = (isset($query[0]))?intval($query[0]['id']):0;		
		$id++;

		try{
			$start_insert = microtime(true);			
			
			$sql = "INSERT INTO MEDICION
				   ([ID]
				   ,[ESTUDIOVARIABLE_ID]
				   ,[TIPOMEDICION_ID]
				   ,[NOMBRE]
				   ,[FECHAINICIO]
				   ,[FECHAFIN]
				   ,[ACTIVO])
			 VALUES
				   ( ? , ? , ? , ? , ? , ? , 1 )";				   			
					   
			$param = array($id, $estudiovariable_id , $tipomedicion_id , $nombremedicion , $fechainicio->format("Y-m-d\TH:i:s") , $fechafin->format("Y-m-d\TH:i:s"));				
			$tipo_param = array(
				\PDO::PARAM_INT,
				\PDO::PARAM_INT,
				\PDO::PARAM_INT,
				\PDO::PARAM_STR,					
				\PDO::PARAM_STR,
				\PDO::PARAM_STR
				);																						
				
			$row_affected += $conn->executeUpdate($sql,$param,$tipo_param);
			set_time_limit(10);
			$time_taken = microtime(true) - $start_insert;
			if($time_taken >= 600){
				$conn->rollback();
				return new JsonResponse(array(
					'status' => false,
					'mensaje' => 'TIEMPO EXCEDIDO ('.round($time_taken,1).' SEG) EN INSERT. EL TIEMPO MAX ES DE 600 SEG. LO QUE DEBERIA ALCANZAR PARA PROCESAR APROX 45 MIL FILAS. SI SU ARCHIVO TIENE MAS, POR FAVOR SAQUE LAS SUFICIENTES FILAS.'
				));
			}			
			$conn->commit();
			
			$time_taken = microtime(true) - $start_insert;

			return new JsonResponse(array(
				'status' => true,
				'row_affected' => $row_affected,
				'time_taken' => $time_taken*1000
			));    
		} catch(Exception $e) {
			$conn->rollback();
			return new JsonResponse(array(
				'status' => false, 
				'mensaje' => 'ERROR EN EL INSERT DE DATOS. NO SE INGRESO NADA'
			));
		}										
	}	
}
