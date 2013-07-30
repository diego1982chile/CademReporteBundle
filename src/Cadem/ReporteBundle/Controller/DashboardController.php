<?php

namespace Cadem\ReporteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class DashboardController extends Controller
{
    
	public function indexAction()
    {
		$user = $this->getUser();
		$em = $this->getDoctrine()->getManager();
		$id_cliente = $user->getClienteID();
		
		//CLIENTE, ESTUDIO
		$query = $em->createQuery(
			'SELECT c,e,ev,v FROM CademReporteBundle:Cliente c
			JOIN c.estudios e
			JOIN e.estudiovariables ev
			JOIN ev.variable v
			WHERE c.id = :id AND c.activo = 1 AND e.activo = 1')
			->setParameter('id', $id_cliente);
		$clientes = $query->getResult();
		$cliente = $clientes[0];
		$estudios = $cliente->getEstudios();
		$choices_estudio = array('0' => 'TODOS');
		foreach($estudios as $e)
		{
			$choices_estudio[$e->getId()] = strtoupper($e->getNombre());
		}				
		
		$defaultData = array();
		$form_estudio = $this->createFormBuilder($defaultData)
			->add('Estudio', 'choice', array(
				'choices'   => $choices_estudio,
				'required'  => true,
				'multiple'  => false,
				'data' => '0'			
			))
			->getForm();
		
		$logofilename = $cliente->getLogofilename();
		$logostyle = $cliente->getLogostyle();
		
		$variables = array_map('strtoupper', $this->get('cadem_reporte.helper.cliente')->getVariables());
		//ULTIMA MEDICION	
						
		foreach($variables as $variable)
		{													
			$id_ultima_medicion = $this->get('cadem_reporte.helper.medicion')->getIdUltimaMedicionPorVariable($variable);

			if($id_ultima_medicion !== -1){								
		
				switch($variable)
				{
					case 'QUIEBRE':
					case 'PRESENCIA':
						//QUIEBRE ULTIMA MEDICION			
						$sql = "SELECT (SUM(case when q.HAYQUIEBRE = 1 then 1 else 0 END)*100.0)/COUNT(q.ID) as porc_quiebre FROM QUIEBRE q
								INNER JOIN PLANOGRAMAQ p on p.ID = q.PLANOGRAMAQ_ID
								INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID
								WHERE sc.CLIENTE_ID = ? AND p.MEDICION_ID = ?";
						$param = array($id_cliente, $id_ultima_medicion);
						$tipo_param = array(\PDO::PARAM_INT, \PDO::PARAM_INT);
						$query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

						if(isset($query[0])){
							$porc_quiebre = $query[0]['porc_quiebre'];
							$porc_quiebre = round($porc_quiebre,1);
						}
						else $porc_quiebre = 0;
						$indicadores[$variable] = $porc_quiebre;
						$rango_quiebre = $this->get('cadem_reporte.helper.cliente')->getRangoQuiebre();
						$indicadores['rango_quiebre'] = $rango_quiebre;


						// OBTENER PORCENTAJE QUIEBRE POR SALA Y SUS COORDENADAS RESPECTIVAS
						$sql = "SELECT s.ID, s.LATITUD as lat, s.LONGITUD AS lon, c.NOMBRE as cadena, s.CALLE as calle, (SUM(case when q.hayquiebre = 1 then 1 else 0 END)*100.0)/COUNT(q.id) as quiebre FROM QUIEBRE q 
								INNER JOIN PLANOGRAMAQ p on p.ID = q.PLANOGRAMAQ_ID 
								INNER JOIN MEDICION m on m.ID = p.MEDICION_ID and m.ID = {$id_ultima_medicion}
								INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID 
								INNER JOIN SALA s on s.ID = sc.SALA_ID 
								INNER JOIN CADENA c on c.ID = s.CADENA_ID
								GROUP BY s.ID, s.LATITUD, s.LONGITUD, c.NOMBRE, s.CALLE";
							
						$query_map = $em->getConnection()->executeQuery($sql)->fetchAll();




						break;
					case 'PRECIO':
						//PRECIO INCUMPLIMIENTO ULTIMA MEDICION			
						$sql = "SELECT (SUM(case when ABS(pr.PRECIO-p.POLITICAPRECIO)>pa.VALOR*p.POLITICAPRECIO/100 then 1 else 0 END)*100.0)/COUNT(pr.ID) as porc_incumplimiento FROM PRECIO pr
								INNER JOIN PLANOGRAMAP p on p.ID = pr.PLANOGRAMAP_ID AND pr.PRECIO IS NOT NULL AND p.POLITICAPRECIO IS NOT NULL
								INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID
								INNER JOIN PARAMETRO pa on pa.CLIENTE_ID = ? and pa.NOMBRE='rango_precio'
								WHERE sc.CLIENTE_ID = ? AND p.MEDICION_ID = ?";
						$param = array($id_cliente,$id_cliente, $id_ultima_medicion);
						$tipo_param = array(\PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT);
						$query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

						if(isset($query[0])){
							$porc_incumplimiento = $query[0]['porc_incumplimiento'];
							$porc_incumplimiento = round($porc_incumplimiento,1);
						}
						else $porc_incumplimiento = 0;
						$indicadores[$variable] = $porc_incumplimiento;
						//RANGO DE PRECIO
						$rango_precio = $this->get('cadem_reporte.helper.cliente')->getRangoPrecio();
						$indicadores['rango_precio'] = $rango_precio;
						break;
				}			
			}			
		}
		
		

						
		//NOTICIAS
		$query = $em->createQuery(
			'SELECT n FROM CademReporteBundle:Noticia n
			WHERE n.clienteid = :idcliente and n.activo = 1')
			->setParameter('idcliente', $id_cliente);
		$noticias = $query->getArrayResult();
		
		//RESPONSE
		$response = $this->render('CademReporteBundle:Dashboard:index.html.twig',
		array(
			'forms' => array(
				'form_estudio' => $form_estudio->createView(),
			),
			'logofilename' => $logofilename,
			'logostyle' => $logostyle,
			'indicadores' => $indicadores,
			'estudios' => $estudios,
			'variables' => $variables,
			'noticias' => $noticias,
			'query_map' => json_encode($query_map),
			'prefixe_tag_variable' => 'Incump.',
		));

		//CACHE
		$response->setPrivate();
		$response->setMaxAge(1);


		return $response;
    }
	
	public function indicadoresAction(Request $request)
    {
		$data = $request->query->all();

		$em = $this->getDoctrine()->getManager();
		$user = $this->getUser();
		$id_cliente = $user->getClienteID();

		$start = microtime(true);
		$evolutivo = array();

		$variables = array_map('strtoupper', $this->get('cadem_reporte.helper.cliente')->getVariables());
		//EVOLUTIVO
		if(in_array("QUIEBRE", $variables) || in_array("PRESENCIA", $variables)){
			$sql = "SELECT TOP(12) (SUM(case when q.HAYQUIEBRE = 1 then 1 else 0 END)*1.0)/COUNT(q.ID) as QUIEBRE, m.NOMBRE, m.FECHAINICIO, m.FECHAFIN, m.ID FROM QUIEBRE q
					INNER JOIN PLANOGRAMAQ p on p.ID = q.PLANOGRAMAQ_ID
					INNER JOIN MEDICION m on m.ID = p.MEDICION_ID
					INNER JOIN ESTUDIOVARIABLE ev on ev.ID = m.ESTUDIOVARIABLE_ID
					INNER JOIN ESTUDIO e on e.ID = ev.ESTUDIO_ID AND e.CLIENTE_ID = ?
					
					GROUP BY m.FECHAINICIO, m.NOMBRE, m.FECHAINICIO, m.FECHAFIN, m.ID
					ORDER BY m.FECHAINICIO DESC";
			$param = array($id_cliente);
			$tipo_param = array(\PDO::PARAM_INT);
			$query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();
			$mediciones_q = array_reverse($query);

			$mediciones_data = array();
			$mediciones_tooltip = array();
			$porc_quiebre = array();

			foreach($mediciones_q as $m){
				$fi = new \DateTime($m['FECHAINICIO']);
				$ff = new \DateTime($m['FECHAFIN']);
				$mediciones_data[] = $fi->format('d/m').'-'.$ff->format('d/m');
				$mediciones_tooltip[] = $m['NOMBRE'];			
				$porc_quiebre[] = $m['QUIEBRE'] !== null?round($m['QUIEBRE']*100,1):null;			
			}

			$evolutivo['mediciones'] = $mediciones_data;
			$evolutivo['mediciones_tooltip'] = $mediciones_tooltip;
			$evolutivo[in_array("QUIEBRE", $variables)?'serie_quiebre':'serie_presencia'] = array(
												'name' => in_array("QUIEBRE", $variables)?'% '.current(explode(' ',$this->get('cadem_reporte.helper.cliente')->getTagVariable('quiebre'))):'% '.current(explode(' ',$this->get('cadem_reporte.helper.cliente')->getTagVariable('presencia'))),
												'color' => '#4572A7',
												'type' => 'spline',
												'data' => $porc_quiebre,
												'tooltip' => array(
													'valueSuffix' => ' %'
												)
											);
		}

		if(in_array("PRECIO", $variables)){
			$sql = "SELECT TOP(12) * FROM 
					(SELECT m.NOMBRE, m.FECHAINICIO, m.FECHAFIN FROM MEDICION m
					INNER JOIN ESTUDIOVARIABLE ev on ev.ID = m.ESTUDIOVARIABLE_ID
					INNER JOIN ESTUDIO e on e.ID = ev.ESTUDIO_ID AND e.CLIENTE_ID = :id_cliente

					GROUP BY m.NOMBRE, m.FECHAINICIO, m.FECHAFIN) as A LEFT JOIN

					(SELECT (SUM(case when ABS(pr.PRECIO-p.POLITICAPRECIO)>pa.VALOR*p.POLITICAPRECIO/100 then 1 else 0 END)*100.0)/COUNT(pr.ID) as porc_incumplimiento, m.NOMBRE as NOMBRE2 FROM PRECIO pr
					INNER JOIN PLANOGRAMAP p on p.ID = pr.PLANOGRAMAP_ID AND pr.PRECIO IS NOT NULL AND p.POLITICAPRECIO IS NOT NULL
					INNER JOIN MEDICION m on m.ID = p.MEDICION_ID
					INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID AND sc.CLIENTE_ID = :id_cliente 
					INNER JOIN PARAMETRO pa on pa.CLIENTE_ID = :id_cliente and pa.NOMBRE='rango_precio'
					GROUP BY m.NOMBRE
					) as B on A.NOMBRE = B.NOMBRE2 ORDER BY FECHAINICIO DESC";
			$param = array('id_cliente' => $id_cliente);
			$tipo_param = array(\PDO::PARAM_INT);
			$query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();
			$mediciones_q = array_reverse($query);

			$porc_precio = array();

			foreach($mediciones_q as $m){
				$fi = new \DateTime($m['FECHAINICIO']);
				$ff = new \DateTime($m['FECHAFIN']);
				$mediciones_data[] = $fi->format('d/m').'-'.$ff->format('d/m');
				$mediciones_tooltip[] = $m['NOMBRE'];
				$porc_incumplimiento[] = $m['porc_incumplimiento'] !== null?round($m['porc_incumplimiento'],1):null;	
			}

			$evolutivo['serie_precio'] = array(
												'name' => '% Incumplimiento '.current(explode(' ',$this->get('cadem_reporte.helper.cliente')->getTagVariable('precio'))),
												'color' => 'red',
												'type' => 'spline',
												'data' => $porc_incumplimiento,
												'tooltip' => array(
													'valueSuffix' => ' %'
												)
											);
		}

		$time_taken = microtime(true) - $start;
		
		
		
		$response = array(
			'evolutivo' => $evolutivo,
			'time_ms' => $time_taken*1000
		);
		
		
		
		
		$response = new JsonResponse($response);
		
		//CACHE
		$response->setPrivate();
		$response->setMaxAge(1);


		return $response;
    }

    public function mapAction(Request $request){
    	$start = microtime(true);
    	$data = $request->query->all();
		$em = $this->getDoctrine()->getManager();
		$user = $this->getUser();
		$id_cliente = $user->getClienteID();

		$variable = strtoupper($data['variable_map']);

		// OBTENER PORCENTAJE QUIEBRE POR SALA Y SUS COORDENADAS RESPECTIVAS
		$id_ultima_medicion = $this->get('cadem_reporte.helper.medicion')->getIdUltimaMedicionPorVariable($variable);

		switch ($variable) {
			case 'QUIEBRE':
			case 'PRESENCIA':
				$sql = "SELECT s.ID, s.LATITUD as lat, s.LONGITUD AS lon, c.NOMBRE as cadena, s.CALLE as calle, (SUM(case when q.hayquiebre = 1 then 1 else 0 END)*100.0)/COUNT(q.id) as quiebre FROM QUIEBRE q 
						INNER JOIN PLANOGRAMAQ p on p.ID = q.PLANOGRAMAQ_ID 
						INNER JOIN MEDICION m on m.ID = p.MEDICION_ID and m.ID = {$id_ultima_medicion}
						INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID 
						INNER JOIN SALA s on s.ID = sc.SALA_ID 
						INNER JOIN CADENA c on c.ID = s.CADENA_ID
						GROUP BY s.ID, s.LATITUD, s.LONGITUD, c.NOMBRE, s.CALLE";
				break;
			case 'PRECIO':
				$sql = "SELECT s.ID, s.LATITUD as lat, s.LONGITUD AS lon, c.NOMBRE as cadena, s.CALLE as calle, (SUM(case when ABS(pr.PRECIO-p.POLITICAPRECIO)>pa.VALOR*p.POLITICAPRECIO/100 then 1 else 0 END)*100.0)/COUNT(pr.ID) as porc_incumplimiento FROM PRECIO pr 
						INNER JOIN PLANOGRAMAP p on p.ID = pr.PLANOGRAMAP_ID AND pr.PRECIO IS NOT NULL AND p.POLITICAPRECIO IS NOT NULL
						INNER JOIN MEDICION m on m.ID = p.MEDICION_ID and m.ID = {$id_ultima_medicion}
						INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID 
						INNER JOIN SALA s on s.ID = sc.SALA_ID 
						INNER JOIN CADENA c on c.ID = s.CADENA_ID
						INNER JOIN PARAMETRO pa on pa.CLIENTE_ID = {$id_cliente} and pa.NOMBRE='rango_precio'
						GROUP BY s.ID, s.LATITUD, s.LONGITUD, c.NOMBRE, s.CALLE";
				break;
			
			default:
				return new JsonResponse(array('status' => false, 'mensaje' => 'NO SE ENCONTRO LA VARIABLE'));
				break;
		}

		$query_map = $em->getConnection()->executeQuery($sql)->fetchAll();


		$time_taken = microtime(true) - $start;
		$response = array(
			'status' => true,
			'query_map' => $query_map,
			'variable' => $variable,
			'time_ms' => $time_taken*1000
		);
		
		
		$response = new JsonResponse($response);
		
		//CACHE
		$response->setPrivate();
		$response->setMaxAge(1);
		return $response;
    }
}
