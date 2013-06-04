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
		
		//ULTIMA MEDICION
		$id_ultima_medicion = $this->get('cadem_reporte.helper.medicion')->getIdUltimaMedicion();
		
		//CONSULTA
		
		$sql = "SELECT (SUM(case when q.hayquiebre = 1 then 1 else 0 END)*100.0)/COUNT(q.id) as quiebre, ni.NOMBRE as SEGMENTO, ni2.NOMBRE as CATEGORIA, cad.NOMBRE as CADENA FROM QUIEBRE q
			INNER JOIN PLANOGRAMA p on p.ID = q.PLANOGRAMA_ID
			INNER JOIN MEDICION m on m.ID = p.MEDICION_ID and m.ID = {$id_ultima_medicion}
			INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID
			INNER JOIN SALA s on s.ID = sc.SALA_ID
			INNER JOIN CADENA cad on cad.ID = s.CADENA_ID
			INNER JOIN CLIENTE c on c.ID = sc.CLIENTE_ID
			INNER JOIN USUARIO u on u.cliente_id=c.id and u.id=".$user->getId()."
			INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID AND ic.CLIENTE_ID = c.ID
			INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID
			INNER JOIN NIVELITEM ni2 on ni2.ID = ic.NIVELITEM_ID2			
			GROUP BY ni2.NOMBRE, ni.NOMBRE, cad.NOMBRE";
		$resumen_quiebre = $em->getConnection()->executeQuery($sql)->fetchAll();
		$niveles=2;
		
			$head=array();
		$cadenas=array();		
		$cadenas_aux=array();		
		
		// Generamos el head de la tabla, y las cadenas
		foreach($resumen_quiebre as $registro)
		{
			// print_r($resumen_quiebre);
			if(!in_array($registro['CADENA'],$head))
			{
				array_push($head,$registro['CADENA']);
				array_push($cadenas_aux,$registro['CADENA']);
			}		
		}							
		// Ordenamos la estructura usando comparador personalizado
		usort($cadenas_aux, array($this,"sortFunction"));		
		// CONSTRUIR EL ENCABEZADO DE LA TABLA
			
		if($niveles==1)
			$prefixes=array('SKU/SALA');
		else
			$prefixes=array('SKU/SALA','SEGMENTO');
		
		$head=array();		
		
		foreach($cadenas_aux as $cadena)
		{
			array_push($cadenas,$cadena);
			array_push($head,$cadena);						
		}		
		
		foreach(array_reverse($prefixes) as $prefix)		
			array_unshift($head,$prefix);		
		array_push($head,'TOTAL');	
		
// Obtener totales horizontales por segmento
			
		$sql =	"SELECT ni.NOMBRE as segmento, ni2.NOMBRE as categoria, SUM(case when q.HAYQUIEBRE = 1 then 1 else 0 end)*1.0/COUNT(q.HAYQUIEBRE) as quiebre FROM QUIEBRE q
		INNER JOIN PLANOGRAMA p on p.ID = q.PLANOGRAMA_ID AND p.MEDICION_ID = {$id_ultima_medicion}
		INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID
		INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID
		INNER JOIN NIVELITEM ni2 on ni2.ID = ic.NIVELITEM_ID2
		INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID
		INNER JOIN SALA s on s.ID = sc.SALA_ID
		INNER JOIN CADENA c on c.ID = s.CADENA_ID
		GROUP BY ni.NOMBRE, ni2.NOMBRE
		ORDER BY categoria, segmento";
	
		$totales_segmento = $em->getConnection()->executeQuery($sql)->fetchAll();		

		// Obtener totales verticales por categoria
					
		$sql =	"SELECT ni.NOMBRE as CATEGORIA, c.NOMBRE as CADENA, SUM(case when q.HAYQUIEBRE = 1 then 1 else 0 end)*1.0/COUNT(q.HAYQUIEBRE) as QUIEBRE FROM QUIEBRE q
		INNER JOIN PLANOGRAMA p on p.ID = q.PLANOGRAMA_ID AND p.MEDICION_ID = {$id_ultima_medicion}
		INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID
		INNER JOIN SALA s on s.ID = sc.SALA_ID
		INNER JOIN CADENA c on c.ID = s.CADENA_ID
		INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID
		INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID2
		GROUP BY ni.NOMBRE, c.NOMBRE
		ORDER BY CATEGORIA,CADENA";
	
		$totales_categoria = $em->getConnection()->executeQuery($sql)->fetchAll();
		
		// Obtener totales horizontales por totales segmento (ultima columna de totales verticales por categoria)
		
		$sql =	"SELECT ni.NOMBRE as CATEGORIA, SUM(case when q.HAYQUIEBRE = 1 then 1 else 0 end)*1.0/COUNT(q.HAYQUIEBRE) as QUIEBRE FROM QUIEBRE q
				INNER JOIN PLANOGRAMA p on p.ID = q.PLANOGRAMA_ID AND p.MEDICION_ID = {$id_ultima_medicion}
				INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID
				INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID2
				GROUP BY ni.NOMBRE
				ORDER BY CATEGORIA";
			
		$totales_horizontales_categoria = $em->getConnection()->executeQuery($sql)->fetchAll();	
		
		// Obtener totales verticales por totales categoria
		
		$sql = "SELECT  c.NOMBRE as CADENA, SUM(case when q.HAYQUIEBRE = 1 then 1 else 0 end)*1.0/COUNT(q.HAYQUIEBRE) as QUIEBRE FROM QUIEBRE q
		INNER JOIN PLANOGRAMA p on p.ID = q.PLANOGRAMA_ID AND p.MEDICION_ID = {$id_ultima_medicion}
		INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID
		INNER JOIN SALA s on s.ID = sc.SALA_ID
		INNER JOIN CADENA c on c.ID = s.CADENA_ID
		GROUP BY c.NOMBRE
		ORDER BY CADENA";
		
		$totales_verticales_categoria = $em->getConnection()->executeQuery($sql)->fetchAll();							
		
		// Obtener total horizontal por totales verticales por totales categoria
		
		$sql = "SELECT SUM(case when q.HAYQUIEBRE = 1 then 1 else 0 end)*1.0/COUNT(q.HAYQUIEBRE) as QUIEBRE FROM QUIEBRE q
		INNER JOIN PLANOGRAMA p on p.ID = q.PLANOGRAMA_ID AND p.MEDICION_ID = {$id_ultima_medicion}";			

		$total = $em->getConnection()->executeQuery($sql)->fetchAll();											
		
		// Guardamos resultado de consulta en variable de sesión para reusarlas en un action posterior
		$session->set("cadenas",$cadenas);		
		$session->set("resumen_quiebre",$resumen_quiebre);		
		$session->set("totales_segmento",$totales_segmento);		
		$session->set("totales_categoria",$totales_categoria);	
		$session->set("totales_horizontales_categoria",$totales_horizontales_categoria);	
		$session->set("totales_verticales_categoria",$totales_verticales_categoria);	
		$session->set("total",$total);			
						
		// Calcula el ancho máximo de la tabla	
		$extension=count($head)*10-100;
	
		if($extension<0)
			$extension=0;
			
		$max_width=100+$extension;
		
		
		//DATOS DEL EJE X EN EVOLUTIVO
		$sql = "SELECT TOP(12) m.NOMBRE, m.FECHAINICIO, m.FECHAFIN FROM MEDICION m
			INNER JOIN PLANOGRAMA p on p.MEDICION_ID = m.ID
			INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID
			INNER JOIN SALA s on s.ID = sc.SALA_ID
			
			WHERE sc.CLIENTE_ID = ?
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
			INNER JOIN PLANOGRAMA p on p.ID = q.PLANOGRAMA_ID
			INNER JOIN MEDICION m on m.ID = p.MEDICION_ID
			INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID
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
			'max_width' => $max_width,
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
	
	// Definimos un comparador de cadenas para ordenar las salas
	function sortFunction( $a, $b ) {		
		return $a > $b;
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
			INNER JOIN PLANOGRAMA p on p.MEDICION_ID = m.ID
			INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID
			INNER JOIN SALA s on s.ID = sc.SALA_ID
			
			WHERE sc.CLIENTE_ID = ? AND s.COMUNA_ID IN ( ? )
			ORDER BY m.FECHAINICIO DESC";
		$param = array($id_cliente, $array_comuna);
		$tipo_param = array(\PDO::PARAM_INT, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
		$mediciones_q = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();
		$mediciones_q = array_reverse($mediciones_q);
		
		foreach($mediciones_q as $m){
			$fi = new \DateTime($m['FECHAINICIO']);
			$ff = new \DateTime($m['FECHAFIN']);
			$mediciones_data[] = $fi->format('d/m').'-'.$ff->format('d/m');
			$mediciones_tooltip[] = $m['NOMBRE'];
		}
		
		//SI SE NECESITA AGREGAR UN GRAFICO POR CADENA
		if(isset($data['cadena'])){
			$cadena = $data['cadena'];
			$cadena_join = " INNER JOIN CADENA c on c.ID = s.CADENA_ID ";
			$cadena_where = " AND c.NOMBRE = '{$cadena}' ";
		}
		else{
			$cadena_join = "";
			$cadena_where = "";
		}
		
		//SI SE NECESITA AGREGAR UN GRAFICO POR NIVEL
		if(isset($data['nivel'])){
			$nivel = $data['nivel'];
			$esCategoria = $data['cat'];
			$nivel_join = " INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID ";
			if($esCategoria === 'true') $nivel_join .= " INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID2 ";
			else $nivel_join .= " INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID ";
			$nivel_where = " AND ni.NOMBRE = '{$nivel}' ";
		}
		else{
			$nivel_join = "";
			$nivel_where = "";
		}
		
		//DATOS DEL EJE Y EN EVOLUTIVO
		$sql = "SELECT TOP(12) (SUM(case when q.HAYQUIEBRE = 1 then 1 else 0 END)*1.0)/COUNT(q.ID) as QUIEBRE FROM QUIEBRE q
			INNER JOIN PLANOGRAMA p on p.ID = q.PLANOGRAMA_ID
			INNER JOIN MEDICION m on m.ID = p.MEDICION_ID
			INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID
			INNER JOIN SALA s on s.ID = sc.SALA_ID
			{$cadena_join}
			{$nivel_join}
			
			WHERE sc.CLIENTE_ID = ? AND s.COMUNA_ID IN ( ? ) {$cadena_where} {$nivel_where}
			GROUP BY m.FECHAINICIO
			ORDER BY m.FECHAINICIO DESC";
		$param = array($id_cliente, $array_comuna);
		$tipo_param = array(\PDO::PARAM_INT, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
		$quiebres_q = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();
		$quiebres_q = array_reverse($quiebres_q);
		
		foreach ($quiebres_q as $q) $porc_quiebre[] = round($q['QUIEBRE']*100,1);
		if(isset($porc_quiebre) === false) $porc_quiebre = -1;//EL FILTRO SELECCIONADO NO TIENE DATOS
		
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
	
	public function bodyAction(Request $request)
	{	
		// Recuperar el usuario, parámetros y datos de sesión
		$session=$this->get("session");			
		$resumen_quiebre=$session->get("resumen_quiebre");
		$cadenas=$session->get("cadenas");		
		$totales_segmento=$session->get("totales_segmento");		
		$totales_categoria=$session->get("totales_categoria");	
		$totales_horizontales_categoria=$session->get("totales_horizontales_categoria");	
		$totales_verticales_categoria=$session->get("totales_verticales_categoria");	
		$total=$session->get("total");	
				
		$body=array();		
		$matriz_totales=array();		
		$num_regs=count($resumen_quiebre);		
		$cont_cads=0;
		$cont_regs=0;		
		$num_cads=count($cadenas);			
		
		$cont_totales_segmento=0;
				
		if($num_regs>0)
		{					
			// Para llevar los cambios del 1er nivel de agregacion
			$nivel1=$resumen_quiebre[$cont_regs]['SEGMENTO'];			
			// Lleno la fila con vacios, le agrego 3 posiciones, correspondientes a los niveles de agregación y al total															
			$fila=array_fill(0,$num_cads+3,'-');																														
			
			while($cont_regs<$num_regs)
			{	
				$columna_quiebre=array_search($resumen_quiebre[$cont_regs]['CADENA'],$cadenas);	
		
				if($nivel1==$resumen_quiebre[$cont_regs]['SEGMENTO'])
				{ // Mientras no cambie el 1er nivel asignamos los valores de quiebre a las columnas correspondientes				
					$fila[0]=$resumen_quiebre[$cont_regs]['SEGMENTO'];					
					$fila[1]=$resumen_quiebre[$cont_regs]['CATEGORIA'];												
					$fila[$columna_quiebre+2]=round($resumen_quiebre[$cont_regs]['quiebre'],1);											
					$cont_regs++;
					$cont_cads++;
				}	
				else
				{ // Si el primer nivel de agregacion cambió, lo actualizo, agrego la fila al body y reseteo el contador de cadenas
					$fila[$num_cads+2]=round($totales_segmento[$cont_totales_segmento]['quiebre']*100,1);					
					$cont_totales_segmento++;
					$cont_cads=0;					
					$nivel1=$resumen_quiebre[$cont_regs]['SEGMENTO'];
					array_push($body,(object)$fila);
					$fila=array_fill(0,$num_cads+3,'-');					
				}
				if($cont_regs==$num_regs-1)		
				{	
					$fila[$num_cads+2]=round($totales_segmento[$cont_totales_segmento]['quiebre']*100,1);					
					array_push($body,(object)$fila);		
					$cont_regs++;					
				}
			}							
								
			// Calculo de totales
			$fila=array_fill(0,$num_cads+1,"-");	
			$num_regs=count($totales_categoria);
			$cont_regs=0;														
			$nivel2=$totales_categoria[$cont_regs]['CATEGORIA'];	
			$cont_totales_horizontales_categoria=0;						
			
			while($cont_regs<$num_regs)
			{
				$columna_quiebre=array_search($totales_categoria[$cont_regs]['CADENA'],$cadenas);					
				// Mientras no cambie la categoria
				if($nivel2==$totales_categoria[$cont_regs]['CATEGORIA'])
				{
					$fila[$columna_quiebre]=round($totales_categoria[$cont_regs]['QUIEBRE']*100,1);					
					$cont_regs++;
				}
				else
				{
					$fila[$num_cads]=round($totales_horizontales_categoria[$cont_totales_horizontales_categoria]['QUIEBRE']*100,1);
					$cont_totales_horizontales_categoria++;
					array_push($matriz_totales,$fila);
					$fila=array_fill(0,$num_cads+1,"-");
					$nivel2=$totales_categoria[$cont_regs]['CATEGORIA'];					
				}
				if($cont_regs==$num_regs-1)		
				{	
					$fila[$num_cads]=round($totales_horizontales_categoria[$cont_totales_horizontales_categoria]['QUIEBRE']*100,1);
					array_push($matriz_totales,(object)$fila);		
					$cont_regs++;					
				}				
			}	

			$cont_regs=0;
			$num_regs=count($totales_verticales_categoria);
			$fila=array_fill(0,$num_cads+1,"-");				
			
			while($cont_regs<$num_regs)
			{
				$columna_quiebre=array_search($totales_verticales_categoria[$cont_regs]['CADENA'],$cadenas);					
				// Mientras no cambie la cadena  
				$fila[$columna_quiebre]=round($totales_verticales_categoria[$cont_regs]['QUIEBRE']*100,1);					
				$cont_regs++;
			}	
			
			$fila[$num_cads]=round($total[0]['QUIEBRE']*100,1);
			
			// print_r($fila);
			
			array_push($matriz_totales,$fila);			
			
			// print_r($matriz_totales);	
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
	
	public function headerAction(Request $request)
	{		
		// Recuperar el usuario, parámetros y datos de sesión
		$user = $this->getUser();
		$em = $this->getDoctrine()->getManager();
		$session=$this->get("session");								
		$parametros = $request->query->all();							
			
		// Como es una llamada desde el filtro, entonces se deben recuperar los parametros y regenerar el dataset			
		$estudio=$parametros['f_estudio']['Estudio'];	
		$medicion=$parametros['f_periodo']['Periodo'];			
		$comunas='';
		foreach($parametros['f_comuna']['Comuna'] as $comuna)
			$comunas.=$comuna.',';	
		$comunas = trim($comunas, ',');											

		$sql = "SELECT (SUM(case when q.hayquiebre = 1 then 1 else 0 END)*100.0)/COUNT(q.id) as quiebre, ni.NOMBRE as SEGMENTO, ni2.NOMBRE as CATEGORIA, cad.NOMBRE as CADENA FROM QUIEBRE q
			INNER JOIN PLANOGRAMA p on p.ID = q.PLANOGRAMA_ID
			INNER JOIN MEDICION m on m.ID = p.MEDICION_ID and m.ID = {$medicion}
			INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID
			INNER JOIN SALA s on s.ID = sc.SALA_ID and s.COMUNA_ID in( {$comunas} )
			INNER JOIN CADENA cad on cad.ID = s.CADENA_ID
			INNER JOIN CLIENTE c on c.ID = sc.CLIENTE_ID
			INNER JOIN USUARIO u on u.cliente_id=c.id and u.id=2
			INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID AND ic.CLIENTE_ID = c.ID
			INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID
			INNER JOIN NIVELITEM ni2 on ni2.ID = ic.NIVELITEM_ID2				
			GROUP BY ni2.NOMBRE, ni.NOMBRE, cad.NOMBRE";				
				
		$resumen_quiebre = $em->getConnection()->executeQuery($sql)->fetchAll();						
		
		// Variable para saber cuantos niveles de agregacion define el cliente, esto debe ser parametrizado en una etapa posterior
		$niveles=2;										
				
		$head=array();
		$cadenas=array();		
		$cadenas_aux=array();		
		
		// Generamos el head de la tabla, y las cadenas
		foreach($resumen_quiebre as $registro)
		{
			// print_r($resumen_quiebre);
			if(!in_array($registro['CADENA'],$head))
			{
				array_push($head,$registro['CADENA']);
				array_push($cadenas_aux,$registro['CADENA']);
			}		
		}							
		// Ordenamos la estructura usando comparador personalizado
		usort($cadenas_aux, array($this,"sortFunction"));		
		// CONSTRUIR EL ENCABEZADO DE LA TABLA
			
		if($niveles==1)
			$prefixes=array('SKU/SALA');
		else
			$prefixes=array('SKU/SALA','SEGMENTO');
		
		$head=array();		
		
		foreach($cadenas_aux as $cadena)
		{
			array_push($cadenas,$cadena);
			array_push($head,$cadena);						
		}		
		
		foreach(array_reverse($prefixes) as $prefix)		
			array_unshift($head,$prefix);		
		array_push($head,'TOTAL');				
				
		// Obtener totales horizontales por segmento
			
		$sql =	"SELECT ni.NOMBRE as segmento, ni2.NOMBRE as categoria, SUM(case when q.HAYQUIEBRE = 1 then 1 else 0 end)*1.0/COUNT(q.HAYQUIEBRE) as quiebre FROM QUIEBRE q
		INNER JOIN PLANOGRAMA p on p.ID = q.PLANOGRAMA_ID AND p.MEDICION_ID = {$medicion}
		INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID
		INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID
		INNER JOIN NIVELITEM ni2 on ni2.ID = ic.NIVELITEM_ID2
		INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID
		INNER JOIN SALA s on s.ID = sc.SALA_ID and s.COMUNA_ID in( {$comunas} )
		INNER JOIN CADENA c on c.ID = s.CADENA_ID
		GROUP BY ni.NOMBRE, ni2.NOMBRE
		ORDER BY categoria, segmento";
	
		$totales_segmento = $em->getConnection()->executeQuery($sql)->fetchAll();		

		// Obtener totales verticales por categoria
					
		$sql =	"SELECT ni.NOMBRE as CATEGORIA, c.NOMBRE as CADENA, SUM(case when q.HAYQUIEBRE = 1 then 1 else 0 end)*1.0/COUNT(q.HAYQUIEBRE) as QUIEBRE FROM QUIEBRE q
		INNER JOIN PLANOGRAMA p on p.ID = q.PLANOGRAMA_ID AND p.MEDICION_ID = {$medicion}
		INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID
		INNER JOIN SALA s on s.ID = sc.SALA_ID and s.COMUNA_ID in( {$comunas} )
		INNER JOIN CADENA c on c.ID = s.CADENA_ID
		INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID
		INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID2
		GROUP BY ni.NOMBRE, c.NOMBRE
		ORDER BY CATEGORIA,CADENA";
	
		$totales_categoria = $em->getConnection()->executeQuery($sql)->fetchAll();
		
		// Obtener totales horizontales por totales segmento (ultima columna de totales verticales por categoria)
		
		$sql =	"SELECT ni.NOMBRE as CATEGORIA, SUM(case when q.HAYQUIEBRE = 1 then 1 else 0 end)*1.0/COUNT(q.HAYQUIEBRE) as QUIEBRE FROM QUIEBRE q
				INNER JOIN PLANOGRAMA p on p.ID = q.PLANOGRAMA_ID AND p.MEDICION_ID = {$medicion}
				INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID
				INNER JOIN SALA s on s.ID = sc.SALA_ID and s.COMUNA_ID in( {$comunas} )
				INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID
				INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID2
				GROUP BY ni.NOMBRE
				ORDER BY CATEGORIA";
			
		$totales_horizontales_categoria = $em->getConnection()->executeQuery($sql)->fetchAll();	
		
		// Obtener totales verticales por totales categoria
		
		$sql = "SELECT  c.NOMBRE as CADENA, SUM(case when q.HAYQUIEBRE = 1 then 1 else 0 end)*1.0/COUNT(q.HAYQUIEBRE) as QUIEBRE FROM QUIEBRE q
		INNER JOIN PLANOGRAMA p on p.ID = q.PLANOGRAMA_ID AND p.MEDICION_ID = {$medicion}
		INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID
		INNER JOIN SALA s on s.ID = sc.SALA_ID and s.COMUNA_ID in( {$comunas} )
		INNER JOIN CADENA c on c.ID = s.CADENA_ID
		GROUP BY c.NOMBRE
		ORDER BY CADENA";
		
		$totales_verticales_categoria = $em->getConnection()->executeQuery($sql)->fetchAll();							
		
		// Obtener total horizontal por totales verticales por totales categoria
		
		$sql = "SELECT SUM(case when q.HAYQUIEBRE = 1 then 1 else 0 end)*1.0/COUNT(q.HAYQUIEBRE) as QUIEBRE FROM QUIEBRE q
		INNER JOIN PLANOGRAMA p on p.ID = q.PLANOGRAMA_ID AND p.MEDICION_ID = {$medicion}
		INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID
		INNER JOIN SALA s on s.ID = sc.SALA_ID and s.COMUNA_ID in( {$comunas} )";			

		$total = $em->getConnection()->executeQuery($sql)->fetchAll();									
		
		// Guardamos resultado de consulta en variables de sesión para reusarlas en un action posterior
		$session->set("cadenas",$cadenas);		
		$session->set("resumen_quiebre",$resumen_quiebre);	
		$session->set("totales_segmento",$totales_segmento);		
		$session->set("totales_categoria",$totales_categoria);	
		$session->set("totales_horizontales_categoria",$totales_horizontales_categoria);	
		$session->set("totales_verticales_categoria",$totales_verticales_categoria);	
		$session->set("total",$total);	
		
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
}
