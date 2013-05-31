<?php

namespace Cadem\ReporteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\HttpFoundation\Session;

class EvolucionController extends Controller
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
												
		$form_estudio = $this->get('form.factory')->createNamedBuilder('f_estudio', 'form')
			->add('Estudio', 'choice', array(
				'choices'   => $choices_estudio,
				'required'  => true,
				'multiple'  => false,
				'data' => '0',
				'attr' => array('id' => 'myValue')
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
		
		$sql = "SELECT (SUM(case when q.hayquiebre = 1 then 1 else 0 END)*100.0)/COUNT(q.id) as quiebre, i.NOMBRE as PRODUCTO,  ni.NOMBRE as SEGMENTO, m.NOMBRE, m.FECHAINICIO FROM QUIEBRE q
				INNER JOIN PLANOGRAMA p on p.ID = q.PLANOGRAMA_ID
				INNER JOIN MEDICION m on m.ID = p.MEDICION_ID AND m.ID IN (SELECT TOP(12) m2.ID FROM MEDICION m2 WHERE m2.ID = p.MEDICION_ID ORDER BY m2.FECHAINICIO ASC)
				INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID
				INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID
				INNER JOIN CLIENTE c on c.ID = sc.CLIENTE_ID
				INNER JOIN USUARIO u on u.cliente_id=c.id and u.id=".$user->getId()."
				INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID
				INNER JOIN ITEM i on i.ID = ic.ITEM_ID
				GROUP BY  ni.NOMBRE,i.NOMBRE,m.NOMBRE,m.FECHAINICIO
				ORDER BY ni.NOMBRE,i.NOMBRE";							
		
		$evolucion_quiebre = $em->getConnection()->executeQuery($sql)->fetchAll();
		$niveles=2;
				
		$head=array();
		$mediciones=array();
		$mediciones2=array();
		
		// Generamos el head de la tabla, y las mediciones
		foreach($evolucion_quiebre as $registro)
		{
			$fila=array();
			// print_r($resumen_quiebre);
			if(!in_array($registro['NOMBRE'],$head))
			{
				array_push($head,$registro['NOMBRE']);
				$fila['nombre']=$registro['NOMBRE'];
				$fila['fecha']=$registro['FECHAINICIO'];
				array_push($mediciones,$fila);
			}		
		}						
				
		usort($mediciones, array($this,"sortFunction"));
		// CONSTRUIR EL ENCABEZADO DE LA TABLA
		
		if($niveles==1)
			$head=array('SKU/MEDICIÓN');
		else
			$head=array('SKU/MEDICIÓN','CATEGORIA');	
		
		foreach($mediciones as $medicion)
		{
			array_push($mediciones2,$medicion['nombre']);					
			array_push($head,$medicion['nombre']);					
		}

		array_push($head,'TOTAL');
		
		// Calcula el ancho máximo de la tabla	
		$extension=count($head)*10-100;
	
		if($extension<0)
			$extension=0;
			
		$max_width=100+$extension;
		
		// Guardamos resultado de consulta en variable de sesión para reusarlas en un action posterior
		$session->set("mediciones",$mediciones2);
		// $session->set("agregaciones",$agregaciones);
		$session->set("evolucion_quiebre",$evolucion_quiebre);
				
		//RESPONSE
		$response = $this->render('CademReporteBundle:Evolucion:index.html.twig',
		array(
			'forms' => array(
				'form_estudio' 	=> $form_estudio->createView(),
				'form_region' 	=> $form_region->createView(),
				'form_provincia' => $form_provincia->createView(),
				'form_comuna' 	=> $form_comuna->createView(),
			),
			'head' => $head,
			'max_width' => $max_width,
			'logofilename' => $logofilename,
			'logostyle' => $logostyle,			
			// 'evolutivo' => json_encode($evolutivo),
			// 'periodos' => json_encode($periodos)
			)
		);
		//CACHE
		$response->setPrivate();
		$response->setMaxAge(1);

		return $response;
    }
	
	// Definimos un comparador de fechas para ordenar las mediciones
	function sortFunction( $a, $b ) {		
		return strtotime($a['fecha']) - strtotime($b['fecha']);
	}		
	
	public function bodyAction(Request $request)
	{		
		// Recuperar datos de sesión
		$session=$this->get("session");			
		$mediciones=$session->get("mediciones");							
				
		$evolucion_quiebre=$session->get("evolucion_quiebre");			
				
		/* Recorrer vector de mediciones, y resultado de la consulta de forma sincrona; cada vez que se encuentre coincidencia hacer 
		fetch en resultado consulta, si no, asignar vacio */
		
		$body=array();		
		$matriz_totales=array();		
		$num_regs=count($evolucion_quiebre);
		$cont_meds=0;
		$cont_regs=0;
		$num_meds=count($mediciones);				
		
		if($num_regs>0)
		{
			// Para llevar los cambios del 1er nivel de agregacion
			$nivel1=$evolucion_quiebre[$cont_regs]['PRODUCTO'];			
			// Lleno la fila con vacios, le agrego 3 posiciones, correspondientes a los niveles de agregación y al total															
			$fila=array_fill(0,$num_meds+3,'-');																				
			$total=0;								
		
			while($cont_regs<$num_regs)
			{	// Lleno la fila con vacios, le agrego 3 posiciones, correspondientes a los niveles de agregación y al total
				$columna_quiebre=array_search($evolucion_quiebre[$cont_regs]['NOMBRE'],$mediciones);	
					
				// Mientras el primer nivel de agregación no cambie
				if($nivel1==$evolucion_quiebre[$cont_regs]['PRODUCTO'])
				{					
					// if(strlen(trim($evolucion_quiebre[$cont_regs]['PRODUCTO']))>$max_largo_cadena)
						// $max_largo_cadena=strlen(trim($evolucion_quiebre[$cont_regs]['PRODUCTO']));
					$fila[0]=trim($evolucion_quiebre[$cont_regs]['PRODUCTO']);//trim(str_replace(' ','_',trim($evolucion_quiebre[$cont_regs]['PRODUCTO'])));					
					$fila[1]=$evolucion_quiebre[$cont_regs]['SEGMENTO'];													
					$fila[$columna_quiebre+2]=round($evolucion_quiebre[$cont_regs]['quiebre'],1);						
					$total+=$evolucion_quiebre[$cont_regs]['quiebre'];	
					$cont_regs++;
					$cont_meds++;
				}	
				else
				{			
					// Si el primer nivel de agregacion cambió, lo actualizo, agrego la fila al body y reseteo el contador de mediciones			
					$fila[$num_meds+2]=round($total/$num_meds,1);
					$cont_meds=0;
					$total=0;				
					$nivel1=$evolucion_quiebre[$cont_regs]['PRODUCTO'];				
					array_push($body,(object)$fila);
					$fila=array_fill(0,$num_meds+3,'-');					
				}
				if($cont_regs==$num_regs-1)		
				{	
					$fila[$num_meds+2]=round($total/($cont_meds+1),1);					
					array_push($body,(object)$fila);
					$cont_regs++;
				}		
			}			
			// Calculo de totales			
			$totales=array_fill(0,$num_meds+1,0);
			$contadores=array_fill(0,$num_meds+1,1);
			$nivel2=$evolucion_quiebre[0]['SEGMENTO'];
			$cont_fil=0;
			$num_fil=count($body);
			$cont=0;
			
			foreach($body as $objeto)
			{	
				$fila=(array)$objeto;
				
				if($nivel2!=$fila[1])			
				{ // Si cambia el 2o nivel agrego totales del segmento actual a la matriz		
					for($aux=0;$aux<count($totales);++$aux)								
						$contadores[$aux]==0? $totales[$aux]='-':$totales[$aux]=round($totales[$aux]/$contadores[$aux],1);																						
					$matriz_totales[$cont]=$totales;
					$cont++;
					$totales=array_fill(0,$num_meds+1,0);
					$contadores=array_fill(0,$num_meds+1,0);
					$nivel2=$fila[1];					
				}	
				$cont_col=0;				
				foreach(array_slice($fila,2) as $quiebre)
				{											
					if($quiebre!='-')
					{
						$contadores[$cont_col]++;					
						$totales[$cont_col]+=$quiebre;
					}
					$cont_col++;
				}		
				if($cont_fil==$num_fil-1)		
				{	
					for($aux=0;$aux<count($totales);++$aux)								
						$contadores[$aux]==0? $totales[$aux]='-':$totales[$aux]=round($totales[$aux]/$contadores[$aux],1);																						
					$matriz_totales[$cont]=$totales;
					$cont++;
					$totales=array_fill(0,$num_meds+1,0);
					$contadores=array_fill(0,$num_meds+1,0);
					$nivel2=$fila[1];						
				}				
				$cont_fil++;
			}					
		}
		/*
		 * Output
		 */
		 // print_r($body);
		$output = array(
			"sEcho" => intval($_GET['sEcho']),
			"iTotalRecords" => $num_regs,
			"iTotalDisplayRecords" => $num_regs,
			"aaData" => $body,
			"matriz_totales" => $matriz_totales,
			// "max_largo_cadena" => $max_largo_cadena
		);		
		return new JsonResponse($output);
	}
	
	public function headerAction(Request $request)
	{		
		// Recuperar el usuario, parámetros y datos de sesión
		$user = $this->getUser();
		$em = $this->getDoctrine()->getManager();
		$session=$this->get("session");									
		$parametros = $request->query->all();							
			
		// Como es una llamada desde el filtro, entonces se deben recuperar los parametros y regenerar el dataset			
		$estudio=$parametros['f_estudio']['Estudio'];			
		$comunas='';
		foreach($parametros['f_comuna']['Comuna'] as $comuna)
			$comunas.=$comuna.',';	
		$comunas = trim($comunas, ',');								

		$sql = "SELECT (SUM(case when q.hayquiebre = 1 then 1 else 0 END)*100.0)/COUNT(q.id) as quiebre, i.NOMBRE as PRODUCTO,  ni.NOMBRE as SEGMENTO, m.NOMBRE, m.FECHAINICIO FROM QUIEBRE q
				INNER JOIN PLANOGRAMA p on p.ID = q.PLANOGRAMA_ID
				INNER JOIN MEDICION m on m.ID = p.MEDICION_ID AND m.ID IN (SELECT TOP(12) m2.ID FROM MEDICION m2 WHERE m2.ID = p.MEDICION_ID ORDER BY m2.FECHAINICIO ASC)
				INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID
				INNER JOIN SALA s on s.ID = sc.SALA_ID and s.COMUNA_ID in( {$comunas} )
				INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID
				INNER JOIN CLIENTE c on c.ID = sc.CLIENTE_ID
				INNER JOIN USUARIO u on u.cliente_id=c.id and u.id=".$user->getId()."
				INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID
				INNER JOIN ITEM i on i.ID = ic.ITEM_ID
				GROUP BY  ni.NOMBRE,i.NOMBRE,m.NOMBRE,m.FECHAINICIO
				ORDER BY ni.NOMBRE,i.NOMBRE";			
				
		$evolucion_quiebre = $em->getConnection()->executeQuery($sql)->fetchAll();						
		
		// Variable para saber cuantos niveles de agregacion define el cliente, esto debe ser parametrizado en una etapa posterior
		$niveles=2;										
				
		$head=array();
		$mediciones=array();		
		$mediciones_aux=array();		
		
		// Generamos el head de la tabla, y las mediciones
		foreach($evolucion_quiebre as $registro)
		{
			$fila=array();
			// print_r($resumen_quiebre);
			if(!in_array($registro['NOMBRE'],$head))
			{
				array_push($head,$registro['NOMBRE']);
				$fila['nombre']=$registro['NOMBRE'];
				$fila['fecha']=$registro['FECHAINICIO'];
				array_push($mediciones_aux,$fila);
			}		
		}	
		// Ordenamos la estructura usando comparador personalizado
		usort($mediciones_aux, array($this,"sortFunction"));	
		// CONSTRUIR EL ENCABEZADO DE LA TABLA

		if($niveles==1)
			$prefixes=array('SKU/MEDICION');
		else
			$prefixes=array('SKU/MEDICION','SEGMENTO');
		
		$head=array();																
		
		foreach($mediciones_aux as $medicion)
		{
			array_push($mediciones,$medicion['nombre']);					
			array_push($head,$medicion['nombre']);											
		}										
		foreach(array_reverse($prefixes) as $prefix)		
			array_unshift($head,$prefix);		
		array_push($head,'TOTAL');				
		
		// Guardamos resultado de consulta en variable de sesión para reusarlas en un action posterior
		$session->set("mediciones",$mediciones);		
		$session->set("evolucion_quiebre",$evolucion_quiebre);	
		
		// Calcula el ancho máximo de la tabla	
		$extension=count($head)*10-100;
	
		if($extension<0)
			$extension=0;
			
		$max_width=100+$extension;
		/*
		 * Output
		 */
		// $session->close();
		$output = array(
			"head" => $head,
			"max_width" => $max_width
		);		
		return new JsonResponse($output);		
	}		
	
	public function periodoAction(Request $request)
	{
	
		$min = 0;
		$max = 100;				
	
	$tabla_resumen = array(
		'cadenas' => array('LIDER','JUMBO','SANTA ISABEL','SMU','SODIMAC','MAYORISTA 10','ALVI','TOTAL'),
		'totales' => array('nombre'=>'QUIEBRE SC JOHNSON',
						   'valores'=>array(mt_rand($min, $max), mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max), mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max))
					 ),
		'segmento' => array(array('nombre'=>'AIR CARE',
								  'valores'=>array(mt_rand($min, $max), mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max), mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max)),
								  'categoria'=>array(array('nombre'=>'AMBIENTALES AUTO',
														   'valores'=>array(mt_rand($min, $max), mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max), mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max)),
											
															),		
													 array('nombre'=>'CONTINUO ELECTRICO',
														   'valores'=>array(mt_rand($min, $max), mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max), mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max)),
											
															),					
													 array('nombre'=>'CONTINUO NO ELECTRICO',
														   'valores'=>array(mt_rand($min, $max), mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max), mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max)),
											
															)			
													 ),		
																													 
								 ),
							array('nombre'=>'AUTO CARE',
								  'valores'=>array(mt_rand($min, $max), mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max), mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max)),
								  'categoria'=>array(array('nombre'=>'AMBIENTALES AUTO',
														   'valores'=>array(mt_rand($min, $max), mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max), mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max)),
											
															),									
													 ),		
																													 
								 ), 
							array('nombre'=>' HOME CLEANING',
								  'valores'=>array(mt_rand($min, $max), mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max), mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max)),
								  'categoria'=>array(array('nombre'=>'BAÑO',
														   'valores'=>array(mt_rand($min, $max), mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max), mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max)),
															),																						
													 array('nombre'=>'BAÑO-CREMA',
														   'valores'=>array(mt_rand($min, $max), mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max), mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max)),
															),																						 
													 array('nombre'=>'COCINA',
														   'valores'=>array(mt_rand($min, $max), mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max), mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max)),
															),																						 
													 array('nombre'=>'LIMPIAHORNOS',
														   'valores'=>array(mt_rand($min, $max), mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max), mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max)),
															),									
																										 
																													 
								 ), 								 
							),
						),
					);
					
		
		$evolutivo= array(mt_rand($min, $max), mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max), mt_rand($min, $max), mt_rand($min, $max),mt_rand($min, $max),mt_rand($min, $max));	
		
		//RESPONSE
		$response = 
		array(
			'tabla_resumen' => $tabla_resumen,
			'evolutivo' => $evolutivo,
			);
		
		return new JsonResponse($response);
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
