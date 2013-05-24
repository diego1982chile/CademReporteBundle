<?php

namespace Cadem\ReporteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\HttpFoundation\Session;


class ResumenController extends Controller
{		
	
	public function indexAction()
    {
		$session = $this->get("session");
	
		$user = $this->getUser();
		$em = $this->getDoctrine()->getManager();
		//CLIENTE Y ESTUDIO, LOGO
		$query = $em->createQuery(
			'SELECT c,e FROM CademReporteBundle:Cliente c
			JOIN c.estudios e
			JOIN c.usuarios u
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
		
		$logofilename = $cliente->getLogofilename();
		$logostyle = $cliente->getLogostyle();
		
		//REGIONES
		$query = $em->createQuery(
			'SELECT DISTINCT r FROM CademReporteBundle:Region r
			JOIN r.provincias p
			JOIN p.comunas c
			JOIN c.salas s
			JOIN s.salaclientes sc
			JOIN sc.cliente cl
			WHERE cl.id = :id')
			->setParameter('id', $cliente->getId());
		$regiones = $query->getResult();
		
		$choices_regiones = array();
		foreach($regiones as $r)
		{
			$choices_regiones[$r->getId()] = strtoupper($r->getNombre());
		}

		//PROVINCIA
		$query = $em->createQuery(
			'SELECT DISTINCT p FROM CademReporteBundle:Provincia p
			JOIN p.comunas c
			JOIN c.salas s
			JOIN s.salaclientes sc
			JOIN sc.cliente cl
			WHERE cl.id = :id')
			->setParameter('id', $cliente->getId());
		$provincias = $query->getResult();
		
		$choices_provincias = array();
		foreach($provincias as $r)
		{
			$choices_provincias[$r->getId()] = strtoupper($r->getNombre());
		}
		
		//COMUNA
		$query = $em->createQuery(
			'SELECT DISTINCT c FROM CademReporteBundle:Comuna c
			JOIN c.salas s
			JOIN s.salaclientes sc
			JOIN sc.cliente cl
			WHERE cl.id = :id')
			->setParameter('id', $cliente->getId());
		$comunas = $query->getResult();
		
		$choices_comunas = array();
		foreach($comunas as $r)
		{
			$choices_comunas[$r->getId()] = strtoupper($r->getNombre());
		}
		
		//MEDICION
		$query = $em->createQuery(
			'SELECT m.id, m.nombre FROM CademReporteBundle:Medicion m
			JOIN m.estudio e
			JOIN e.cliente c
			WHERE c.id = :id
			ORDER BY m.fechainicio DESC')
			->setParameter('id', $cliente->getId());
		$mediciones_q = $query->getArrayResult();
		
		foreach($mediciones_q as $m) $mediciones[$m['id']] = $m['nombre'];
		
		if(count($mediciones) > 0) $ultima_medicion = current(array_keys($mediciones));
		else $ultima_medicion = null;
		
		
		$form_estudio = $this->get('form.factory')->createNamedBuilder('f_estudio', 'form')
			->add('Estudio', 'choice', array(
				'choices'   => $choices_estudio,
				'required'  => true,
				'multiple'  => false,
				'data' => '0',
				'attr' => array('id' => 'myValue')
			))
			->getForm();
			
		$form_periodo = $this->get('form.factory')->createNamedBuilder('f_periodo', 'form')
			->add('Periodo', 'choice', array(
				'choices'   => $mediciones,
				'required'  => true,
				'multiple'  => false,
				'data' => $ultima_medicion			
			))
			->getForm();
			
		$form_region = $this->get('form.factory')->createNamedBuilder('f_region', 'form')
			->add('Region', 'choice', array(
				'choices'   => $choices_regiones,
				'required'  => true,
				'multiple'  => true,
				'data' => array_keys($choices_regiones)
			))
			->getForm();
			
		$form_provincia = $this->get('form.factory')->createNamedBuilder('f_provincia', 'form')
			->add('Provincia', 'choice', array(
				'choices'   => $choices_provincias,
				'required'  => true,
				'multiple'  => true,
				'data' => array_keys($choices_provincias)
			))
			->getForm();
			
		$form_comuna = $this->get('form.factory')->createNamedBuilder('f_comuna', 'form')
			->add('Comuna', 'choice', array(
				'choices'   => $choices_comunas,
				'required'  => true,
				'multiple'  => true,
				'data' => array_keys($choices_comunas)
			))
			->getForm();			
		
		
		//CONSULTA
		
		$sql = "SELECT (SUM(case when q.hayquiebre = 1 then 1 else 0 END)*100.0)/COUNT(q.id) as quiebre, ni.NOMBRE as SEGMENTO, ni2.NOMBRE as CATEGORIA, cad.NOMBRE as CADENA FROM QUIEBRE q
			INNER JOIN SALAMEDICION sm on sm.ID = q.SALAMEDICION_ID
			INNER JOIN MEDICION m on m.ID = sm.MEDICION_ID and m.ID =17
			INNER JOIN SALACLIENTE sc on sc.ID = sm.SALACLIENTE_ID
			INNER JOIN SALA s on s.ID = sc.SALA_ID
			INNER JOIN CADENA cad on cad.ID = s.CADENA_ID
			INNER JOIN CLIENTE c on c.ID = sc.CLIENTE_ID
			INNER JOIN USUARIO u on u.cliente_id=c.id and u.id=".$user->getId()."
			INNER JOIN ITEMCLIENTE ic on ic.ID = q.ITEMCLIENTE_ID AND ic.CLIENTE_ID = c.ID
			INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID
			INNER JOIN NIVELITEM ni2 on ni2.ID = ic.NIVELITEM_ID2			
			GROUP BY ni2.NOMBRE, ni.NOMBRE, cad.NOMBRE";
		$resumen_quiebre = $em->getConnection()->executeQuery($sql)->fetchAll();
		$niveles=2;
				
		// print_r($resumen_quiebre);
		
		// CONSTRUIR EL ENCABEZADO DE LA TABLA
		
		if($niveles==1)
			$head=array('CATEGORIA/CADENA');
		else
			$head=array('CATEGORIA/CADENA','SEGMENTO');
				
		$cadenas=array();
		$agregaciones=array();
		
		// Generamos el head de la tabla, y las cadenas
		foreach($resumen_quiebre as $registro)
		{
			// print_r($resumen_quiebre);
			if(!in_array($registro['CADENA'],$head))
			{
				array_push($head,$registro['CADENA']);
				array_push($cadenas,$registro['CADENA']);
			}		
		}									
		
		array_push($head,'TOTAL');
		
		// Guardamos resultado de consulta en variable de sesión para reusarlas en un action posterior
		$session->set("cadenas",$cadenas);
		// $session->set("agregaciones",$agregaciones);
		$session->set("resumen_quiebre",$resumen_quiebre);		
		// print_r($tabla_resumen);						
		
		//medicion join estudio
		$query = $em->createQuery(
			'SELECT m.nombre, m.fechafin FROM CademReporteBundle:Medicion m
			JOIN m.estudio e
			JOIN e.cliente c
			JOIN c.usuarios u
			WHERE u.id = :id')
			->setParameter('id', $user->getId());
		$mediciones_q = $query->getArrayResult();
		
		foreach($mediciones_q as $m)
		{
			$mediciones[] = $m['nombre'];
			$mediciones_corta[] = $m['fechafin']->format('d/m');
		}
		
		//quiebre join salamedicion join medicion
		$query = $em->createQuery(
			'SELECT COUNT(q) FROM CademReporteBundle:Quiebre q
			JOIN q.salamedicion sm
			JOIN sm.medicion m
			JOIN m.estudio e
			JOIN e.cliente c
			JOIN c.usuarios u
			WHERE u.id = :id
			GROUP BY m.id')
			->setParameter('id', $user->getId());
		$quiebres_totales = $query->getResult();

		$query = $em->createQuery(
			'SELECT COUNT(q) FROM CademReporteBundle:Quiebre q
			JOIN q.salamedicion sm
			JOIN sm.medicion m
			JOIN m.estudio e
			JOIN e.cliente c
			JOIN c.usuarios u
			WHERE u.id = :id
			AND q.hayquiebre = 1
			GROUP BY m.id')
			->setParameter('id', $user->getId());
		$quiebres = $query->getResult();
		
		foreach ($quiebres_totales as $key => $value) $porc_quiebre[] = $quiebres[$key][1]/$quiebres_totales[$key][1]*100;
								
		$periodos= array(
			'tooltip' => $mediciones,
			'data' => $mediciones_corta,
		);
		$evolutivo= $porc_quiebre;							
			
		//RESPONSE
		$response = $this->render('CademReporteBundle:Resumen:index.html.twig',
		array(
			'forms' => array(
				'form_estudio' 	=> $form_estudio->createView(),
				'form_periodo' 	=> $form_periodo->createView(),	
				'form_region' 	=> $form_region->createView(),
				'form_provincia' => $form_provincia->createView(),
				'form_comuna' 	=> $form_comuna->createView(),	
			),
			'head' => $head,
			'logofilename' => $logofilename,
			'logostyle' => $logostyle,
			'evolutivo' => json_encode($evolutivo),
			'periodos' => json_encode($periodos)
			)
		);		
		//CACHE
		$response->setPrivate();
		$response->setMaxAge(1);

		return $response;
    }
	
	public function tablaAction(Request $request)
	{	
		// Recuperar el usuario, parámetros y datos de sesión
		$user = $this->getUser();
		$em = $this->getDoctrine()->getManager();
		$session=$this->get("session");			
		$cadenas=$session->get("cadenas");			
		
		$parametros = $request->query->all();
		// $dataform = $data['f_region'];				
		
		// // CONSTRUIR EL CUERPO DE LA TABLA
		if(!array_key_exists('f_estudio',$parametros))
		{ // Si el action es invocado durante la carga de la pagina obtener el dataset desde la sesion							
			$resumen_quiebre=$session->get("resumen_quiebre");
		}
		else
		{ // Si es una llamada desde el filtro, entonces se deben recuperar los parametros y regenerar el dataset			
			$estudio=$parametros['f_estudio']['Estudio'];
			$medicion=$parametros['f_periodo']['Periodo'];
			$comunas='';
			foreach($parametros['f_comuna']['Comuna'] as $comuna)
				$comunas.=$comuna.',';	
			$comunas = trim($comunas, ',');
				
			// return(print_r($comunas,true));
			
			$sql = "SELECT (SUM(case when q.hayquiebre = 1 then 1 else 0 END)*100.0)/COUNT(q.id) as quiebre, ni.NOMBRE as SEGMENTO, ni2.NOMBRE as CATEGORIA, cad.NOMBRE as CADENA FROM QUIEBRE q
			INNER JOIN SALAMEDICION sm on sm.ID = q.SALAMEDICION_ID
			INNER JOIN MEDICION m on m.ID = sm.MEDICION_ID and m.ID = $medicion
			INNER JOIN SALACLIENTE sc on sc.ID = sm.SALACLIENTE_ID
			INNER JOIN SALA s on s.ID = sc.SALA_ID and s.COMUNA_ID in($comunas)
			INNER JOIN CADENA cad on cad.ID = s.CADENA_ID
			INNER JOIN CLIENTE c on c.ID = sc.CLIENTE_ID
			INNER JOIN USUARIO u on u.cliente_id=c.id and u.id=".$user->getId()."
			INNER JOIN ITEMCLIENTE ic on ic.ID = q.ITEMCLIENTE_ID AND ic.CLIENTE_ID = c.ID
			INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID
			INNER JOIN NIVELITEM ni2 on ni2.ID = ic.NIVELITEM_ID2				
			GROUP BY ni2.NOMBRE, ni.NOMBRE, cad.NOMBRE";				
			
			$resumen_quiebre = $em->getConnection()->executeQuery($sql)->fetchAll();
			// return(print_r($sql,true));								
		}		
		$body=array();			
		$num_regs=count($resumen_quiebre);		
		$cont_cads=0;
		$cont_regs=0;
		$num_cads=count($cadenas);		
		$matriz_totales=array();
				
		if($num_regs>0)
		{					
			// Para llevar los cambios del 1er nivel de agregacion
			$nivel1=$resumen_quiebre[$cont_regs]['SEGMENTO'];
			$nivel2=$resumen_quiebre[$cont_regs]['CATEGORIA'];
			// Lleno la fila con vacios, le agrego 3 posiciones, correspondientes a los niveles de agregación y al total															
			$fila=array_fill(0,$num_cads+3,'-');
			// Almacena totales de agregacion			
			// Lleno la fila con vacios, le agrego 1 posiciones, correspondientes al total																		
			$totales=array_fill(0,$num_cads+1,0);
			$total=0;
			$cont=1;
			$cont_totales=0;
			
			while($cont_regs<$num_regs)
			{	
				$columna_quiebre=array_search($resumen_quiebre[$cont_regs]['CADENA'],$cadenas);	
				// Mientras no cambie el 2o nivel acumulamos totales de agregcion en columnas correspondientes			
				if($nivel2==$resumen_quiebre[$cont_regs]['CATEGORIA'])
				{
					$totales[$columna_quiebre]+=round($resumen_quiebre[$cont_regs]['quiebre'],1);				
				}
				else
				{ // Si cambia el 2o nivel agrego totales del segmento actual a la matriz			
					for($aux=0;$aux<count($totales);++$aux)
						$totales[$aux]=round($totales[$aux]/$cont,1);			
					$totales[$num_cads]=round($totales[$num_cads]/$cont,1);	
					// Reinicializo contador de segmentos
					$cont=0;
					$matriz_totales[$cont_totales]=$totales;
					$cont_totales++;
					$nivel2=$resumen_quiebre[$cont_regs]['CATEGORIA'];
					$totales=array_fill(0,$num_cads+1,0);
				}			
				if($nivel1==$resumen_quiebre[$cont_regs]['SEGMENTO'])
				{ // Mientras no cambie el 1er nivel asignamos los valores de quiebre a las columnas correspondientes				
					$fila[0]=$resumen_quiebre[$cont_regs]['SEGMENTO'];					
					$fila[1]=$resumen_quiebre[$cont_regs]['CATEGORIA'];												
					$fila[$columna_quiebre+2]=round($resumen_quiebre[$cont_regs]['quiebre'],1);						
					$total+=$resumen_quiebre[$cont_regs]['quiebre'];	
					$cont_regs++;
				}	
				else
				{ // Si el primer nivel de agregacion cambió, lo actualizo, agrego la fila al body y reseteo el contador de cadenas
					$fila[$num_cads+2]=round($total/$num_cads,1);
					$totales[$num_cads]=$totales[$num_cads]+$total/$num_cads;				
					$total=0;
					$cont++;				
					$nivel1=$resumen_quiebre[$cont_regs]['SEGMENTO'];
					array_push($body,(object)$fila);
					$fila=array_fill(0,$num_cads+3,'-');
					// $cont_regs--;
				}
				if($cont_regs==$num_regs-1)
				{
					$fila[$num_cads+2]=round($total/$num_cads,1);
					$totales[$num_cads]+=$total/$num_cads;
					$total=0;
					// $cont++;
					// Si el primer nivel de agregacion cambió, lo actualizo, agrego la fila al body y reseteo el contador de cadenas
					$nivel1=$resumen_quiebre[$cont_regs]['SEGMENTO'];
					array_push($body,(object)$fila);
					$fila=array_fill(0,$num_cads+3,'-');
					// echo "cont=".$cont."\n";
					for($aux=0;$aux<count($totales);++$aux)
						$totales[$aux]=round($totales[$aux]/$cont,1);				
					$cont=0;
					$matriz_totales[$cont_totales]=$totales;
					$cont_totales++;
					$nivel2=$resumen_quiebre[$cont_regs]['CATEGORIA'];
					$totales=array_fill(0,$num_cads+1,0);				
				}
			}		
		}
		// print_r($matriz_totales);		
		// print_r($resumen_quiebre);
		// print_r($body);		
		/*
		 * Output
		 */
		$output = array(
			"sEcho" => intval($_GET['sEcho']),
			"iTotalRecords" => $num_regs,
			"iTotalDisplayRecords" => $num_regs,
			"aaData" => $body,
			"matriz_totales" => $matriz_totales
		);		
		return new JsonResponse($output);
	}	
	
	public function evolutivoAction(Request $request)
    {
		$min = 0;
		$max = 100;	
	
		$evolutivo= array(mt_rand($min, $max), mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max), mt_rand($min, $max), mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max));
		
		return new JsonResponse($evolutivo);
	}
	
	public function indicadoresAction(Request $request)
    {
		// $start = microtime(true);

		// $em = $this->getDoctrine()->getManager();
		// $query = $em->createQuery(
			// 'SELECT t FROM CademReporteBundle:Test t'
		// )->setMaxResults(10000);
		
		// $cacheDriver = new \Doctrine\Common\Cache\ApcCache();

		
		//$cacheDriver->deleteAll();
		// if($prueba = $cacheDriver->contains('my_query_result')){
			// $test = $cacheDriver->fetch('my_query_result');
		// }
		// else{
			// $test = $query->getResult(\Doctrine\ORM\Query::HYDRATE_ARRAY);
			// $cacheDriver->save('my_query_result', $test, 20);
		// }
		
		// $time_taken = microtime(true) - $start;
		
		$data = $request->query->all();
		
		$min = 0;
		$max = 100;
		
		switch($data['form']['Canal']){
			case '1':
				$min = 60;
				$max = 100;
			break;
			case '2':
				$min = 40;
				$max = 100;
			break;
			case '3':
				$min = 0;
				$max = 60;
			break;
		}
		
		$ranking = array(
			'head' => array('CATEGORIA','JUMBO','LIDER','TOTTUS','TOTAL'),
			'body' => array(
				array("CERVEZA", mt_rand($min, $max), mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max)),
				array("ENERGETICA", mt_rand($min, $max), mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max)),
				array("RON", mt_rand($min, $max), mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max))
			)
		);
				
		$responseA = array(
				'cobertura' =>	array(
					'type' => 'pie',
					'name' => 'Cobertura',
					'data' => array(
							array('name' => 'Cumple', 'y' => 20, 'color' => '#83A931'),
							array('name' => 'No cumple', 'y' => 80, 'color' => '#EB3737')
						)
				),
				'atributo' =>	array(
					'type' => 'pie',
					'name' => 'Atributo',
					'data' => array(
							array('name' => 'Cumple', 'y' => 35.5, 'color' => '#83A931'),
							array('name' => 'No cumple', 'y' => 64.5, 'color' => '#EB3737')
						)
				),
				'quiebre' =>	array(
					'type' => 'pie',
					'name' => 'Quiebre',
					'data' => array(
							array('name' => 'Cumple', 'y' => 55.5, 'color' => '#83A931'),
							array('name' => 'No cumple', 'y' => 44.5, 'color' => '#EB3737')
						)
				),
				'presencia' =>	array(
					'type' => 'pie',
					'name' => 'Presencia',
					'data' => array(
							array('name' => 'Cumple', 'y' => 44.5, 'color' => '#83A931'),
							array('name' => 'No cumple', 'y' => 55.5, 'color' => '#EB3737')
						)
				),
				'ranking' => $ranking
				
		);
		$responseB = array( 
				'cobertura' =>	array(
					'type' => 'pie',
					'name' => 'Cobertura',
					'data' => array(
							array('name' => 'Cumple', 'y' => 60, 'color' => '#83A931'),
							array('name' => 'No cumple', 'y' => 40, 'color' => '#EB3737')
						)
				),
				'atributo' =>	array(
					'type' => 'pie',
					'name' => 'Atributo',
					'data' => array(
							array('name' => 'Cumple', 'y' => 15.5, 'color' => '#83A931'),
							array('name' => 'No cumple', 'y' => 84.5, 'color' => '#EB3737')
						)
				),
				'quiebre' =>	array(
					'type' => 'pie',
					'name' => 'Quiebre',
					'data' => array(
							array('name' => 'Cumple', 'y' => 0, 'color' => '#83A931'),
							array('name' => 'No cumple', 'y' => 100, 'color' => '#EB3737')
						)
				),
				'presencia' =>	array(
					'type' => 'pie',
					'name' => 'Presencia',
					'data' => array(
							array('name' => 'Cumple', 'y' => 44.5, 'color' => '#83A931'),
							array('name' => 'No cumple', 'y' => 55.5, 'color' => '#EB3737')
						)
				),
				'ranking' => $ranking
		);
		
		//RESPONSE
		if('1' === $data['form']['Periodo']) $response = new JsonResponse($responseA);
		else $response = new JsonResponse($responseB);
		
		//CACHE
		$response->setPrivate();
		$response->setMaxAge(1);


		return $response;
    }
}
