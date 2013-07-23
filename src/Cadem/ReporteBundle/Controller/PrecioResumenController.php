<?php

namespace Cadem\ReporteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\HttpFoundation\Session;


class PrecioResumenController extends Controller
{		
	
	public function indexAction($variable)
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
		$id_cliente = $user->getClienteID();
		
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
			JOIN m.estudiovariable ev
			JOIN ev.estudio e
			JOIN e.cliente c
			WHERE c.id = :id AND ev.variableid = :id_ev
			ORDER BY m.fechainicio DESC')
			->setParameter('id', $cliente->getId())
			->setParameter('id_ev', 2);
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
		
		$sql = "SELECT (SUM(case when ABS(pr.PRECIO-p.POLITICAPRECIO)>pa.VALOR*p.POLITICAPRECIO/100 then 1 else 0 END)*100.0)/COUNT(pr.ID) as PRECIO, ni.NOMBRE as SEGMENTO, ni2.NOMBRE as CATEGORIA, cad.NOMBRE as CADENA FROM PRECIO pr
			INNER JOIN PLANOGRAMAP p on p.ID = pr.PLANOGRAMAP_ID and pr.PRECIO is not null and p.POLITICAPRECIO is not null
			INNER JOIN MEDICION m on m.ID = p.MEDICION_ID and m.ID = {$id_ultima_medicion} 
			INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID
			INNER JOIN SALA s on s.ID = sc.SALA_ID
			INNER JOIN CADENA cad on cad.ID = s.CADENA_ID
			INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID AND ic.CLIENTE_ID = {$id_cliente}
			INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID
			INNER JOIN NIVELITEM ni2 on ni2.ID = ic.NIVELITEM_ID2	
			INNER JOIN PARAMETRO pa on pa.CLIENTE_ID = {$user->getClienteID()} and pa.NOMBRE='rango_precio'			
			GROUP BY ni2.NOMBRE, ni.NOMBRE, cad.NOMBRE
			ORDER BY categoria, segmento";
		
		// print_r($sql);		
		
		$resumen_precio = $em->getConnection()->executeQuery($sql)->fetchAll();
		$niveles=2;
		
		$head=array();
		$cadenas=array();		
		$cadenas_aux=array();		
		
		// Generamos el head de la tabla, y las cadenas
		foreach($resumen_precio as $registro)
		{
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
			$prefixes=array('SKU/CADENA');
		else
			$prefixes=array('SKU/CADENA','SEGMENTO');
		
		$head=array();		
		
		// Oonstruir inicialización de columnas
		$aoColumnDefs=array();
		
		$fila=array();
		$fila['aTargets']=array(0);
		$fila['sClass']="tag";
		$fila['sWidth']="160px";
		array_push($aoColumnDefs,$fila);
		
		$fila=array();
		$fila['aTargets']=array(1);
		$fila['bVisible']=false;		
		array_push($aoColumnDefs,$fila);		
		
		$cont=2;		
		
		foreach($cadenas_aux as $cadena)
		{
			array_push($cadenas,$cadena);
			array_push($head,$cadena);					
			$fila=array();
			$fila['sWidth']="100px";
			$fila['aTargets']=array($cont);	
			array_push($aoColumnDefs,$fila);
			$cont++;			
		}		
		
		$fila=array();
		$fila['aTargets']=array($cont);		
		$fila['sWidth']="80px";	
		array_push($aoColumnDefs,$fila);				
		
		foreach(array_reverse($prefixes) as $prefix)		
			array_unshift($head,$prefix);		
		array_push($head,'TOTAL');	
		
		// Obtener totales horizontales por segmento
			
		$sql =	"SELECT ni.NOMBRE as segmento, ni2.NOMBRE as categoria, (SUM(case when ABS(pr.PRECIO-p.POLITICAPRECIO)>pa.VALOR*p.POLITICAPRECIO/100 then 1 else 0 END)*100.0)/COUNT(pr.ID) as PRECIO FROM PRECIO pr
		INNER JOIN PLANOGRAMAP p on p.ID = pr.PLANOGRAMAP_ID AND p.MEDICION_ID = {$id_ultima_medicion} and pr.PRECIO is not null and p.POLITICAPRECIO is not null
		INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID
		INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID
		INNER JOIN NIVELITEM ni2 on ni2.ID = ic.NIVELITEM_ID2
		INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID
		INNER JOIN SALA s on s.ID = sc.SALA_ID
		INNER JOIN CADENA c on c.ID = s.CADENA_ID
		INNER JOIN PARAMETRO pa on pa.CLIENTE_ID = {$user->getClienteID()} and pa.NOMBRE='rango_precio'			
		GROUP BY ni.NOMBRE, ni2.NOMBRE
		ORDER BY categoria, segmento";
	
		$totales_segmento = $em->getConnection()->executeQuery($sql)->fetchAll();


		// Obtener totales verticales por categoria
					
		$sql =	"SELECT ni.NOMBRE as CATEGORIA, c.NOMBRE as CADENA, (SUM(case when ABS(pr.PRECIO-p.POLITICAPRECIO)>pa.VALOR*p.POLITICAPRECIO/100 then 1 else 0 END)*100.0)/COUNT(pr.ID) as PRECIO FROM PRECIO pr
		INNER JOIN PLANOGRAMAP p on p.ID = pr.PLANOGRAMAP_ID AND p.MEDICION_ID = {$id_ultima_medicion} and pr.PRECIO is not null and p.POLITICAPRECIO is not null
		INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID
		INNER JOIN SALA s on s.ID = sc.SALA_ID
		INNER JOIN CADENA c on c.ID = s.CADENA_ID
		INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID
		INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID2
		INNER JOIN PARAMETRO pa on pa.CLIENTE_ID = {$user->getClienteID()} and pa.NOMBRE='rango_precio'			
		GROUP BY ni.NOMBRE, c.NOMBRE
		ORDER BY CATEGORIA,CADENA";
	
		$totales_categoria = $em->getConnection()->executeQuery($sql)->fetchAll();	
				
		
		// Obtener totales horizontales por totales segmento (ultima columna de totales verticales por categoria)
		
		$sql =	"SELECT ni.NOMBRE as CATEGORIA, (SUM(case when ABS(pr.PRECIO-p.POLITICAPRECIO)>pa.VALOR*p.POLITICAPRECIO/100 then 1 else 0 END)*100.0)/COUNT(pr.ID) as PRECIO FROM PRECIO pr
				INNER JOIN PLANOGRAMAP p on p.ID = pr.PLANOGRAMAP_ID AND p.MEDICION_ID = {$id_ultima_medicion} and pr.PRECIO is not null and p.POLITICAPRECIO is not null
				INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID
				INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID2
				INNER JOIN PARAMETRO pa on pa.CLIENTE_ID = {$user->getClienteID()} and pa.NOMBRE='rango_precio'			
				GROUP BY ni.NOMBRE
				ORDER BY CATEGORIA";
			
		$totales_horizontales_categoria = $em->getConnection()->executeQuery($sql)->fetchAll();				
		
		// Obtener totales verticales por totales categoria
		
		$sql = "SELECT  c.NOMBRE as CADENA, (SUM(case when ABS(pr.PRECIO-p.POLITICAPRECIO)>pa.VALOR*p.POLITICAPRECIO/100 then 1 else 0 END)*100.0)/COUNT(pr.ID) as PRECIO FROM PRECIO pr
		INNER JOIN PLANOGRAMAP p on p.ID = pr.PLANOGRAMAP_ID AND p.MEDICION_ID = {$id_ultima_medicion} and pr.PRECIO is not null and p.POLITICAPRECIO is not null
		INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID
		INNER JOIN SALA s on s.ID = sc.SALA_ID
		INNER JOIN CADENA c on c.ID = s.CADENA_ID
		INNER JOIN PARAMETRO pa on pa.CLIENTE_ID = {$user->getClienteID()} and pa.NOMBRE='rango_precio'			
		GROUP BY c.NOMBRE
		ORDER BY CADENA";
		
		$totales_verticales_categoria = $em->getConnection()->executeQuery($sql)->fetchAll();				
		
		// Obtener total horizontal por totales verticales por totales categoria
		
		$sql = "SELECT (SUM(case when ABS(pr.PRECIO-p.POLITICAPRECIO)>pa.VALOR*p.POLITICAPRECIO/100 then 1 else 0 END)*100.0)/COUNT(pr.ID) as PRECIO FROM PRECIO pr
		INNER JOIN PLANOGRAMAP p on p.ID = pr.PLANOGRAMAP_ID AND p.MEDICION_ID = {$id_ultima_medicion} and pr.PRECIO is not null and p.POLITICAPRECIO is not null
		INNER JOIN PARAMETRO pa on pa.CLIENTE_ID = {$user->getClienteID()} and pa.NOMBRE='rango_precio'";			

		$total = $em->getConnection()->executeQuery($sql)->fetchAll();											
				
		
		// Guardamos resultado de consulta en variable de sesión para reusarlas en un action posterior
		$session->set("cadenas",$cadenas);		
		$session->set("resumen_precio",$resumen_precio);		
		$session->set("totales_segmento",$totales_segmento);		
		$session->set("totales_categoria",$totales_categoria);	
		$session->set("totales_horizontales_categoria",$totales_horizontales_categoria);	
		$session->set("totales_verticales_categoria",$totales_verticales_categoria);	
		$session->set("total",$total);			
		$session->set("flag",false);	
						
		// Calcula el ancho máximo de la tabla	
		$extension=count($head)*10-100;
	
		if($extension<0)
			$extension=0;
			
		$max_width=100+$extension;
		
		//DATOS DEL EJE Y EN EVOLUTIVO
		$sql = "SELECT TOP(12) (SUM(case when ABS(pr.PRECIO-p.POLITICAPRECIO)>pa.VALOR*p.POLITICAPRECIO/100 then 1 else 0 END)*100.0)/COUNT(pr.ID) as QUIEBRE,m.NOMBRE, m.FECHAINICIO, m.FECHAFIN, m.ID FROM PRECIO pr
			INNER JOIN PLANOGRAMAP p on p.ID = pr.PLANOGRAMAP_ID and pr.PRECIO is not null and p.POLITICAPRECIO is not null
			INNER JOIN MEDICION m on m.ID = p.MEDICION_ID
			INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID AND sc.CLIENTE_ID = ?
			INNER JOIN PARAMETRO pa on pa.CLIENTE_ID = ? and pa.NOMBRE='rango_precio'						
			GROUP BY m.FECHAINICIO, m.NOMBRE, m.FECHAINICIO, m.FECHAFIN, m.ID
			ORDER BY m.FECHAINICIO DESC";		
				
		$param = array($id_cliente,$id_cliente);
		$tipo_param = array(\PDO::PARAM_INT,\PDO::PARAM_INT);
		$mediciones_q = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();
		$mediciones_q = array_reverse($mediciones_q);
		
		$mediciones_data = array();
		$mediciones_tooltip = array();
		$porc_quiebre = array();
		
		foreach($mediciones_q as $m){
			$fi = new \DateTime($m['FECHAINICIO']);
			$ff = new \DateTime($m['FECHAFIN']);
			$mediciones_data[] = $fi->format('d/m').'-'.$ff->format('d/m');
			$mediciones_tooltip[] = $m['NOMBRE'];
			$porc_quiebre[] = round($m['QUIEBRE'],1);			
		}		
		
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
			'periodos' => json_encode($periodos),	
			'estudios' => $estudios,	
			'aoColumnDefs' => json_encode($aoColumnDefs),			
			'header_action' => 'precio_resumen_header',
			'body_action' => 'precio_resumen_body',	
			'header_detalle_action' => 'precio_resumen_detalle_header',
			'body_detalle_action' => 'precio_resumen_detalle_body',						
			'tag_variable' => ucwords($variable),
			'tag_cliente' => $cliente->getNombrefantasia(),
			'columnas_reservadas' => 3,
			'rango_precio' => $this->get('cadem_reporte.helper.cliente')->getRangoPrecio()
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
			INNER JOIN PLANOGRAMAQ p on p.MEDICION_ID = m.ID
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
		$sql = "SELECT TOP(12) (SUM(case when ABS(pr.PRECIO-p.POLITICAPRECIO)>pa.VALOR*p.POLITICAPRECIO/100 then 1 else 0 END)*100.0)/COUNT(pr.ID) as QUIEBRE FROM PRECIO pr
			INNER JOIN PLANOGRAMAP p on p.ID = pr.PLANOGRAMAP_ID and pr.PRECIO is not null and p.POLITICAPRECIO is not null
			INNER JOIN MEDICION m on m.ID = p.MEDICION_ID
			INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID
			INNER JOIN SALA s on s.ID = sc.SALA_ID
			INNER JOIN PARAMETRO pa on pa.CLIENTE_ID = ? and pa.NOMBRE='rango_precio'
			{$cadena_join}
			{$nivel_join}			
			WHERE sc.CLIENTE_ID = ? AND s.COMUNA_ID IN ( ? ) {$cadena_where} {$nivel_where}
			GROUP BY m.FECHAINICIO
			ORDER BY m.FECHAINICIO DESC";
		$param = array($id_cliente,$id_cliente, $array_comuna);
		$tipo_param = array(\PDO::PARAM_INT, \PDO::PARAM_INT, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
		$quiebres_q = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();
		$quiebres_q = array_reverse($quiebres_q);
		
		foreach ($quiebres_q as $q) $porc_quiebre[] = round($q['QUIEBRE']*100,1);
		if(isset($porc_quiebre) === false) $porc_quiebre = -1;//EL FILTRO SELECCIONADO NO TIENE DATOS
		
		//RESPONSE
		$response = array(
			'evo_ejex' => $mediciones_data,
			'evo_tooltip' => $mediciones_tooltip,
			'evo_ejey' => $porc_quiebre,			
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
		$resumen_precio=$session->get("resumen_precio");
		$cadenas=$session->get("cadenas");		
		$totales_segmento=$session->get("totales_segmento");		
		$totales_categoria=$session->get("totales_categoria");	
		$totales_horizontales_categoria=$session->get("totales_horizontales_categoria");	
		$totales_verticales_categoria=$session->get("totales_verticales_categoria");	
		$total=$session->get("total");	
				
		$body=array();		
		$matriz_totales=array();		
		$num_regs=count($resumen_precio);		
		$cont_cads=0;
		$cont_regs=0;		
		$num_cads=count($cadenas);			
		
		$cont_totales_segmento=0;			
		
		// print_r($resumen_quiebre);
		
		if($num_regs>0)
		{					
			// Para llevar los cambios del 1er nivel de agregacion
			$nivel1=$resumen_precio[$cont_regs]['SEGMENTO'];			
			// Lleno la fila con vacios, le agrego 3 posiciones, correspondientes a los niveles de agregación y al total															
			$fila=array_fill(0,$num_cads+3,'-');																														
			
			while($cont_regs<$num_regs)
			{	
				$columna_precio=array_search($resumen_precio[$cont_regs]['CADENA'],$cadenas);	
		
				if($nivel1==$resumen_precio[$cont_regs]['SEGMENTO'])
				{ // Mientras no cambie el 1er nivel asignamos los valores de quiebre a las columnas correspondientes				
					$fila[0]=$resumen_precio[$cont_regs]['SEGMENTO'];					
					$fila[1]=$resumen_precio[$cont_regs]['CATEGORIA'];												
					$fila[$columna_precio+2]=number_format(round($resumen_precio[$cont_regs]['PRECIO'],1),1,',','.');											
					$cont_regs++;
					$cont_cads++;
				}	
				else
				{ // Si el primer nivel de agregacion cambió, lo actualizo, agrego la fila al body y reseteo el contador de cadenas
					$fila[$num_cads+2]=number_format(round($totales_segmento[$cont_totales_segmento]['PRECIO'],1),1,',','.');					
					$cont_totales_segmento++;
					$cont_cads=0;					
					$nivel1=$resumen_precio[$cont_regs]['SEGMENTO'];
					array_push($body,(object)$fila);
					$fila=array_fill(0,$num_cads+3,'-');					
				}
				if($cont_regs==$num_regs)		
				{					
					$columna_precio=array_search($resumen_precio[$cont_regs-1]['CADENA'],$cadenas);
					$fila[$columna_precio+2]=round($resumen_precio[$cont_regs-1]['PRECIO'],1);					
					$fila[$num_cads+2]=number_format(round($totales_segmento[$cont_totales_segmento]['PRECIO'],1),1,',','.');					
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
				$columna_precio=array_search($totales_categoria[$cont_regs]['CADENA'],$cadenas);					
				// Mientras no cambie la categoria
				if($num_regs==1)		
				{						
					$columna_precio=array_search($totales_categoria[$cont_regs]['CADENA'],$cadenas);
					$fila[$columna_precio]=number_format(round($totales_categoria[$cont_regs]['PRECIO'],1),1,',','.');										
					$fila[$num_cads]=number_format(round($totales_horizontales_categoria[$cont_totales_horizontales_categoria]['PRECIO'],1),1,',','.');					
					array_push($matriz_totales,(object)$fila);		
					$cont_regs++;
					break;
				}
				if($nivel2==$totales_categoria[$cont_regs]['CATEGORIA'])
				{					
					$fila[$columna_precio]=number_format(round($totales_categoria[$cont_regs]['PRECIO'],1),1,',','.');					
					$cont_regs++;
				}
				else
				{
					$fila[$num_cads]=number_format(round($totales_horizontales_categoria[$cont_totales_horizontales_categoria]['PRECIO'],1),1,',','.');
					$cont_totales_horizontales_categoria++;
					array_push($matriz_totales,$fila);
					$fila=array_fill(0,$num_cads,"-");
					$nivel2=$totales_categoria[$cont_regs]['CATEGORIA'];					
				}
				if($cont_regs==$num_regs)		
				{						
					$columna_precio=array_search($totales_categoria[$cont_regs-1]['CADENA'],$cadenas);
					$fila[$columna_precio]=number_format(round($totales_categoria[$cont_regs-1]['PRECIO'],1),1,',','.');										
					$fila[$num_cads]=number_format(round($totales_horizontales_categoria[$cont_totales_horizontales_categoria]['PRECIO'],1),1,',','.');					
					array_push($matriz_totales,(object)$fila);		
					$cont_regs++;					
				}				
			}			

			$cont_regs=0;
			$num_regs=count($totales_verticales_categoria);
			$fila=array_fill(0,$num_cads,"-");				
			
			while($cont_regs<$num_regs)
			{
				$columna_precio=array_search($totales_verticales_categoria[$cont_regs]['CADENA'],$cadenas);					
				// Mientras no cambie la cadena  
				$fila[$columna_precio]=number_format(round($totales_verticales_categoria[$cont_regs]['PRECIO'],1),1,',','.');					
				$cont_regs++;
			}	
			
			$fila[$num_cads]=round($total[0]['PRECIO'],1);
			
			// print_r($fila);
			
			array_push($matriz_totales,$fila);			
						
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
			
		$sql = "SELECT (SUM(case when ABS(pr.PRECIO-p.POLITICAPRECIO)>pa.VALOR*p.POLITICAPRECIO/100 then 1 else 0 END)*100.0)/COUNT(pr.ID) as PRECIO, ni.NOMBRE as SEGMENTO, ni2.NOMBRE as CATEGORIA, cad.NOMBRE as CADENA FROM PRECIO pr
			INNER JOIN PLANOGRAMAP p on p.ID = pr.PLANOGRAMAP_ID and pr.PRECIO is not null and p.POLITICAPRECIO is not null
			INNER JOIN MEDICION m on m.ID = p.MEDICION_ID and m.ID = {$medicion}
			INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID
			INNER JOIN SALA s on s.ID = sc.SALA_ID and s.COMUNA_ID in( {$comunas} )
			INNER JOIN CADENA cad on cad.ID = s.CADENA_ID
			INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID AND ic.CLIENTE_ID = {$user->getClienteID()}
			INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID
			INNER JOIN NIVELITEM ni2 on ni2.ID = ic.NIVELITEM_ID2	
			INNER JOIN PARAMETRO pa on pa.CLIENTE_ID = {$user->getClienteID()} and pa.NOMBRE='rango_precio'			
			GROUP BY ni2.NOMBRE, ni.NOMBRE, cad.NOMBRE
			ORDER BY categoria, segmento";			
				
		$resumen_precio = $em->getConnection()->executeQuery($sql)->fetchAll();						
		
		// Variable para saber cuantos niveles de agregacion define el cliente, esto debe ser parametrizado en una etapa posterior
		$niveles=2;										
				
		$head=array();
		$cadenas=array();		
		$cadenas_aux=array();		
		
		// Generamos el head de la tabla, y las cadenas
		foreach($resumen_precio as $registro)
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
			$prefixes=array('SKU/CADENA');
		else
			$prefixes=array('SKU/CADENA','SEGMENTO');
		
		$head=array();		
		$aoColumns=array();
		
		foreach($cadenas_aux as $cadena)
		{
			array_push($cadenas,$cadena);
			array_push($head,$cadena);						
		}		
		
		foreach(array_reverse($prefixes) as $prefix)		
			array_unshift($head,$prefix);		
		array_push($head,'TOTAL');				
				
		// Obtener totales horizontales por segmento					
		
		$sql =	"SELECT ni.NOMBRE as segmento, ni2.NOMBRE as categoria, (SUM(case when ABS(pr.PRECIO-p.POLITICAPRECIO)>pa.VALOR*p.POLITICAPRECIO/100 then 1 else 0 END)*100.0)/COUNT(pr.ID) as PRECIO FROM PRECIO pr
		INNER JOIN PLANOGRAMAP p on p.ID = pr.PLANOGRAMAP_ID AND p.MEDICION_ID = {$medicion} and pr.PRECIO is not null and p.POLITICAPRECIO is not null
		INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID
		INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID
		INNER JOIN NIVELITEM ni2 on ni2.ID = ic.NIVELITEM_ID2
		INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID
		INNER JOIN SALA s on s.ID = sc.SALA_ID and s.COMUNA_ID in( {$comunas} )
		INNER JOIN CADENA c on c.ID = s.CADENA_ID
		INNER JOIN PARAMETRO pa on pa.CLIENTE_ID = {$user->getClienteID()} and pa.NOMBRE='rango_precio'			
		GROUP BY ni.NOMBRE, ni2.NOMBRE
		ORDER BY categoria, segmento";
	
		$totales_segmento = $em->getConnection()->executeQuery($sql)->fetchAll();		

		// Obtener totales verticales por categoria
					
		$sql =	"SELECT ni.NOMBRE as CATEGORIA, c.NOMBRE as CADENA, (SUM(case when ABS(pr.PRECIO-p.POLITICAPRECIO)>pa.VALOR*p.POLITICAPRECIO/100 then 1 else 0 END)*100.0)/COUNT(pr.ID) as PRECIO FROM PRECIO pr
		INNER JOIN PLANOGRAMAP p on p.ID = pr.PLANOGRAMAP_ID AND p.MEDICION_ID = {$medicion} and pr.PRECIO is not null and p.POLITICAPRECIO is not null
		INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID
		INNER JOIN SALA s on s.ID = sc.SALA_ID and s.COMUNA_ID in( {$comunas} )
		INNER JOIN CADENA c on c.ID = s.CADENA_ID
		INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID
		INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID2
		INNER JOIN PARAMETRO pa on pa.CLIENTE_ID = {$user->getClienteID()} and pa.NOMBRE='rango_precio'			
		GROUP BY ni.NOMBRE, c.NOMBRE
		ORDER BY CATEGORIA,CADENA";
	
		$totales_categoria = $em->getConnection()->executeQuery($sql)->fetchAll();
						
		// Obtener totales horizontales por totales segmento (ultima columna de totales verticales por categoria)				
				
		$sql =	"SELECT ni.NOMBRE as CATEGORIA, (SUM(case when ABS(pr.PRECIO-p.POLITICAPRECIO)>pa.VALOR*p.POLITICAPRECIO/100 then 1 else 0 END)*100.0)/COUNT(pr.ID) as PRECIO FROM PRECIO pr
				INNER JOIN PLANOGRAMAP p on p.ID = pr.PLANOGRAMAP_ID AND p.MEDICION_ID = {$medicion} and pr.PRECIO is not null and p.POLITICAPRECIO is not null
				INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID
				INNER JOIN SALA s on s.ID = sc.SALA_ID and s.COMUNA_ID in( {$comunas} )
				INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID
				INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID2
				INNER JOIN PARAMETRO pa on pa.CLIENTE_ID = {$user->getClienteID()} and pa.NOMBRE='rango_precio'			
				GROUP BY ni.NOMBRE
				ORDER BY CATEGORIA";				
			
		$totales_horizontales_categoria = $em->getConnection()->executeQuery($sql)->fetchAll();	
		
		// Obtener totales verticales por totales categoria				
		
		$sql = "SELECT  c.NOMBRE as CADENA, (SUM(case when ABS(pr.PRECIO-p.POLITICAPRECIO)>pa.VALOR*p.POLITICAPRECIO/100 then 1 else 0 END)*100.0)/COUNT(pr.ID) as PRECIO FROM PRECIO pr
		INNER JOIN PLANOGRAMAP p on p.ID = pr.PLANOGRAMAP_ID AND p.MEDICION_ID = {$medicion} and pr.PRECIO is not null and p.POLITICAPRECIO is not null
		INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID
		INNER JOIN SALA s on s.ID = sc.SALA_ID and s.COMUNA_ID in( {$comunas} )
		INNER JOIN CADENA c on c.ID = s.CADENA_ID
		INNER JOIN PARAMETRO pa on pa.CLIENTE_ID = {$user->getClienteID()} and pa.NOMBRE='rango_precio'			
		GROUP BY c.NOMBRE
		ORDER BY CADENA";		
		
		$totales_verticales_categoria = $em->getConnection()->executeQuery($sql)->fetchAll();							
		
		// Obtener total horizontal por totales verticales por totales categoria				
		
		$sql = "SELECT (SUM(case when ABS(pr.PRECIO-p.POLITICAPRECIO)>pa.VALOR*p.POLITICAPRECIO/100 then 1 else 0 END)*100.0)/COUNT(pr.ID) as PRECIO FROM PRECIO pr
		INNER JOIN PLANOGRAMAP p on p.ID = pr.PLANOGRAMAP_ID AND p.MEDICION_ID = {$medicion} and pr.PRECIO is not null and p.POLITICAPRECIO is not null
		INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID
		INNER JOIN SALA s on s.ID = sc.SALA_ID and s.COMUNA_ID in( {$comunas} )
		INNER JOIN PARAMETRO pa on pa.CLIENTE_ID = {$user->getClienteID()} and pa.NOMBRE='rango_precio'";					

		$total = $em->getConnection()->executeQuery($sql)->fetchAll();									
		
		// Guardamos resultado de consulta en variables de sesión para reusarlas en un action posterior
		$session->set("cadenas",$cadenas);		
		$session->set("resumen_precio",$resumen_precio);	
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
			"max_width" => $max_width,
			'aoColumns' => $aoColumns
		);		
		return new JsonResponse($output);		
	}

	public function detalleheaderAction(Request $request)
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
		
		$f_cadena=json_decode(stripslashes($parametros['cadenas']));
		$f_segmento=json_decode(stripslashes($parametros['segmentos']));
		$f_categoria=json_decode(stripslashes($parametros['categorias']));
		
		$cadenas='';
		foreach($f_cadena as $cadena)
			$cadenas.="'".$cadena."',";
		$cadenas = trim($cadenas, ',');				
		
		$segmentos='';
		foreach($f_segmento as $segmento)
			$segmentos.="'".$segmento."',";
		$segmentos = trim($segmentos, ',');		
		
		$and_segmento='';
		$and_categoria='';
		
		if(strlen($segmentos)>0)		
			$and_segmento="and ni.NOMBRE in ({$segmentos})";		
		
		$categorias='';
		foreach($f_categoria as $categoria)
			$categorias.="'".$categoria."',";
		$categorias = trim($categorias, ',');		
		
		if(strlen($categorias)>0)		
			$and_categoria="and ni2.NOMBRE in ({$categorias})";		

		//23 SEG
		$start = microtime(true);
		$sql = "SELECT precio as precio, p.POLITICAPRECIO as politica, ic.CODIGOITEM1 as COD_PRODUCTO,i.NOMBRE as NOM_PRODUCTO,ni.NOMBRE as SEGMENTO, ni2.NOMBRE as CATEGORIA, ISNULL(sc.CODIGOSALA, UPPER(cad.NOMBRE+' '+com.NOMBRE+' '+s.CALLE+' '+s.NUMEROCALLE)) as ID_SALA, ISNULL(sc.CODIGOSALA,'-') as COD_SALA, UPPER(cad.NOMBRE+' '+com.NOMBRE+' '+s.CALLE+' '+s.NUMEROCALLE) as NOM_SALA FROM PRECIO pr
				INNER JOIN PLANOGRAMAP p on p.ID = pr.PLANOGRAMAP_ID and p.MEDICION_ID = {$medicion} and pr.PRECIO is not null and p.POLITICAPRECIO is not null
				INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID and sc.CLIENTE_ID = {$user->getClienteID()}
				INNER JOIN SALA s on s.ID = sc.SALA_ID and s.COMUNA_ID in ({$comunas}) 
				INNER JOIN CADENA c on s.CADENA_ID= c.ID and c.NOMBRE in ({$cadenas})
				INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID
				INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID {$and_segmento} 
				INNER JOIN NIVELITEM ni2 on ic.NIVELITEM_ID2 = ni2.ID {$and_categoria}
				INNER JOIN COMUNA com on s.COMUNA_ID=com.ID
				INNER JOIN CADENA cad on s.CADENA_ID=cad.ID	
				INNER JOIN ITEM i on i.ID = ic.ITEM_ID	
				INNER JOIN PARAMETRO pa on pa.CLIENTE_ID = {$user->getClienteID()} and pa.NOMBRE='rango_precio'
				ORDER BY SEGMENTO,NOM_PRODUCTO,COD_PRODUCTO,NOM_SALA";								
		
		$sha1 = sha1($sql);
		
		// print_r($sql);

		if(!$session->has($sha1)){
			$detalle_precio = $em->getConnection()->executeQuery($sql)->fetchAll();
			$session->set($sha1,$detalle_precio);
		}
		else $detalle_precio = $session->get($sha1);
		$time_taken = microtime(true) - $start;
		//return $time_taken*1000;
								
		// Variable para saber cuantos niveles de agregacion define el cliente, esto debe ser parametrizado en una etapa posterior
		$niveles=2;												
						
		$head=array();
		$salas=array();		
		$salas_aux=array();		
		
		// Generamos el head de la tabla, y las salas
		foreach($detalle_precio as $registro)
		{			
			$fila=array();						
			
			if(!in_array($registro['ID_SALA'],$head))
			{
				array_push($head,$registro['ID_SALA']);
				$fila['ID_SALA']=$registro['ID_SALA'];
				$fila['COD_SALA']=$registro['COD_SALA'];
				$fila['NOM_SALA']=$registro['NOM_SALA'];
				array_push($salas_aux,$fila);
			}					
		}						
		// Ordenamos la estructura usando comparador personalizado
		usort($salas_aux, array($this,"sortFunction"));		
		
		// CONSTRUIR EL ENCABEZADO DE LA TABLA
			
		$prefixes=array('DESCRIPCIÓN','POLÍTICA','POLÍTICA');
		
		$head=array();
		
		// Oonstruir inicialización de columnas
		$aoColumnDefs=array();
		
		$fila=array();
		$fila['aTargets']=array(0);
		$fila['sClass']="tag";
		$fila['sWidth']="160px";		
		array_push($aoColumnDefs,$fila);
		
		$fila=array();
		$fila['aTargets']=array(1);
		// $fila['sClass']="tag";
		// $fila['sWidth']="260px";
		$fila['bVisible']=false;	
		array_push($aoColumnDefs,$fila);		

		$fila=array();
		$fila['aTargets']=array(2);		
		$fila['sWidth']="20px";
		array_push($aoColumnDefs,$fila);	

		$cont=3;						
		
		foreach($salas_aux as $sala)
		{
			$fila=array();
			$fila['cod_sala']=$sala['COD_SALA'];
			$fila['nom_sala']=$sala['NOM_SALA'];			
			array_push($salas,$sala['ID_SALA']);		
			array_push($head,$fila);
			$fila=array();
			$fila['aTargets']=array($cont);		
			// $fila['sWidth']="3%";
			array_push($aoColumnDefs,$fila);
			$cont++;			
			// $head[$sala['COD_SALA']]=$sala['NOM_SALA'];											
		}	

		$fila=array();
		$fila['aTargets']=array($cont);		
		// $fila['sWidth']="2%";		
		array_push($aoColumnDefs,$fila);
		
		foreach(array_reverse($prefixes) as $prefix)		
			array_unshift($head,$prefix);		
		array_push($head,'TOTAL');						
		
		// Guardamos resultado de consulta en variable de sesión para reusarlas en un action posterior
		$session->set("salas",$salas);		
		$session->set("detalle_precio",$detalle_precio);			
		$session->set("flag",true);		
		// Calcula el ancho máximo de la tabla	
		$extension=count($head)*(12+log(count($head),10))-100;
	
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
			'aoColumnDefs' => json_encode($aoColumnDefs),					
		);		
		return new JsonResponse($output);		
	}
	
	public function detallebodyAction(Request $request)
	{
		$start = microtime(true);
		// Recuperar el usuario y datos de sesión
		$user = $this->getUser();
		$em = $this->getDoctrine()->getManager();
		$session=$this->get("session");			
		$salas=$session->get("salas");				
		$total=$session->get("total");			
		$detalle_precio=$session->get("detalle_precio");		
		$flag=$session->get("flag");		
		
		// CONSTRUIR EL CUERPO DE LA TABLA						
		$body=array();									
		$num_regs=count($detalle_precio);		
		$cont_salas=0;
		$cont_regs=0;
		$num_salas=count($salas);			
		$matriz_totales=array();		

		$variable=$session->get("variable");										
				
		if($num_regs>0 && $flag)
		{
			$nivel1=$detalle_precio[$cont_regs]['COD_PRODUCTO'];		
			// Lleno la fila con vacios, le agrego 1 posiciones, correspondientes al total					
			$fila=array_fill(0,$num_salas+3," ");								
			$nivel2=$detalle_precio[$cont_regs]['SEGMENTO'];																								
			$cont_totales_producto=0;				
		
			while($cont_regs<$num_regs)
			{	// Lleno la fila con vacios, le agrego 3 posiciones, correspondientes a los niveles de agregación y al total	
				$columna_precio=array_search($detalle_precio[$cont_regs]['ID_SALA'],$salas);	
						
				// Mientras el primer nivel de agregación no cambie			
				if($nivel1==$detalle_precio[$cont_regs]['COD_PRODUCTO'])
				{									
					$fila[2]=$detalle_precio[$cont_regs]['SEGMENTO'];	
					$fila[0]=$detalle_precio[$cont_regs]['NOM_PRODUCTO'];//.' ['.$detalle_quiebre[$cont_regs]['COD_PRODUCTO'].']';										
					$fila[1]=$detalle_precio[$cont_regs]['politica'];
					$fila[$columna_precio+3]=$detalle_precio[$cont_regs]['precio'];//.' ['.$detalle_quiebre[$cont_regs]['COD_PRODUCTO'].']';																														
					$cont_regs++;						
				}	
				else
				{ // Si el primer nivel de agregacion cambió, lo actualizo, agrego la fila al body y reseteo el contador de cadenas												
					$fila[$num_salas+3]=0;					
					// $cont_totales_producto++;																			
					$nivel1=$detalle_precio[$cont_regs]['COD_PRODUCTO'];
					array_push($body,$fila);
					$fila=array_fill(0,$num_salas+3," ");	
				}
				if($cont_regs==$num_regs)		
				{						
					$columna_precio=array_search($detalle_precio[$cont_regs-1]['COD_SALA'],$salas);	
					$fila[$columna_precio+3]=$detalle_precio[$cont_regs-1]['precio'];														
					$fila[$num_salas+3]=0;					
					// $cont_totales_producto++;								
					array_push($body,$fila);
					$cont_regs++;
				}			
			}				
		}

		$cont_regs=0;
		
		while($cont_regs<$num_regs)
		{
			$fila=array_fill(0,$num_salas+3,0);	
			array_push($matriz_totales,$fila);
			$cont_regs++;
		}
		/*
		 * Output
		 */
		// $session->close();
		$time_taken = microtime(true) - $start;
		$output = array(
			"sEcho" => intval($_POST['sEcho']),
			"iTotalRecords" => count($detalle_precio),
			"iTotalDisplayRecords" => count($body),
			"aaData" => $body,
			"matriz_totales" => $matriz_totales,
			"time_taken" => $time_taken*1000
		);		
		return new JsonResponse($output);		
	}
	
}