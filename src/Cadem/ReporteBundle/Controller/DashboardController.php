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
		$id_ultima_medicion = $this->get('cadem_reporte.helper.medicion')->getIdUltimaMedicion();
		
		if($id_ultima_medicion !== -1){
		
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
		
		
		
		
		$response = new JsonResponse($response);
		
		//CACHE
		$response->setPrivate();
		$response->setMaxAge(1);


		return $response;
    }
}
