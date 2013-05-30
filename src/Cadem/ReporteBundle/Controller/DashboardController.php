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
		
		//CLIENTE, ESTUDIO
		$query = $em->createQuery(
			'SELECT c,e,ev,v FROM CademReporteBundle:Cliente c
			JOIN c.estudios e
			JOIN c.usuarios u
			JOIN e.estudiovariables ev
			JOIN ev.variable v
			WHERE u.id = :id AND c.activo = 1 AND e.activo = 1')
			->setParameter('id', $user->getId());
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
		$query = $em->createQuery(
			'SELECT m.id FROM CademReporteBundle:Medicion m
			JOIN m.estudio e
			WHERE e.clienteid = :idcliente
			ORDER BY m.fechainicio DESC')
			->setParameter('idcliente', $cliente->getId());
		$medicion_q = $query->getArrayResult();
		
		if(count($medicion_q) > 0){
			$id_ultima_medicion = $medicion_q[0]['id'];
		
			//QUIEBRE ULTIMA MEDICION
			$query = $em->createQuery(
				'SELECT (SUM(case when q.hayquiebre = 1 then 1 else 0 END)*100.0)/COUNT(q) FROM CademReporteBundle:Quiebre q
				JOIN q.planograma p
				JOIN p.salacliente sc
				WHERE sc.clienteid = :idcliente AND p.medicionid = :idmedicion')
				->setParameter('idcliente', $cliente->getId())
				->setParameter('idmedicion', $id_ultima_medicion);
			$quiebre = $query->getSingleScalarResult();
			$porc_quiebre = round($quiebre,1);
		}
		else $porc_quiebre = 0;
		
		
		
		
		//RESPONSE
		$response = $this->render('CademReporteBundle:Dashboard:index.html.twig',
		array(
			'forms' => array(
				'form_estudio' => $form_estudio->createView(),
			),
			'logofilename' => $logofilename,
			'logostyle' => $logostyle,
			'porc_quiebre' => $porc_quiebre,
			'estudios' => $estudios
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
		
		//medicion join estudio
		$query = $em->createQuery(
			'SELECT m.nombre, m.fechainicio, m.fechafin FROM CademReporteBundle:Medicion m
			JOIN m.estudio e
			JOIN e.cliente c
			JOIN c.usuarios u
			WHERE u.id = :id
			ORDER BY m.fechainicio DESC')
			->setMaxResults(12)
			->setParameter('id', $user->getId());
		$mediciones_q = $query->getArrayResult();
		$mediciones_q = array_reverse($mediciones_q);
		
		foreach($mediciones_q as $m){
			$mediciones[] = $m['fechainicio']->format('d/m').'-'.$m['fechafin']->format('d/m');
			$mediciones_tooltip[] = $m['nombre'];
		}
		
		//quiebre join salamedicion join medicion
		$query = $em->createQuery(
			'SELECT (SUM(case when q.hayquiebre = 1 then 1 else 0 END)*1.0)/COUNT(q.id) as quiebre FROM CademReporteBundle:Quiebre q
			JOIN q.salamedicion sm
			JOIN sm.medicion m
			JOIN m.estudio e
			JOIN e.cliente c
			JOIN c.usuarios u
			WHERE u.id = :id
			GROUP BY m.id, m.fechainicio
			ORDER BY m.fechainicio DESC')
			->setMaxResults(12)
			->setParameter('id', $user->getId());
		$quiebres = $query->getArrayResult();
		$quiebres = array_reverse($quiebres);
		
		foreach ($quiebres as $q) $porc_quiebre[] = round($q['quiebre']*100,1);
		
		$time_taken = microtime(true) - $start;
		
		$response = array(
			'evolutivo' => array(
				'mediciones' => $mediciones,
				'mediciones_tooltip' => $mediciones_tooltip,
				'serie_quiebre' => array(
					'name' => '% Quiebre',
					'color' => '#4572A7',
					'type' => 'spline',
					'data' => $porc_quiebre,
					'tooltip' => array(
						'valueSuffix' => ' %'
					)
				)
			),
			'time_ms' => $time_taken*1000
		);
		
		
		
		// $responseB = array( 
				// 'cobertura' =>	array(
					// 'type' => 'pie',
					// 'name' => 'Cobertura',
					// 'data' => array(
							// array('name' => 'Cumple', 'y' => 60, 'color' => '#83A931'),
							// array('name' => 'No cumple', 'y' => 40, 'color' => '#EB3737')
						// )
				// ),
				// 'atributo' =>	array(
					// 'type' => 'pie',
					// 'name' => 'Atributo',
					// 'data' => array(
							// array('name' => 'Cumple', 'y' => 15.5, 'color' => '#83A931'),
							// array('name' => 'No cumple', 'y' => 84.5, 'color' => '#EB3737')
						// )
				// ),
				// 'quiebre' =>	array(
					// 'type' => 'pie',
					// 'name' => 'Quiebre',
					// 'data' => array(
							// array('name' => 'Cumple', 'y' => 5, 'color' => '#83A931'),
							// array('name' => 'No cumple', 'y' => 95, 'color' => '#EB3737')
						// )
				// ),
				// 'precio' =>	array(
					// 'type' => 'pie',
					// 'name' => 'Presencia',
					// 'data' => array(
							// array('name' => 'Cumple', 'y' => 44.5, 'color' => '#83A931'),
							// array('name' => 'No cumple', 'y' => 55.5, 'color' => '#EB3737')
						// )
				// ),
				// 'evo_quiebre_precio' => array(
					// 'precio' => array(
						// 'name' => 'Promedio Precio',
						// 'color' => '#89A54E',
						// 'yAxis' => 1,
						// 'type' => 'spline',
						// 'data' => array(1300.0, 1100.9, 1000.5, 4490.5, 1889.2, 1198.5, 1500.2, 1612.5, 1332.3, 845.3, 1753.9, 1798.6),
						// 'tooltip' => array(
							// 'valuePrefix' => '$'
						// )
					// ),
					// 'quiebre' => array(
						// 'name' => '% Quiebre',
						// 'color' => '#4572A7',
						// 'type' => 'spline',
						// 'data' => array(73.0, 61.9, 20.5, 14.5, 18.2, 21.5, 25.2, 26.5, 23.3, 18.3, 13.9, 39.6),
						// 'tooltip' => array(
							// 'valueSuffix' => ' %'
						// )
					// )
				// ),
				// 'evo_cobertura' => array(
					// 'cobertura' => array(
						// 'name' => '% de Cobertura',
						// 'color' => '#4572A7',
						// 'type' => 'spline',
						// 'data' => array(73.0, 11.9, 20.5, 14.5, 18.2, 21.5, 25.2, 26.5, 23.3, 18.3, 13.9, 19.6),
						// 'tooltip' => array(
							// 'valueSuffix' => ' %'
						// )
					// )
				// )
		// );
		
		//RESPONSE
		// if('1' === $data['form']['Estudio']) $response = new JsonResponse($responseA);
		// else $response = new JsonResponse($responseB);
		$response = new JsonResponse($response);
		
		//CACHE
		$response->setPrivate();
		$response->setMaxAge(1);


		return $response;
    }
}
