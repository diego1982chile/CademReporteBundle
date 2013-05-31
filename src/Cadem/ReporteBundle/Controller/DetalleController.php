<?php

namespace Cadem\ReporteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\HttpFoundation\Session;

class DetalleController extends Controller
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
		
		if(count($mediciones) > 0){
			$ultima_medicion = current(array_keys($mediciones));
			$id_medicion_actual = $ultima_medicion;
			if(count($mediciones) > 1) list(,$id_medicion_anterior) = array_keys($mediciones);
			else $id_medicion_anterior = $id_medicion_actual;
		}
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
		
		$sql = "SELECT (case when q.hayquiebre = 1 then 1 else 0 END) as quiebre, ic.CODIGOITEM1 as COD_PRODUCTO,i.NOMBRE as NOM_PRODUCTO,ni.NOMBRE as SEGMENTO, sc.CODIGOSALA as COD_SALA, s.CALLE as CALLE_SALA, s.NUMEROCALLE as NUM_SALA, cad.NOMBRE as CAD_SALA, com.NOMBRE as COM_SALA FROM QUIEBRE q
		INNER JOIN PLANOGRAMA p on p.ID = q.PLANOGRAMA_ID
		INNER JOIN MEDICION m on m.ID = p.MEDICION_ID and m.ID=6
		INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID
		INNER JOIN SALA s on s.ID = sc.SALA_ID
		INNER JOIN CLIENTE c on c.ID = sc.CLIENTE_ID
		INNER JOIN USUARIO u on u.cliente_id=c.id and u.id=".$user->getId()."
		INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID AND ic.CLIENTE_ID = c.ID
		INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID
		INNER JOIN COMUNA com on s.COMUNA_ID=com.ID
		INNER JOIN CADENA cad on s.CADENA_ID=cad.ID	
		INNER JOIN ITEM i on i.ID = ic.ITEM_ID	
		ORDER BY SEGMENTO,NOM_PRODUCTO,CAD_SALA,COM_SALA,CALLE_SALA";
		
		$detalle_quiebre = $em->getConnection()->executeQuery($sql)->fetchAll();
				
		// Variable para saber cuantos niveles de agregacion define el cliente, esto debe ser parametrizado en una etapa posterior
		$niveles=2;										
				
		$head=array();
		$salas=array();		
		$salas_aux=array();		
		
		// // Generamos el head de la tabla, y las salas
		foreach($detalle_quiebre as $registro)
		{			
			$fila=array();
			
			if(!in_array($registro['COD_SALA'],$head))
			{
				array_push($head,$registro['COD_SALA']);
				$fila['COD_SALA']=$registro['COD_SALA'];
				$fila['NOM_SALA']=$registro['CAD_SALA'].' '.strtoupper($registro['COM_SALA']).' '.$registro['CALLE_SALA'].' '.$registro['NUM_SALA'];
				array_push($salas_aux,$fila);
			}					
		}				
		
		usort($salas_aux, array($this,"sortFunction"));		
		// CONSTRUIR EL ENCABEZADO DE LA TABLA
			
		if($niveles==1)
			$prefixes=array('SKU/SALA');
		else
			$prefixes=array('SKU/SALA','SEGMENTO');
		
		$head=array();
		
		foreach($salas_aux as $sala)
		{
			array_push($salas,$sala['COD_SALA']);	
			$head[$sala['COD_SALA']]=$sala['NOM_SALA'];						
		}		
						
		foreach(array_reverse($prefixes) as $prefix)		
			array_unshift($head,$prefix);		
		array_push($head,'TOTAL');			
		
		// Guardamos resultado de consulta en variable de sesión para reusarlas en un action posterior
		$session->set("salas",$salas);				
		$session->set("detalle_quiebre",$detalle_quiebre);		

		// Calcula el ancho máximo de la tabla	
		$extension=count($head)*15-100;
	
		if($extension<0)
			$extension=0;
			
		$max_width=100+$extension;		

		echo "max_width=".$max_width;
		
		//RESPONSE
		$response = $this->render('CademReporteBundle:Detalle:index.html.twig',
		array(
			'forms' => array(
				'form_estudio' 	=> $form_estudio->createView(),
				'form_periodo' 	=> $form_periodo->createView(),	
				'form_region' 	=> $form_region->createView(),
				'form_provincia' => $form_provincia->createView(),
				'form_comuna' 	=> $form_comuna->createView(),	
			),
			'head' => $head,
			'max_width' => $max_width,
			'logofilename' => $logofilename,
			'logostyle' => $logostyle,
			)
		);
		
		//CACHE
		$response->setPrivate();
		$response->setMaxAge(1);

		return $response;
    }
	
	function limit_text($text, $limit) {
      if (str_word_count($text, 0) > $limit) {
          $words = str_word_count($text, 2);
          $pos = array_keys($words);
          $text = substr($text, 0, $pos[$limit]) . '...';
      }
      return $text;
    }
	
	// Definimos un comparador de cadenas para ordenar las salas
	function sortFunction( $a, $b ) {		
		return $a['NOM_SALA'] > $b['NOM_SALA'];
	}		
	
	public function bodyAction(Request $request)
	{		
		// Recuperar el usuario y datos de sesión
		$user = $this->getUser();
		$em = $this->getDoctrine()->getManager();
		$session=$this->get("session");			
		$salas=$session->get("salas");									
		$detalle_quiebre=$session->get("detalle_quiebre");
		
		// CONSTRUIR EL CUERPO DE LA TABLA						
		$body=array();									
		$num_regs=count($detalle_quiebre);		
		$cont_salas=0;
		$cont_regs=0;
		$num_salas=count($salas);			
		$matriz_totales=array();		
	
		if($num_regs>0)
		{
			$nivel1=$detalle_quiebre[$cont_regs]['COD_PRODUCTO'];		
			// Lleno la fila con vacios, le agrego 1 posiciones, correspondientes al total		
			$fila=array_fill(0,$num_salas+3,"-");	
							
			$nivel2=$detalle_quiebre[$cont_regs]['SEGMENTO'];																								
			$total=0;					
		
			while($cont_regs<$num_regs)
			{	// Lleno la fila con vacios, le agrego 3 posiciones, correspondientes a los niveles de agregación y al total	
				$columna_quiebre=array_search($detalle_quiebre[$cont_regs]['COD_SALA'],$salas);	
						
				// Mientras el primer nivel de agregación no cambie			
				if($nivel1==$detalle_quiebre[$cont_regs]['COD_PRODUCTO'])
				{									
					$fila[0]=$detalle_quiebre[$cont_regs]['NOM_PRODUCTO'].' ['.$detalle_quiebre[$cont_regs]['COD_PRODUCTO'].']';					
					$fila[1]=$detalle_quiebre[$cont_regs]['SEGMENTO'];	
					$fila[$columna_quiebre+2]=$detalle_quiebre[$cont_regs]['quiebre'];
					
					$total+=$detalle_quiebre[$cont_regs]['quiebre'];	
					$cont_regs++;	
					$cont_salas++;
				}	
				else
				{							
					$fila[$num_salas+2]=round($total/$cont_salas,1);
					$cont_salas=0;
					$total=0;
					// Si el primer nivel de agregacion cambió, lo actualizo, agrego la fila al body y reseteo el contador de cadenas
					$nivel1=$detalle_quiebre[$cont_regs]['COD_PRODUCTO'];
					array_push($body,$fila);
					$fila=array_fill(0,$num_salas+3,"-");	
				}
				if($cont_regs==$num_regs-1)		
				{	
					$fila[$num_salas+2]=round($total/$cont_salas,1);					
					array_push($body,$fila);						
				}			
			}	
			// Calculo de totales			
			$totales=array_fill(0,$num_salas+1,0);
			$contadores=array_fill(0,$num_salas+1,0);
			$nivel2=$detalle_quiebre[0]['SEGMENTO'];
			$cont_fil=0;
			$num_fil=count($body);
			$cont=0;						
			
			foreach($body as $objeto)
			{									
				$fila=$objeto;				
				
				if($nivel2!=$fila[1])			
				{ // Si cambia el 2o nivel agrego totales del segmento actual a la matriz							
					for($aux=0;$aux<count($totales);++$aux)								
						$contadores[$aux]==0? $totales[$aux]='-':$totales[$aux]=round($totales[$aux]/$contadores[$aux],1);																						
					$matriz_totales[$cont]=$totales;
					$cont++;
					$totales=array_fill(0,$num_salas+1,0);
					$contadores=array_fill(0,$num_salas+1,0);
					$nivel2=$fila[1];					
				}	
				$cont_col=0;				
								
				foreach(array_slice($fila,2) as $quiebre)
				{											
					if(strcmp($quiebre,"-")!=0)
					{						
						$contadores[$cont_col]++;					
						$totales[$cont_col]+=$quiebre;
						switch($quiebre)
						{
							case '0':
								$body[$cont_fil][$cont_col+2]="<div style='background:green;height:1.6em'></div>";	
								break;
							case '1':
								$body[$cont_fil][$cont_col+2]="<div style='background:red;height:1.6em'></div>";	
								break;
						}
					}
					else
					{
						$body[$cont_fil][$cont_col+2]="<div style='background:grey;height:1.6em'></div>";	
					}					
					$cont_col++;
				}		
				if($cont_fil==$num_fil-1)		
				{	
					for($aux=0;$aux<count($totales);++$aux)								
						$contadores[$aux]==0? $totales[$aux]='-':$totales[$aux]=round($totales[$aux]/$contadores[$aux],1);																						
					$matriz_totales[$cont]=$totales;
					$cont++;
					$totales=array_fill(0,$num_salas+1,0);
					$contadores=array_fill(0,$num_salas+1,0);
					$nivel2=$fila[1];						
				}					
				$cont_fil++;
			}					
		}
		/*
		 * Output
		 */
		// $session->close();
		$output = array(
			"sEcho" => intval($_GET['sEcho']),
			"iTotalRecords" => $num_regs,
			"iTotalDisplayRecords" => $num_regs,
			"aaData" => $body,
			"matriz_totales" => $matriz_totales
		);		
		return new JsonResponse($output);		
	}
	
	public function headerAction(Request $request)
	{		
		// Recuperar el usuario, parámetros y datos de sesión
		$user = $this->getUser();
		$em = $this->getDoctrine()->getManager();
		$session=$this->get("session");			
		// $salas=$session->get("salas");					
		$parametros = $request->query->all();							
			
		// Como es una llamada desde el filtro, entonces se deben recuperar los parametros y regenerar el dataset			
		$estudio=$parametros['f_estudio']['Estudio'];	
		$medicion=$parametros['f_periodo']['Periodo'];			
		$comunas='';
		foreach($parametros['f_comuna']['Comuna'] as $comuna)
			$comunas.=$comuna.',';	
		$comunas = trim($comunas, ',');						
			
		$sql = "SELECT (case when q.hayquiebre = 1 then 1 else 0 END) as quiebre, ic.CODIGOITEM1 as COD_PRODUCTO,i.NOMBRE as NOM_PRODUCTO,ni.NOMBRE as SEGMENTO, sc.CODIGOSALA as COD_SALA, s.CALLE as CALLE_SALA, s.NUMEROCALLE as NUM_SALA, cad.NOMBRE as CAD_SALA, com.NOMBRE as COM_SALA FROM QUIEBRE q
				INNER JOIN PLANOGRAMA p on p.ID = q.PLANOGRAMA_ID
				INNER JOIN MEDICION m on m.ID = p.MEDICION_ID and m.ID=$medicion
				INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID
				INNER JOIN SALA s on s.ID = sc.SALA_ID
				INNER JOIN CLIENTE c on c.ID = sc.CLIENTE_ID
				INNER JOIN USUARIO u on u.cliente_id=c.id and u.id=".$user->getId()."
				INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID AND ic.CLIENTE_ID = c.ID
				INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID
				INNER JOIN COMUNA com on s.COMUNA_ID=com.ID and com.ID in ($comunas)
				INNER JOIN CADENA cad on s.CADENA_ID=cad.ID	
				INNER JOIN ITEM i on i.ID = ic.ITEM_ID	
				ORDER BY SEGMENTO,NOM_PRODUCTO,CAD_SALA,COM_SALA,CALLE_SALA";																		
				
		$detalle_quiebre = $em->getConnection()->executeQuery($sql)->fetchAll();						
		
		// Variable para saber cuantos niveles de agregacion define el cliente, esto debe ser parametrizado en una etapa posterior
		$niveles=2;										
				
		$head=array();
		$salas=array();		
		$salas_aux=array();		
		
		// Generamos el head de la tabla, y las salas
		foreach($detalle_quiebre as $registro)
		{			
			$fila=array();
			
			if(!in_array($registro['COD_SALA'],$head))
			{
				array_push($head,$registro['COD_SALA']);
				$fila['COD_SALA']=$registro['COD_SALA'];
				$fila['NOM_SALA']=$registro['CAD_SALA'].' '.strtoupper($registro['COM_SALA']).' '.$registro['CALLE_SALA'].' '.$registro['NUM_SALA'];
				array_push($salas_aux,$fila);
			}					
		}						
		// Ordenamos la estructura usando comparador personalizado
		usort($salas_aux, array($this,"sortFunction"));		
		// CONSTRUIR EL ENCABEZADO DE LA TABLA
			
		if($niveles==1)
			$prefixes=array('SKU/SALA');
		else
			$prefixes=array('SKU/SALA','SEGMENTO');
		
		$head=array();
		
		foreach($salas_aux as $sala)
		{
			$fila=array();
			$fila['cod_sala']=$sala['COD_SALA'];
			$fila['nom_sala']=$sala['NOM_SALA'];			
			array_push($salas,$sala['COD_SALA']);		
			array_push($head,$fila);
			// $head[$sala['COD_SALA']]=$sala['NOM_SALA'];											
		}		
		
		foreach(array_reverse($prefixes) as $prefix)		
			array_unshift($head,$prefix);		
		array_push($head,'TOTAL');						
		
		// Guardamos resultado de consulta en variable de sesión para reusarlas en un action posterior
		$session->set("salas",$salas);		
		$session->set("detalle_quiebre",$detalle_quiebre);	
		
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
			"head" => (array)$head,
			"max_width" => $max_width,
		);		
		return new JsonResponse($output);		
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
