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
		$id_cliente = $cliente->getId();
		
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
		
		
		//DATOS DEL EJE X EN EVOLUTIVO
		$sql = "SELECT TOP(12) m.NOMBRE, m.FECHAINICIO, m.FECHAFIN FROM MEDICION m
			INNER JOIN SALAMEDICION sm on sm.MEDICION_ID = m.ID
			INNER JOIN SALACLIENTE sc on sc.ID = sm.SALACLIENTE_ID
			INNER JOIN SALA s on s.ID = sc.SALA_ID
			
			WHERE sc.CLIENTE_ID = ?
			ORDER BY m.FECHAINICIO DESC";
		$param = array($id_cliente);
		$tipo_param = array(\PDO::PARAM_INT);
		$mediciones_q = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();
		$mediciones_q = array_reverse($mediciones_q);
		
		foreach($mediciones_q as $m){
			$mediciones_data[] = (new \DateTime($m['FECHAINICIO']))->format('d/m').'-'.(new \DateTime($m['FECHAFIN']))->format('d/m');
			$mediciones_tooltip[] = $m['NOMBRE'];
		}
		
		//DATOS DEL EJE Y EN EVOLUTIVO
		$sql = "SELECT TOP(12) (SUM(case when q.HAYQUIEBRE = 1 then 1 else 0 END)*1.0)/COUNT(q.ID) as QUIEBRE FROM QUIEBRE q
			INNER JOIN SALAMEDICION sm on sm.ID = q.SALAMEDICION_ID
			INNER JOIN MEDICION m on m.ID = sm.MEDICION_ID
			INNER JOIN SALACLIENTE sc on sc.ID = sm.SALACLIENTE_ID
			INNER JOIN SALA s on s.ID = sc.SALA_ID
			
			WHERE sc.CLIENTE_ID = ?
			GROUP BY m.FECHAINICIO
			ORDER BY m.FECHAINICIO DESC";
		$param = array($id_cliente);
		$tipo_param = array(\PDO::PARAM_INT);
		$quiebres_q = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();
		$quiebres_q = array_reverse($quiebres_q);
		
		foreach ($quiebres_q as $q) $porc_quiebre[] = round($q['QUIEBRE']*100,1);
		
								
		$periodos= array(
			'tooltip' => $mediciones_tooltip,
			'data' => $mediciones_data,
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
	
	public function evolutivoAction(Request $request)
    {
		$user = $this->getUser();
		$em = $this->getDoctrine()->getManager();
		$data = $request->query->all();
		
		//CLIENTE
		$query = $em->createQuery(
			'SELECT c FROM CademReporteBundle:Cliente c
			JOIN c.usuarios u
			WHERE u.id = :id AND c.activo = 1')
			->setParameter('id', $user->getId());
		$clientes = $query->getResult();
		$cliente = $clientes[0];
		
		//DATOS
		$id_cliente = $cliente->getId();
		// $id_medicion_actual = intval($data['f_periodo']['Periodo']);
		$id_estudio = intval($data['f_estudio']['Estudio']);// 0 = TODOS
		$array_comuna = $data['f_comuna']['Comuna'];
		foreach($array_comuna as $k => $v) $array_comuna[$k] = intval($v);
		
		
		//DATOS DEL EJE X EN EVOLUTIVO
		$sql = "SELECT TOP(12) m.NOMBRE, m.FECHAINICIO, m.FECHAFIN FROM MEDICION m
			INNER JOIN SALAMEDICION sm on sm.MEDICION_ID = m.ID
			INNER JOIN SALACLIENTE sc on sc.ID = sm.SALACLIENTE_ID
			INNER JOIN SALA s on s.ID = sc.SALA_ID
			
			WHERE sc.CLIENTE_ID = ? AND s.COMUNA_ID IN ( ? )
			ORDER BY m.FECHAINICIO DESC";
		$param = array($id_cliente, $array_comuna);
		$tipo_param = array(\PDO::PARAM_INT, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
		$mediciones_q = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();
		$mediciones_q = array_reverse($mediciones_q);
		
		foreach($mediciones_q as $m){
			$mediciones_data[] = (new \DateTime($m['FECHAINICIO']))->format('d/m').'-'.(new \DateTime($m['FECHAFIN']))->format('d/m');
			$mediciones_tooltip[] = $m['NOMBRE'];
		}
		
		//DATOS DEL EJE Y EN EVOLUTIVO
		$sql = "SELECT TOP(12) (SUM(case when q.HAYQUIEBRE = 1 then 1 else 0 END)*1.0)/COUNT(q.ID) as QUIEBRE FROM QUIEBRE q
			INNER JOIN SALAMEDICION sm on sm.ID = q.SALAMEDICION_ID
			INNER JOIN MEDICION m on m.ID = sm.MEDICION_ID
			INNER JOIN SALACLIENTE sc on sc.ID = sm.SALACLIENTE_ID
			INNER JOIN SALA s on s.ID = sc.SALA_ID
			
			WHERE sc.CLIENTE_ID = ? AND s.COMUNA_ID IN ( ? )
			GROUP BY m.FECHAINICIO
			ORDER BY m.FECHAINICIO DESC";
		$param = array($id_cliente, $array_comuna);
		$tipo_param = array(\PDO::PARAM_INT, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
		$quiebres_q = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();
		$quiebres_q = array_reverse($quiebres_q);
		
		foreach ($quiebres_q as $q) $porc_quiebre[] = round($q['QUIEBRE']*100,1);
		
		//RESPONSE
		$response = array(
			'evo_ejex' => $mediciones_data,
			'evo_tooltip' => $mediciones_tooltip,
			'evo_ejey' => $porc_quiebre
		);
		$response = new JsonResponse($response);
		
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
				
		if($num_regs>0)
		{					
			// Para llevar los cambios del 1er nivel de agregacion
			$nivel1=$resumen_quiebre[$cont_regs]['SEGMENTO'];			
			// Lleno la fila con vacios, le agrego 3 posiciones, correspondientes a los niveles de agregación y al total															
			$fila=array_fill(0,$num_cads+3,'-');																				
			$total=0;								
			
			while($cont_regs<$num_regs)
			{	
				$columna_quiebre=array_search($resumen_quiebre[$cont_regs]['CADENA'],$cadenas);	
		
				if($nivel1==$resumen_quiebre[$cont_regs]['SEGMENTO'])
				{ // Mientras no cambie el 1er nivel asignamos los valores de quiebre a las columnas correspondientes				
					$fila[0]=$resumen_quiebre[$cont_regs]['SEGMENTO'];					
					$fila[1]=$resumen_quiebre[$cont_regs]['CATEGORIA'];												
					$fila[$columna_quiebre+2]=round($resumen_quiebre[$cont_regs]['quiebre'],1);						
					$total+=$resumen_quiebre[$cont_regs]['quiebre'];	
					$cont_regs++;
					$cont_cads++;
				}	
				else
				{ // Si el primer nivel de agregacion cambió, lo actualizo, agrego la fila al body y reseteo el contador de cadenas
					$fila[$num_cads+2]=round($total/$cont_cads,1);					
					$cont_cads=0;
					$total=0;						
					$nivel1=$resumen_quiebre[$cont_regs]['SEGMENTO'];
					array_push($body,(object)$fila);
					$fila=array_fill(0,$num_cads+3,'-');					
				}
				if($cont_regs==$num_regs-1)		
				{	
					$fila[$num_cads+2]=round($total/$cont_cads,1);					
					array_push($body,(object)$fila);						
				}
			}
									
			// Calculo de totales
			$matriz_totales=array();
			$totales=array_fill(0,$num_cads+1,0);
			$contadores=array_fill(0,$num_cads+1,1);
			$nivel2=$resumen_quiebre[0]['CATEGORIA'];
			$cont_fil=0;
			$num_fil=count($body);
			$cont=0;
			
			foreach($body as $objeto)
			{	
				$fila=(array)$objeto;
				
				if($nivel2!=$fila[1])			
				{ // Si cambia el 2o nivel agrego totales del segmento actual a la matriz		
					for($aux=0;$aux<count($totales);++$aux)								
						$contadores[$aux]=='-'? $totales[$aux]='-':$totales[$aux]=round($totales[$aux]/$contadores[$aux],1);																						
					$matriz_totales[$cont]=$totales;
					$cont++;
					$totales=array_fill(0,$num_cads+1,0);
					$contadores=array_fill(0,$num_cads+1,0);
					$nivel2=$fila[1];	
						
				}	
				$cont_col=0;				
				foreach(array_slice($fila,2) as $quiebre)
				{											
					if($quiebre!='-')
					{
						$contadores[$cont_col]++;					
						$totales[$cont_col]+=$quiebre;
						$flag=false;
					}
					$cont_col++;
				}		
				if($cont_fil==$num_fil-1)		
				{	
					for($aux=0;$aux<count($totales);++$aux)								
						$contadores[$aux]==0? $totales[$aux]='-':$totales[$aux]=round($totales[$aux]/$contadores[$aux],1);																						
					$matriz_totales[$cont]=$totales;
					$cont++;
					$totales=array_fill(0,$num_cads+1,0);
					$contadores=array_fill(0,$num_cads+1,0);
					$nivel2=$fila[1];						
				}				
				$cont_fil++;
			}			
		}							
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
}
