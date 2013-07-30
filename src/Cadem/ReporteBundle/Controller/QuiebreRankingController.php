<?php

namespace Cadem\ReporteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\HttpFoundation\Session;

class QuiebreRankingController extends Controller
{
    
	public function indexAction($variable)
    {
		$variable_medida = $variable;
		$user = $this->getUser();
		$em = $this->getDoctrine()->getManager();
		$session = $this->get("session");
		//CLIENTE Y ESTUDIO, LOGO
		$query = $em->createQuery(
			'SELECT c,e FROM CademReporteBundle:Cliente c
			JOIN c.estudios e
			JOIN c.usuarios u
			WHERE u.id = :id AND c.activo = 1 AND e.activo = 1')
			->setParameter('id', $user->getId());
		$clientes = $query->getResult();
		$cliente = $clientes[0];
		$id_cliente = $cliente->getId();
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
			WHERE sc.clienteid = :id')
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
			WHERE sc.clienteid = :id')
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
			WHERE sc.clienteid = :id')
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
			WHERE e.clienteid = :id AND ev.variableid = :id_ev
			ORDER BY m.fechainicio DESC')
			->setParameter('id', $cliente->getId())
			->setParameter('id_ev', 1);
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

	
		$estudio_variable=$estudios[0]->getEstudiovariables();	
		
		$variable=$estudio_variable[0]->getVariable()->getId();
				
		$session->set("variable",$variable);
		
		switch($variable)
		{
			case 1: // Si el tag de la variable es quiebre ordenamos por % de quiebre ascendente
				$order=' ASC';
				break;
			case 5: // Si el tag de la variable es presencia ordenamos por % de quiebre descendente
				$order=' DESC';
				break;			
		}
		
		//RANKING POR SALA--------------------------------------------------------------------
		$sql = "DECLARE @id_cliente integer = :id_cliente;
		SELECT TOP(20)*,rank() OVER (ORDER BY quiebre {$order}) as rank, ROUND(quiebre-quiebre_anterior, 1) as diferencia FROM 
(SELECT s.id, s.calle, s.numerocalle, sc.codigosala, cad.nombre as cadena, (SUM(case when q.hayquiebre = 1 then 1 else 0 END)*100.0)/COUNT(q.id) as quiebre FROM SALA s
			INNER JOIN SALACLIENTE sc on s.ID = sc.SALA_ID AND sc.CLIENTE_ID = @id_cliente AND sc.MEDICION_ID = :id_medicion_actual
			INNER JOIN CADENA cad on cad.ID = s.CADENA_ID
			INNER JOIN PLANOGRAMAQ p on p.SALACLIENTE_ID = sc.ID AND p.MEDICION_ID = :id_medicion_actual
			INNER JOIN QUIEBRE q on q.PLANOGRAMAQ_ID = p.ID
			GROUP BY sc.id, s.id, s.calle, s.numerocalle, sc.codigosala, cad.nombre
			) AS A LEFT JOIN
			
(SELECT s.id as id2, (SUM(case when q.hayquiebre = 1 then 1 else 0 END)*100.0)/COUNT(q.id) as quiebre_anterior FROM SALA s
			INNER JOIN SALACLIENTE sc on s.ID = sc.SALA_ID AND sc.CLIENTE_ID = @id_cliente AND sc.MEDICION_ID = :id_medicion_anterior
			INNER JOIN PLANOGRAMAQ p on p.SALACLIENTE_ID = sc.ID
			INNER JOIN QUIEBRE q on q.PLANOGRAMAQ_ID = p.ID AND p.MEDICION_ID = :id_medicion_anterior
			GROUP BY sc.id, s.id, s.calle, s.numerocalle, sc.codigosala
			) AS B on A.ID = B.id2
			ORDER BY rank";
		$param = array('id_cliente' => $id_cliente, 'id_medicion_actual' => $id_medicion_actual, 'id_medicion_anterior' => $id_medicion_anterior);
		$ranking_sala = $em->getConnection()->executeQuery($sql,$param)->fetchAll();
		
		//RANKING POR PRODUCTO-----------------------------------------------
		$sql = "DECLARE @id_cliente integer = :id_cliente;
		SELECT TOP(20)*,rank() OVER (ORDER BY quiebre {$order}) as rank, ROUND(quiebre-quiebre_anterior, 1) as diferencia FROM 
(SELECT ic.CODIGOITEM1 as id, ic.codigoitem1, i.nombre,(SUM(case when q.hayquiebre = 1 then 1 else 0 END)*100.0)/COUNT(q.id) as quiebre FROM SALACLIENTE sc
			INNER JOIN PLANOGRAMAQ p on p.SALACLIENTE_ID = sc.ID AND p.MEDICION_ID = :id_medicion_actual AND sc.CLIENTE_ID = @id_cliente
			INNER JOIN QUIEBRE q on q.PLANOGRAMAQ_ID = p.ID
			INNER JOIN ITEMCLIENTE ic on p.ITEMCLIENTE_ID = ic.ID AND ic.MEDICION_ID = :id_medicion_actual
			INNER JOIN ITEM i on i.ID = ic.ITEM_ID
			GROUP BY ic.id, ic.codigoitem1, i.nombre
			) AS A LEFT JOIN						
			
(SELECT ic.CODIGOITEM1 as id2, ic.ID as id3, (SUM(case when q.hayquiebre = 1 then 1 else 0 END)*100.0)/COUNT(q.id) as quiebre_anterior FROM SALACLIENTE sc
			INNER JOIN PLANOGRAMAQ p on p.SALACLIENTE_ID = sc.ID AND p.MEDICION_ID = :id_medicion_anterior AND sc.CLIENTE_ID = @id_cliente
			INNER JOIN QUIEBRE q on q.PLANOGRAMAQ_ID = p.ID
			INNER JOIN ITEMCLIENTE ic on p.ITEMCLIENTE_ID = ic.ID AND ic.MEDICION_ID = :id_medicion_anterior
			GROUP BY ic.id, ic.CODIGOITEM1
			) AS B on A.ID = B.ID2
			ORDER BY rank ";
		$param = array('id_cliente' => $id_cliente, 'id_medicion_actual' => $id_medicion_actual, 'id_medicion_anterior' => $id_medicion_anterior);
		$ranking_item = $em->getConnection()->executeQuery($sql,$param)->fetchAll();
		
		
		// RANKING POR VENDEDOR--------------------------------------------------
		$sql = "DECLARE @id_cliente integer = :id_cliente;
		SELECT  TOP(20) *,rank() OVER (ORDER BY quiebre {$order}) as rank, ROUND(quiebre-quiebre_anterior, 1) as diferencia FROM 
(SELECT e.id, e.nombre, car.nombre as cargo, (SUM(case when q.hayquiebre = 1 then 1 else 0 END)*100.0)/COUNT(q.id) as quiebre FROM SALACLIENTE sc
			INNER JOIN PLANOGRAMAQ p on p.SALACLIENTE_ID = sc.ID AND p.MEDICION_ID = :id_medicion_actual AND sc.CLIENTE_ID = @id_cliente
			INNER JOIN QUIEBRE q on q.PLANOGRAMAQ_ID = p.ID
			INNER JOIN EMPLEADO e on e.ID = sc.EMPLEADO_ID
			INNER JOIN CARGO car on car.ID = e.CARGO_ID
			GROUP BY e.ID, e.NOMBRE, car.nombre
			) AS A LEFT JOIN
			
(SELECT e.id as id2, (SUM(case when q.hayquiebre = 1 then 1 else 0 END)*100.0)/COUNT(q.id) as quiebre_anterior FROM SALACLIENTE sc
			INNER JOIN PLANOGRAMAQ p on p.SALACLIENTE_ID = sc.ID AND p.MEDICION_ID = :id_medicion_anterior AND sc.CLIENTE_ID = @id_cliente
			INNER JOIN QUIEBRE q on q.PLANOGRAMAQ_ID = p.ID
			INNER JOIN EMPLEADO e on e.ID = sc.EMPLEADO_ID
			INNER JOIN CARGO car on car.ID = e.CARGO_ID
			GROUP BY e.ID
			) AS B on A.ID = B.ID2
			ORDER BY rank";
			
		$param = array('id_cliente' => $id_cliente, 'id_medicion_actual' => $id_medicion_actual, 'id_medicion_anterior' => $id_medicion_anterior);
		$ranking_empleado = $em->getConnection()->executeQuery($sql,$param)->fetchAll();
		

		$muestrarankingempleado = $this->get('cadem_reporte.helper.cliente')->MuestraRankingEmpleado();
		
		
		//RESPONSE
		$response = $this->render('CademReporteBundle:Ranking:index.html.twig',
			array(
				'forms' => array(
					'form_estudio' 	=> $form_estudio->createView(),
					'form_periodo' 	=> $form_periodo->createView(),
					'form_region' 	=> $form_region->createView(),
					'form_provincia' => $form_provincia->createView(),
					'form_comuna' 	=> $form_comuna->createView(),
					),
				'logofilename' => $logofilename,
				'logostyle' => $logostyle,
				'ranking_sala' => $ranking_sala,
				'ranking_empleado' => $ranking_empleado,
				'ranking_item' => $ranking_item,
				'estudios' => $estudios,
				'variable' => strtolower($variable_medida),
				'muestrarankingempleado' => $muestrarankingempleado,
			)
		);

		//CACHE
		$response->setPrivate();
		$response->setMaxAge(1);


		return $response;
    }
	
	public function filtrosAction(Request $request)
    {
		// $cacheDriver = new \Doctrine\Common\Cache\ApcCache();
		// $cacheseg = 0;
		$start = microtime(true);
		$user = $this->getUser();
		$em = $this->getDoctrine()->getManager();
		$data = $request->query->all();
		$id_cliente = $user->getClienteID();
		
		//DATOS
		$id_medicion_actual = intval($data['f_periodo']['Periodo']);
		$id_estudio = intval($data['f_estudio']['Estudio']);// 0 = TODOS
		// $array_region = $data['f_region']['Region'];
		// $array_provincia = $data['f_provincia']['Provincia'];
		$array_comuna = $data['f_comuna']['Comuna'];
		foreach($array_comuna as $k => $v) $array_comuna[$k] = intval($v);
		//BUSQUEDA
		if(isset($data['search_sala']) && $data['search_sala'] != ''){
			$where_sala = "  
			codigosala LIKE '%' + @search_sala +'%' OR 
			cadena LIKE '%' + @search_sala +'%' OR
			calle LIKE '%' + @search_sala +'%' ";
			$search_sala = $data['search_sala'];
		}
		else{
			$where_sala = "1=1";
			$search_sala = "";
		}

		if(isset($data['search_item']) && $data['search_item'] != ''){
			$where_item = "  
			codigoitem1 LIKE '%' + @search_item +'%' OR 
			nombre LIKE '%' + @search_item +'%' ";
			$search_item = $data['search_item'];
		}
		else{
			$where_item = "1=1";
			$search_item = "";
		}

		if(isset($data['search_empleado']) && $data['search_empleado'] != ''){
			$where_empleado = "  
			nombre LIKE '%' + @search_empleado +'%' OR 
			cargo LIKE '%' + @search_empleado +'%' ";
			$search_empleado = $data['search_empleado'];
		}
		else{
			$where_empleado = "1=1";
			$search_empleado = "";
		}
		
		//SE BUSCA MEDICION ANTERIOR
		$id_medicion_anterior = $this->get('cadem_reporte.helper.medicion')->getIdMedicionAnterior($id_medicion_actual, 'QUIEBRE');

		$session=$this->get("session");
		$variable=$session->get("variable");
		
		switch($variable)
		{
			case 1: // Si el tag de la variable es quiebre ordenamos por % de quiebre ascendente
				if($data['tb_sala'] === 't') $orderby_sala = "ASC";
				else $orderby_sala = "DESC";
				if($data['tb_producto'] === 't') $orderby_producto = "ASC";
				else $orderby_producto = "DESC";
				if($data['tb_empleado'] === 't') $orderby_empleado = "ASC";
				else $orderby_empleado = "DESC";
				break;
			case 5: // Si el tag de la variable es presencia ordenamos por % de quiebre descendente
				if($data['tb_sala'] === 't') $orderby_sala = "DESC";
				else $orderby_sala = "ASC";
				if($data['tb_producto'] === 't') $orderby_producto = "DESC";
				else $orderby_producto = "ASC";
				if($data['tb_empleado'] === 't') $orderby_empleado = "DESC";
				else $orderby_empleado = "ASC";
				break;
		}
		
		
		//RANKING POR SALA--------------------------------------------------------------------
		$sql = "DECLARE @search_sala varchar(128) = ? ;DECLARE @id_cliente integer = ? ; DECLARE @id_medicion_actual integer = ? ;  DECLARE @id_medicion_anterior integer = ? ;
		SELECT TOP(20)* FROM (
		(SELECT TOP(1000)*,rank() OVER (ORDER BY quiebre {$orderby_sala}) as rank, ROUND(quiebre-quiebre_anterior, 1) as diferencia FROM 
(SELECT s.id, s.calle, s.numerocalle, sc.codigosala, cad.nombre as cadena, (SUM(case when q.hayquiebre = 1 then 1 else 0 END)*100.0)/COUNT(q.id) as quiebre FROM SALA s
			INNER JOIN SALACLIENTE sc on s.ID = sc.SALA_ID AND sc.CLIENTE_ID = @id_cliente AND sc.MEDICION_ID = @id_medicion_actual AND s.COMUNA_ID IN ( ? )
			INNER JOIN CADENA cad on cad.ID = s.CADENA_ID
			INNER JOIN PLANOGRAMAQ p on p.SALACLIENTE_ID = sc.ID AND p.MEDICION_ID = @id_medicion_actual
			INNER JOIN QUIEBRE q on q.PLANOGRAMAQ_ID = p.ID
			GROUP BY sc.id, s.id, s.calle, s.numerocalle, sc.codigosala, cad.nombre
			) AS A LEFT JOIN
			
(SELECT s.id as id2, (SUM(case when q.hayquiebre = 1 then 1 else 0 END)*100.0)/COUNT(q.id) as quiebre_anterior FROM SALA s
			INNER JOIN SALACLIENTE sc on s.ID = sc.SALA_ID AND sc.CLIENTE_ID = @id_cliente AND sc.MEDICION_ID = @id_medicion_anterior
			INNER JOIN PLANOGRAMAQ p on p.SALACLIENTE_ID = sc.ID AND p.MEDICION_ID = @id_medicion_anterior
			INNER JOIN MEDICION m on m.ID = p.MEDICION_ID
			INNER JOIN QUIEBRE q on q.PLANOGRAMAQ_ID = p.ID
			GROUP BY sc.id, s.id, s.calle, s.numerocalle, sc.codigosala
			) AS B on A.ID = B.id2
			ORDER BY rank)) as t
			WHERE 1=1 AND ( {$where_sala} )";
		$param = array($search_sala, $id_cliente, $id_medicion_actual, $id_medicion_anterior, $array_comuna);
		$tipo_param = array(\PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
		$ranking_sala = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();
		//CACHE
		// $s1 = sha1($sql.print_r($param,true));
		// if($cacheDriver->contains($s1)) $ranking_sala = $cacheDriver->fetch($s1);
		// else
		// {
			// $ranking_sala = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();
			// $cacheDriver->save($s1, $ranking_sala, $cacheseg);
		// }
					
		
		//RANKING POR PRODUCTO-----------------------------------------------
		$sql = "DECLARE @search_item varchar(128) = ? ;DECLARE @id_cliente integer = ? ; DECLARE @id_medicion_actual integer = ? ;  DECLARE @id_medicion_anterior integer = ? ;
		SELECT TOP(20)* FROM (
		(SELECT TOP(1000)*, rank() OVER (ORDER BY quiebre {$orderby_producto}) as rank, ROUND(quiebre-quiebre_anterior, 1) as diferencia FROM 
(SELECT ic.CODIGOITEM1 as id, ic.codigoitem1, i.nombre,(SUM(case when q.hayquiebre = 1 then 1 else 0 END)*100.0)/COUNT(q.id) as quiebre FROM SALACLIENTE sc
			INNER JOIN SALA s on s.ID = sc.SALA_ID AND sc.CLIENTE_ID = @id_cliente AND sc.MEDICION_ID = @id_medicion_actual AND s.COMUNA_ID IN ( ? )
			INNER JOIN PLANOGRAMAQ p on p.SALACLIENTE_ID = sc.ID AND p.MEDICION_ID = @id_medicion_actual
			INNER JOIN QUIEBRE q on q.PLANOGRAMAQ_ID = p.ID 
			INNER JOIN ITEMCLIENTE ic on p.ITEMCLIENTE_ID = ic.ID AND ic.MEDICION_ID = @id_medicion_actual
			INNER JOIN ITEM i on i.ID = ic.ITEM_ID
			GROUP BY ic.id, ic.codigoitem1, i.nombre
			) AS A LEFT JOIN
			
(SELECT ic.CODIGOITEM1 as id2, (SUM(case when q.hayquiebre = 1 then 1 else 0 END)*100.0)/COUNT(q.id) as quiebre_anterior FROM SALACLIENTE sc
			INNER JOIN PLANOGRAMAQ p on p.SALACLIENTE_ID = sc.ID AND sc.CLIENTE_ID = @id_cliente AND sc.MEDICION_ID = @id_medicion_anterior AND p.MEDICION_ID = @id_medicion_anterior
			INNER JOIN QUIEBRE q on q.PLANOGRAMAQ_ID = p.ID
			INNER JOIN ITEMCLIENTE ic on p.ITEMCLIENTE_ID = ic.ID AND ic.MEDICION_ID = @id_medicion_anterior
			GROUP BY ic.id, ic.CODIGOITEM1
			) AS B on A.ID = B.ID2
			ORDER BY rank)) as t
			WHERE 1=1 AND ( {$where_item} )";
		$param = array($search_item, $id_cliente, $id_medicion_actual, $id_medicion_anterior, $array_comuna);
		$tipo_param = array(\PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
		$ranking_item = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();
		//CACHE
		// $s1 = sha1($sql.print_r($param,true));
		// if($cacheDriver->contains($s1)) $ranking_item = $cacheDriver->fetch($s1);
		// else
		// {
			// $ranking_item = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();
			// $cacheDriver->save($s1, $ranking_item, $cacheseg);
		// }
		
		
		// RANKING POR VENDEDOR--------------------------------------------------
		$sql = "DECLARE @search_empleado varchar(128) = ? ;DECLARE @id_cliente integer = ?; DECLARE @id_medicion_actual integer = ? ; DECLARE @id_medicion_anterior integer = ? ;
		SELECT TOP(20)* FROM (
		(SELECT  TOP(1000) *, rank() OVER (ORDER BY quiebre {$orderby_empleado}) as rank, ROUND(quiebre-quiebre_anterior, 1) as diferencia FROM 
(SELECT e.id, e.nombre, car.nombre as cargo, (SUM(case when q.hayquiebre = 1 then 1 else 0 END)*100.0)/COUNT(q.id) as quiebre FROM SALACLIENTE sc
			INNER JOIN SALA s on s.ID = sc.SALA_ID AND sc.CLIENTE_ID = @id_cliente AND sc.MEDICION_ID = @id_medicion_actual AND s.COMUNA_ID IN ( ? )
			INNER JOIN PLANOGRAMAQ p on p.SALACLIENTE_ID = sc.ID AND p.MEDICION_ID = @id_medicion_actual
			INNER JOIN QUIEBRE q on q.PLANOGRAMAQ_ID = p.ID
			INNER JOIN EMPLEADO e on e.ID = sc.EMPLEADO_ID
			INNER JOIN CARGO car on car.ID = e.CARGO_ID
			GROUP BY e.ID, e.NOMBRE, car.nombre
			) AS A LEFT JOIN
			
(SELECT e.id as id2, (SUM(case when q.hayquiebre = 1 then 1 else 0 END)*100.0)/COUNT(q.id) as quiebre_anterior FROM SALACLIENTE sc
			INNER JOIN PLANOGRAMAQ p on p.SALACLIENTE_ID = sc.ID AND sc.CLIENTE_ID = @id_cliente AND sc.MEDICION_ID = @id_medicion_anterior AND p.MEDICION_ID = @id_medicion_anterior
			INNER JOIN QUIEBRE q on q.PLANOGRAMAQ_ID = p.ID
			INNER JOIN EMPLEADO e on e.ID = sc.EMPLEADO_ID
			GROUP BY e.ID
			) AS B on A.ID = B.ID2
			ORDER BY rank)) as t
			WHERE 1=1 AND ( {$where_empleado} )";
			
		$param = array($search_empleado, $id_cliente, $id_medicion_actual, $id_medicion_anterior, $array_comuna);
		$tipo_param = array(\PDO::PARAM_STR, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \Doctrine\DBAL\Connection::PARAM_INT_ARRAY);
		$ranking_empleado = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();
		//CACHE
		// $s1 = sha1($sql.print_r($param,true));
		// if($cacheDriver->contains($s1)) $ranking_empleado = $cacheDriver->fetch($s1);
		// else
		// {
			// $ranking_empleado = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();
			// $cacheDriver->save($s1, $ranking_empleado, $cacheseg);
		// }
		
		
		
		$time_taken = microtime(true) - $start;
		//RESPONSE
		$response = array(
			'ranking_sala' => $ranking_sala,
			'ranking_item' => $ranking_item,
			'ranking_empleado' => $ranking_empleado,
			'id_medicion_actual' => $id_medicion_actual,
			'id_medicion_anterior' => $id_medicion_anterior,
			'time_taken' => $time_taken*1000,
		);
		$response = new JsonResponse($response);
		
		//CACHE
		$response->setPrivate();
		$response->setMaxAge(1);


		return $response;
    }

}
