<?php

namespace Cadem\ReporteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\HttpFoundation\Session;

class PresenciaEvolucionController extends Controller
{    
	public function indexAction($variable)
    {
		$start = microtime(true);
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
				
		$tag_variable_cliente=$this->get('cadem_reporte.helper.cliente')->getTagVariable($variable);										
		
		// Obtener id de la variable
		$estudio_variable=$estudios[0]->getEstudiovariables();	
		
		$id_variable=$estudio_variable[0]->getVariable()->getId();				
				
		$session->set("variable",$id_variable);			
		
		$choices_estudio = array('0' => 'TODOS');
		foreach($estudios as $e)
		{
			$choices_estudio[$e->getId()] = strtoupper($e->getNombre());
		}
		
		$logofilename = $cliente->getLogofilename();
		$logostyle = $cliente->getLogostyle();
		

		//CADENAS
		$query = $em->createQuery(
			'SELECT DISTINCT cad FROM CademReporteBundle:Cadena cad
			JOIN cad.salas s
			JOIN s.salaclientes sc
			JOIN sc.cliente cl
			WHERE cl.id = :id
			ORDER BY cad.nombre')
			->setParameter('id', $cliente->getId());
		$cadenas = $query->getResult();
		
		$choices_cadenas = array();
		foreach($cadenas as $r)
		{
			$choices_cadenas[$r->getId()] = strtoupper($r->getNombre());
		}


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
		
		$form_cadena = $this->get('form.factory')->createNamedBuilder('f_cadena', 'form')
			->add('Cadena', 'choice', array(
				'choices'   => $choices_cadenas,
				'required'  => true,
				'multiple'  => true,
				'data' => array_keys($choices_cadenas)
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
		
		//CONSULTA SACAR ULTIMAS 12 MEDICIONES
		$sql = "SELECT TOP(12) m2.ID as ID, m2.NOMBRE as NOMBRE, m2.FECHAINICIO as FECHAINICIO, m2.FECHAFIN as FECHAFIN FROM MEDICION m2 
		INNER JOIN ESTUDIOVARIABLE ev on m2.ESTUDIOVARIABLE_ID=ev.ID
		INNER JOIN VARIABLE v on v.ID=ev.VARIABLE_ID and v.NOMBRE='{$variable}'
		INNER JOIN ESTUDIO e on ev.ESTUDIO_ID=e.ID and e.CLIENTE_ID={$user->getClienteID()} 
		ORDER BY m2.FECHAINICIO DESC";											
		
		$data_mediciones = $em->getConnection()->executeQuery($sql)->fetchAll();				
				
		$head=array();
		$mediciones=array();
		$mediciones2=array();
		$mediciones_id=array();
		$mediciones_id_str="";
		$mediciones_data = array();
		$mediciones_tooltip = array();
		$mediciones_id_str="";
		
		// Generamos el head de la tabla, y las mediciones
		foreach($data_mediciones as $registro)
		{
			$fila=array();
			if(!in_array($registro['NOMBRE'],$head))
			{
				array_push($head,$registro['NOMBRE']);
				$fila['nombre']=$registro['NOMBRE'];
				$fila['fecha']=$registro['FECHAINICIO'];
				array_push($mediciones_id,$registro['ID']);
				$mediciones_id_str.=$registro['ID'].',';
				$fi = new \DateTime($registro['FECHAINICIO']);
				$ff = new \DateTime($registro['FECHAFIN']);
				$mediciones_data[] = $fi->format('d/m').'-'.$ff->format('d/m');
				$mediciones_tooltip[] = $registro['NOMBRE'];
				array_push($mediciones,$fila);
			}		
		}										
		
		$mediciones_id_str=trim($mediciones_id_str,',');
				
		usort($mediciones, array($this,"sortFunction"));
		// CONSTRUIR EL ENCABEZADO DE LA TABLA
		
		// Construir consulta mediante UNION con los ID de las últimas 12 mediciones, obtenidos previamente
		$sql="";
		
		// TIME 2500MS
		foreach($mediciones_id as $medicion_id)
		{
			$sql.="SELECT (SUM(case when q.hayquiebre = 1 then 1 else 0 END)*100.0)/COUNT(q.id) as quiebre, i.NOMBRE as PRODUCTO, ic.CODIGOITEM1 as COD_PROD,ni.NOMBRE as SEGMENTO, m.NOMBRE, m.FECHAINICIO FROM QUIEBRE q
				INNER JOIN PLANOGRAMAQ p on p.ID = q.PLANOGRAMAQ_ID and p.MEDICION_ID = {$medicion_id}	
				INNER JOIN MEDICION m on p.MEDICION_ID=m.ID
				INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID
				INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID
				INNER JOIN ITEM i on i.ID = ic.ITEM_ID
				INNER JOIN ESTUDIOVARIABLE ev on ev.ID = m.ESTUDIOVARIABLE_ID
				INNER JOIN ESTUDIO e on e.ID = ev.ESTUDIO_ID AND e.CLIENTE_ID = {$user->getClienteID()}
				GROUP BY  ni.NOMBRE,i.NOMBRE,ic.CODIGOITEM1,m.NOMBRE,m.FECHAINICIO
				UNION ";
		}
		$sql = substr($sql, 0, -6);
		$sql.="ORDER BY ni.NOMBRE,i.NOMBRE";
		
		$sha1 = sha1($sql);

		if(!$session->has($sha1)){
			$evolucion_quiebre = $em->getConnection()->executeQuery($sql)->fetchAll();
			$session->set($sha1,$evolucion_quiebre);
		}
		else $evolucion_quiebre = $session->get($sha1);

		
		
		
		$head=array('SKU/MEDICIÓN','CATEGORIA');	
		
		// Oonstruir inicialización de columnas
		$aoColumnDefs=array();
		
		$fila=array();
		$fila['aTargets']=array(0);
		$fila['sClass']="tag";
		$fila['sWidth']="260px";
		array_push($aoColumnDefs,$fila);
		
		$fila=array();
		$fila['aTargets']=array(1);
		$fila['bVisible']=false;		
		array_push($aoColumnDefs,$fila);		
		
		$cont=2;		
		
		foreach($mediciones as $medicion)
		{
			array_push($mediciones2,$medicion['nombre']);					
			array_push($head,str_replace('AL','-',$medicion['nombre']));			
			$fila=array();
			$fila['aTargets']=array($cont);		
			// $fila['sWidth']="2%";
			$fila['sClass']="medicion";
			array_push($aoColumnDefs,$fila);	
			$cont++;			
		}
		$fila=array();
		$fila['aTargets']=array($cont);		
		$fila['sWidth']="80px";	
		$fila['sClass']="medicion";
		array_push($aoColumnDefs,$fila);
		array_push($head,'TOTAL');
		
		// Obtener totales horizontales por producto
		// TIME 700MS
		$sql = "SELECT i.NOMBRE, ni.NOMBRE, SUM(case when q.HAYQUIEBRE = 1 then 1 else 0 end)*1.0/COUNT(q.HAYQUIEBRE) as QUIEBRE FROM QUIEBRE q
		INNER JOIN PLANOGRAMAQ p on p.ID = q.PLANOGRAMAQ_ID and p.MEDICION_ID IN ({$mediciones_id_str})		
		INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID
		INNER JOIN ITEM i on i.ID = ic.ITEM_ID
		INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID
		GROUP BY i.NOMBRE, ni.NOMBRE
		ORDER BY ni.NOMBRE,i.NOMBRE";					
					
		$sha1 = sha1($sql);

		if(!$session->has($sha1)){
			$totales_producto = $em->getConnection()->executeQuery($sql)->fetchAll();
			$session->set($sha1,$totales_producto);
		}
		else $totales_producto = $session->get($sha1);

		

		// Obtener totales verticales por segmento
		
		$sql="";
		// TIME 2000MS
		
		foreach($mediciones_id as $medicion_id)
		{
			$sql.="SELECT ni.NOMBRE as SEGMENTO, m.FECHAINICIO, m.NOMBRE as MEDICION, SUM(case when q.HAYQUIEBRE = 1 then 1 else 0 end)*1.0/COUNT(q.HAYQUIEBRE) as QUIEBRE FROM QUIEBRE q
				   INNER JOIN PLANOGRAMAQ p on p.ID = q.PLANOGRAMAQ_ID and p.MEDICION_ID={$medicion_id}
				   INNER JOIN MEDICION m on p.MEDICION_ID=m.ID	
				   INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID
				   INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID		
				   GROUP BY ni.NOMBRE, m.FECHAINICIO, m.NOMBRE
				   UNION ";
		}
		$sql = substr($sql, 0, -6);
		$sql.="ORDER BY ni.NOMBRE";									
		
		$sha1 = sha1($sql);

		if(!$session->has($sha1)){
			$totales_segmento = $em->getConnection()->executeQuery($sql)->fetchAll();
			$session->set($sha1,$totales_segmento);
		}
		else $totales_segmento = $session->get($sha1);
				
		
		// Obtener totales horizontales por totales segmento (ultima columna de totales verticales por categoria)
		//TIME 270MS
		$sql =	"SELECT ni.NOMBRE as SEGMENTO, SUM(case when q.HAYQUIEBRE = 1 then 1 else 0 end)*1.0/COUNT(q.HAYQUIEBRE) as QUIEBRE FROM QUIEBRE q
		INNER JOIN PLANOGRAMAQ p on p.ID = q.PLANOGRAMAQ_ID and p.MEDICION_ID IN ({$mediciones_id_str})			
		INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID
		INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID
		GROUP BY ni.NOMBRE
		ORDER BY ni.NOMBRE";				
			
		$totales_horizontales_segmento = $em->getConnection()->executeQuery($sql)->fetchAll();					

		// Obtener totales verticales por totales categoria	
		$sql="";
		//TIME 500MS
		
		foreach($mediciones_id as $medicion_id)
		{
			$sql.= "SELECT m.FECHAINICIO, m.NOMBRE as MEDICION, SUM(case when q.HAYQUIEBRE = 1 then 1 else 0 end)*1.0/COUNT(q.HAYQUIEBRE) as QUIEBRE FROM QUIEBRE q
					INNER JOIN PLANOGRAMAQ p on p.ID = q.PLANOGRAMAQ_ID and p.MEDICION_ID={$medicion_id}
					INNER JOIN MEDICION m on p.MEDICION_ID=m.ID	
					GROUP BY m.FECHAINICIO, m.NOMBRE
				    UNION ";
		}
		$sql = substr($sql, 0, -6);
		$sql.="ORDER BY FECHAINICIO";				
		
		$sha1 = sha1($sql);

		if(!$session->has($sha1)){
			$totales_verticales_segmento = $em->getConnection()->executeQuery($sql)->fetchAll();
			$session->set($sha1,$totales_verticales_segmento);
		}
		else $totales_verticales_segmento = $session->get($sha1);

		
				
		// Obtener total horizontal por totales verticales por totales categoria
		//TIME 200MS
		$sql = "SELECT  SUM(case when q.HAYQUIEBRE = 1 then 1 else 0 end)*1.0/COUNT(q.HAYQUIEBRE) as QUIEBRE FROM QUIEBRE q
		INNER JOIN PLANOGRAMAQ p on p.ID = q.PLANOGRAMAQ_ID and p.MEDICION_ID IN ({$mediciones_id_str})";		

		$total = $em->getConnection()->executeQuery($sql)->fetchAll();
		
		// Calcula el ancho máximo de la tabla	
		$extension=count($head)*11-100;
	
		if($extension<0)
			$extension=0;
			
		$max_width=100+$extension;
		
		// Obtener tag de la variable para ser desplegada en la vista
		$estudio_variable=$estudios[0]->getEstudiovariables();	
		
		$id_variable=$estudio_variable[0]->getVariable()->getId();				
		$tag_variable=$estudio_variable[0]->getVariable()->getNombre();					
		
		// Guardamos resultado de consulta en variables de sesión para reusarlas en un action posterior
		$session->set("mediciones",$mediciones2);
		$session->set("evolucion_quiebre",$evolucion_quiebre);	
		$session->set("totales_producto",$totales_producto);		
		$session->set("totales_segmento",$totales_segmento);	
		$session->set("totales_horizontales_segmento",$totales_horizontales_segmento);	
		$session->set("totales_verticales_segmento",$totales_verticales_segmento);	
		$session->set("total",$total);
		
		$session->set("id_variable",$total);
		$session->set("tag_variable",$total);
		$session->set("mediciones_id",$mediciones_id);
		$session->set("mediciones_id_str",$mediciones_id_str);
		
		$session->set("head",$head);
		$session->set("aoColumnDefs",$aoColumnDefs);
		
		$time_taken = microtime(true) - $start;
						
		//RESPONSE
		$response = $this->render('CademReporteBundle:Evolucion:index.html.twig',
		array(
			'forms' => array(
				'form_estudio' 	=> $form_estudio->createView(),
				'form_cadena' => $form_cadena->createView(),
				'form_region' 	=> $form_region->createView(),
				'form_provincia' => $form_provincia->createView(),
				'form_comuna' 	=> $form_comuna->createView(),
			),
			'head' => $head,
			'mediciones' => json_encode($mediciones2),
			'max_width' => $max_width,
			'logofilename' => $logofilename,
			'logostyle' => $logostyle,	
			'estudios' => $estudios,
			'variable' => 5,
			'header_action' => 'presencia_evolucion_header',
			'body_action' => 'presencia_evolucion_body',	
			'aoColumnDefs' => json_encode($aoColumnDefs),
			'columnas_reservadas' => 2,
			'tag_variable' => ucwords($variable),
			'tag_cliente' => $cliente->getNombrefantasia(),
			'tag_variable_cliente' => $tag_variable_cliente,
			'mediciones_data' => json_encode($mediciones_data)			
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
		$totales_producto=$session->get("totales_producto");		
		$totales_segmento=$session->get("totales_segmento");	
		$totales_horizontales_segmento=$session->get("totales_horizontales_segmento");	
		$totales_verticales_segmento=$session->get("totales_verticales_segmento");	
		$total=$session->get("total");							
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
			$cont_totales_producto=0;			
		
			while($cont_regs<$num_regs)
			{	
				$columna_quiebre=array_search($evolucion_quiebre[$cont_regs]['NOMBRE'],$mediciones);	
					
				// Mientras el primer nivel de agregación no cambie
				if($nivel1==$evolucion_quiebre[$cont_regs]['PRODUCTO'])
				{					
					$fila[0]=trim($evolucion_quiebre[$cont_regs]['PRODUCTO'].' ['.$evolucion_quiebre[$cont_regs]['COD_PROD'].']');
					$fila[1]=$evolucion_quiebre[$cont_regs]['SEGMENTO'];													
					$fila[$columna_quiebre+2]=number_format(round($evolucion_quiebre[$cont_regs]['quiebre'],1),1,',','.');											
					$cont_regs++;
					// $cont_meds++;
				}	
				else
				{			
					// Si el primer nivel de agregacion cambió, lo actualizo, agrego la fila al body y reseteo el contador de mediciones								
					$fila[$num_meds+2]=number_format(round($totales_producto[$cont_totales_producto]['QUIEBRE']*100,1),1,',','.');					
					$cont_totales_producto++;					
					// $cont_meds=0;								
					$nivel1=$evolucion_quiebre[$cont_regs]['PRODUCTO'];				
					array_push($body,$fila);
					$fila=array_fill(0,$num_meds+3,'-');					
				}
				if($cont_regs==$num_regs)		
				{	
					$columna_quiebre=array_search($evolucion_quiebre[$cont_regs-1]['NOMBRE'],$mediciones);
					$fila[$columna_quiebre+2]=number_format(round($evolucion_quiebre[$cont_regs-1]['quiebre'],1),1,',','.');				
					$fila[$num_meds+2]=number_format(round($totales_producto[$cont_totales_producto]['QUIEBRE']*100,1),1,',','.');					
					array_push($body,$fila);									
					$cont_regs++;
				}		
			}			
			// Calculo de totales
			$fila=array_fill(0,$num_meds+1,"-");	
			$num_regs=count($totales_segmento);
			$cont_regs=0;														
			$nivel2=$totales_segmento[$cont_regs]['SEGMENTO'];	
			$cont_totales_horizontales_segmento=0;						
			
			while($cont_regs<$num_regs)
			{
				$columna_quiebre=array_search($totales_segmento[$cont_regs]['MEDICION'],$mediciones);					
				// Mientras no cambie el segmento
				if($nivel2==$totales_segmento[$cont_regs]['SEGMENTO'])
				{
					$fila[$columna_quiebre]=number_format(round($totales_segmento[$cont_regs]['QUIEBRE']*100,1),1,',','.');					
					$cont_regs++;
				}
				else
				{
					$fila[$num_meds]=number_format(round($totales_horizontales_segmento[$cont_totales_horizontales_segmento]['QUIEBRE']*100,1),1,',','.');
					$cont_totales_horizontales_segmento++;
					array_push($matriz_totales,$fila);
					$fila=array_fill(0,$num_meds+1,"-");
					$nivel2=$totales_segmento[$cont_regs]['SEGMENTO'];					
				}
				if($cont_regs==$num_regs)		
				{	
					$columna_quiebre=array_search($totales_segmento[$cont_regs-1]['MEDICION'],$mediciones);
					$fila[$columna_quiebre]=round($totales_segmento[$cont_regs-1]['QUIEBRE']*100,1);	
					$fila[$num_meds]=number_format(round($totales_horizontales_segmento[$cont_totales_horizontales_segmento]['QUIEBRE']*100,1),1,',','.');
					array_push($matriz_totales,$fila);		
					$cont_regs++;					
				}				
			}	
			$cont_regs=0;
			$num_regs=count($totales_verticales_segmento);
			$fila=array_fill(0,$num_meds+1,"-");				
			
			while($cont_regs<$num_regs)
			{
				$columna_quiebre=array_search($totales_verticales_segmento[$cont_regs]['MEDICION'],$mediciones);					
				// Mientras no cambie la cadena  
				$fila[$columna_quiebre]=number_format(round($totales_verticales_segmento[$cont_regs]['QUIEBRE']*100,1),1,',','.');					
				$cont_regs++;
			}	
			
			$fila[$num_meds]=round($total[0]['QUIEBRE']*100,1);			
			
			array_push($matriz_totales,$fila);		
				
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
		$mediciones=$session->get("mediciones");			
		$mediciones_id=$session->get("mediciones_id");			
		$mediciones_id_str=$session->get("mediciones_id_str");
		$head=$session->get("head");			
		$aoColumnDefs=$session->get("aoColumnDefs");		
		$parametros = $request->query->all();							
			
		// Como es una llamada desde el filtro, entonces se deben recuperar los parametros y regenerar el dataset			
		$estudio=$parametros['f_estudio']['Estudio'];			
		$comunas='';
		foreach($parametros['f_comuna']['Comuna'] as $comuna)
			$comunas.=$comuna.',';	
		$comunas = trim($comunas, ',');

		$cadenas='';
		foreach($parametros['f_cadena']['Cadena'] as $cadena)
			$cadenas.=$cadena.',';
		$cadenas = trim($cadenas, ',');
		
		// Construir consulta mediante UNION con los ID de las últimas 12 mediciones, obtenidos previamente
		$sql="";
		
		foreach($mediciones_id as $medicion_id)
		{
			$sql.="SELECT (SUM(case when q.hayquiebre = 1 then 1 else 0 END)*100.0)/COUNT(q.id) as quiebre, i.NOMBRE as PRODUCTO, ic.CODIGOITEM1 as COD_PROD,  ni.NOMBRE as SEGMENTO, m.NOMBRE, m.FECHAINICIO FROM QUIEBRE q
				INNER JOIN PLANOGRAMAQ p on p.ID = q.PLANOGRAMAQ_ID and p.MEDICION_ID={$medicion_id}	
				INNER JOIN MEDICION m on p.MEDICION_ID=m.ID			
				INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID and sc.CLIENTE_ID = {$user->getClienteID()}
				INNER JOIN SALA s on s.ID = sc.SALA_ID and s.COMUNA_ID in ( {$comunas} ) and s.CADENA_ID in ({$cadenas})
				INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID
				INNER JOIN CLIENTE c on c.ID = sc.CLIENTE_ID
				INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID
				INNER JOIN ITEM i on i.ID = ic.ITEM_ID
				GROUP BY ni.NOMBRE,i.NOMBRE,ic.CODIGOITEM1, m.NOMBRE,m.FECHAINICIO
				UNION ";
		}
		$sql = substr($sql, 0, -6);
		$sql.="ORDER BY ni.NOMBRE,i.NOMBRE";		
						
		$sha1 = sha1($sql);
		if(!$session->has($sha1)){
			$evolucion_quiebre = $em->getConnection()->executeQuery($sql)->fetchAll();
			$session->set($sha1,$evolucion_quiebre);
		}
		else $evolucion_quiebre = $session->get($sha1);		

		// Obtener totales horizontales por producto					
		
		$sql =	"SELECT i.NOMBRE, ni.NOMBRE, SUM(case when q.HAYQUIEBRE = 1 then 1 else 0 end)*1.0/COUNT(q.HAYQUIEBRE) as QUIEBRE FROM QUIEBRE q
		INNER JOIN PLANOGRAMAQ p on p.ID = q.PLANOGRAMAQ_ID and p.MEDICION_ID IN ({$mediciones_id_str})		
		INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID and sc.CLIENTE_ID = {$user->getClienteID()}
		INNER JOIN SALA s on s.ID = sc.SALA_ID and s.COMUNA_ID in( {$comunas} ) and s.CADENA_ID in ({$cadenas})
		INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID
		INNER JOIN ITEM i on i.ID = ic.ITEM_ID
		INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID
		GROUP BY i.NOMBRE, ni.NOMBRE
		ORDER BY ni.NOMBRE,i.NOMBRE";					

		$sha1 = sha1($sql);
		if(!$session->has($sha1)){
			$totales_producto = $em->getConnection()->executeQuery($sql)->fetchAll();
			$session->set($sha1,$totales_producto);
		}
		else $totales_producto = $session->get($sha1);

		// Obtener totales verticales por segmento
		
		$sql="";
		
		foreach($mediciones_id as $medicion_id)
		{
			$sql.=	"SELECT ni.NOMBRE as SEGMENTO, m.FECHAINICIO, m.NOMBRE as MEDICION, SUM(case when q.HAYQUIEBRE = 1 then 1 else 0 end)*1.0/COUNT(q.HAYQUIEBRE) as QUIEBRE FROM QUIEBRE q
					INNER JOIN PLANOGRAMAQ p on p.ID = q.PLANOGRAMAQ_ID and p.MEDICION_ID={$medicion_id}
					INNER JOIN MEDICION m on p.MEDICION_ID=m.ID	
					INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID and sc.CLIENTE_ID = {$user->getClienteID()}
					INNER JOIN SALA s on s.ID = sc.SALA_ID and s.COMUNA_ID in( {$comunas} ) and s.CADENA_ID in ({$cadenas})
					INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID
					INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID		
					GROUP BY ni.NOMBRE, m.FECHAINICIO, m.NOMBRE
					UNION ";
		}
		$sql = substr($sql, 0, -6);
		$sql.="ORDER BY ni.NOMBRE";				
	
		$sha1 = sha1($sql);
		if(!$session->has($sha1)){
			$totales_segmento = $em->getConnection()->executeQuery($sql)->fetchAll();
			$session->set($sha1,$totales_segmento);
		}
		else $totales_segmento = $session->get($sha1);
		
		// Obtener totales horizontales por totales segmento (ultima columna de totales verticales por categoria)
		
		$sql =	"SELECT ni.NOMBRE as SEGMENTO, SUM(case when q.HAYQUIEBRE = 1 then 1 else 0 end)*1.0/COUNT(q.HAYQUIEBRE) as QUIEBRE FROM QUIEBRE q
		INNER JOIN PLANOGRAMAQ p on p.ID = q.PLANOGRAMAQ_ID and p.MEDICION_ID IN ({$mediciones_id_str})			
		INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID and sc.CLIENTE_ID = {$user->getClienteID()}
		INNER JOIN SALA s on s.ID = sc.SALA_ID and s.COMUNA_ID in( {$comunas} ) and s.CADENA_ID in ({$cadenas})
		INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID
		INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID
		GROUP BY ni.NOMBRE
		ORDER BY ni.NOMBRE";						
		
		$sha1 = sha1($sql);
		if(!$session->has($sha1)){
			$totales_horizontales_segmento = $em->getConnection()->executeQuery($sql)->fetchAll();
			$session->set($sha1,$totales_horizontales_segmento);
		}
		else $totales_horizontales_segmento = $session->get($sha1);
		
		// Obtener totales verticales por totales categoria						
		
		$sql="";
		
		foreach($mediciones_id as $medicion_id)
		{
			$sql.= "SELECT  m.FECHAINICIO, m.NOMBRE as MEDICION, SUM(case when q.HAYQUIEBRE = 1 then 1 else 0 end)*1.0/COUNT(q.HAYQUIEBRE) as QUIEBRE FROM QUIEBRE q
			INNER JOIN PLANOGRAMAQ p on p.ID = q.PLANOGRAMAQ_ID and p.MEDICION_ID={$medicion_id}
			INNER JOIN MEDICION m on p.MEDICION_ID=m.ID	
			INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID and sc.CLIENTE_ID = {$user->getClienteID()}
			INNER JOIN SALA s on s.ID = sc.SALA_ID and s.COMUNA_ID in( {$comunas} )	and s.CADENA_ID in ({$cadenas})	
			GROUP BY m.FECHAINICIO, m.NOMBRE
			UNION ";
		}
		$sql = substr($sql, 0, -6);
		$sql.="ORDER BY FECHAINICIO";			
				
		$sha1 = sha1($sql);
		if(!$session->has($sha1)){
			$totales_verticales_segmento = $em->getConnection()->executeQuery($sql)->fetchAll();
			$session->set($sha1,$totales_verticales_segmento);
		}
		else $totales_verticales_segmento = $session->get($sha1);					
		
		// Obtener total horizontal por totales verticales por totales categoria
		
		$sql = "SELECT  SUM(case when q.HAYQUIEBRE = 1 then 1 else 0 end)*1.0/COUNT(q.HAYQUIEBRE) as QUIEBRE FROM QUIEBRE q
		INNER JOIN PLANOGRAMAQ p on p.ID = q.PLANOGRAMAQ_ID and p.MEDICION_ID IN ({$mediciones_id_str})		
		INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID and sc.CLIENTE_ID = {$user->getClienteID()}
		INNER JOIN SALA s on s.ID = sc.SALA_ID and s.COMUNA_ID in( {$comunas} ) and s.CADENA_ID in ({$cadenas})";				
		
		$total = $em->getConnection()->executeQuery($sql)->fetchAll();											
		
		// Guardamos resultado de consulta en variable de sesión para reusarlas en un action posterior
		$session->set("mediciones",$mediciones);		
		$session->set("evolucion_quiebre",$evolucion_quiebre);	
		$session->set("totales_producto",$totales_producto);		
		$session->set("totales_segmento",$totales_segmento);	
		$session->set("totales_horizontales_segmento",$totales_horizontales_segmento);	
		$session->set("totales_verticales_segmento",$totales_verticales_segmento);	
		$session->set("total",$total);		
		
		// Calcula el ancho máximo de la tabla	
		$extension=count($head)*11-100;
	
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
}
