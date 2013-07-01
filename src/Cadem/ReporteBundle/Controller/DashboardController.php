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
		
		//ULTIMA MEDICION
		$id_ultima_medicion = $this->get('cadem_reporte.helper.medicion')->getIdUltimaMedicion();
		
		if($id_ultima_medicion !== -1){
		
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
		}
		else $porc_quiebre = 0;
		
		//NOTICIAS
		$query = $em->createQuery(
			'SELECT n FROM CademReporteBundle:Noticia n
			WHERE n.clienteid = :idcliente and n.activo = 1')
			->setParameter('idcliente', $id_cliente);
		$noticias = $query->getArrayResult();

		//INDICADORES
		$indicadores = array('QUIEBRE' => $porc_quiebre, 'PRECIO' => 10, 'PRESENCIA' => 0);
		
		
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
			'noticias' => $noticias
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
		$start = microtime(true);//SE MIDE CUANTO SE DEMORAN LAS CONSULTAS Y PROCESAMIENTO
		$user = $this->getUser();
		$id_cliente = $user->getClienteID();
		
		//DATOS DEL EJE X EN EVOLUTIVO
		$sql = "SELECT TOP(12) m.NOMBRE, m.FECHAINICIO, m.FECHAFIN FROM MEDICION m
			INNER JOIN PLANOGRAMAQ p on p.MEDICION_ID = m.ID
			INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID AND sc.CLIENTE_ID = ?
			
			GROUP BY m.NOMBRE, m.FECHAINICIO, m.FECHAFIN
			ORDER BY m.FECHAINICIO DESC";
		$param = array($id_cliente);
		$tipo_param = array(\PDO::PARAM_INT);
		$mediciones_q = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();
		$mediciones_q = array_reverse($mediciones_q);
		
		foreach($mediciones_q as $m){
			$fi = new \DateTime($m['FECHAINICIO']);
			$ff = new \DateTime($m['FECHAFIN']);
			$mediciones_data[] = $fi->format('d/m').'-'.$ff->format('d/m');
			$mediciones_tooltip[] = $m['NOMBRE'];
		}
		
		//DATOS DEL EJE Y EN EVOLUTIVO
		$sql = "SELECT TOP(12) (SUM(case when q.HAYQUIEBRE = 1 then 1 else 0 END)*1.0)/COUNT(q.ID) as QUIEBRE FROM QUIEBRE q
			INNER JOIN PLANOGRAMAQ p on p.ID = q.PLANOGRAMAQ_ID
			INNER JOIN MEDICION m on m.ID = p.MEDICION_ID
			INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID AND sc.CLIENTE_ID = ?
			INNER JOIN SALA s on s.ID = sc.SALA_ID
			
			GROUP BY m.FECHAINICIO
			ORDER BY m.FECHAINICIO DESC";
		$param = array($id_cliente);
		$tipo_param = array(\PDO::PARAM_INT);
		$quiebres_q = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();
		$quiebres_q = array_reverse($quiebres_q);
		
		foreach ($quiebres_q as $q) $porc_quiebre[] = round($q['QUIEBRE']*100,1);
		
		$time_taken = microtime(true) - $start;
		
		$response = array(
			'evolutivo' => array(
				'mediciones' => $mediciones_data,
				'mediciones_tooltip' => $mediciones_tooltip,
				'serie_quiebre' => array(
					'name' => '% Quiebre',
					'color' => '#4572A7',
					'type' => 'spline',
					'data' => $porc_quiebre,
					'tooltip' => array(
						'valueSuffix' => ' %'
					)
				),
				'serie_precio' => array(
					'name' => '% Incumplimiento Precio',
					'color' => 'red',
					'type' => 'spline',
					'data' => array_reverse($porc_quiebre),
					'tooltip' => array(
						'valueSuffix' => ' %'
					)
				)
			),
			'time_ms' => $time_taken*1000
		);
		
		
		
		
		$response = new JsonResponse($response);
		
		//CACHE
		$response->setPrivate();
		$response->setMaxAge(1);


		return $response;
    }
}
