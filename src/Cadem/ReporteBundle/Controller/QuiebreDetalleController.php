<?php

namespace Cadem\ReporteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\HttpFoundation\Session;

class QuiebreDetalleController extends Controller
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
			WHERE cl.id = :id
			order by r.numero')
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
			WHERE cl.id = :id
			order by p.region_id')
			->setParameter('id', $cliente->getId())
			->setMaxResults(1);
		$provincias = $query->getResult();
		
		$choices_provincias = array();
		foreach($provincias as $r)
		{
			$choices_provincias[$r->getId()] = strtoupper($r->getNombre());
		}				
		
		//COMUNA
		$query = $em->createQuery(
			'SELECT DISTINCT c FROM CademReporteBundle:Comuna c
			JOIN c.provincia p
			JOIN c.salas s
			JOIN s.salaclientes sc
			JOIN sc.cliente cl
			WHERE cl.id = :id and p.region_id= :id_region')
			->setParameter('id', $cliente->getId())
			->setParameter('id_region', $regiones[0]->getId());
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

		$form_cadena = $this->get('form.factory')->createNamedBuilder('f_cadena', 'form')
			->add('Cadena', 'choice', array(
				'choices'   => $choices_cadenas,
				'required'  => true,
				'multiple'  => true,
				'data' => array_keys($choices_cadenas)
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
				'data' => array($regiones[0]->getId())
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
		
		// Obtener id de la variable
		$estudio_variable=$estudios[0]->getEstudiovariables();	
		
		$variable=$estudio_variable[0]->getVariable()->getId();				
				
		$session->set("variable",$variable);					
		
		//ULTIMA MEDICION
		$id_ultima_medicion = $this->get('cadem_reporte.helper.medicion')->getIdUltimaMedicion();
		
		$comunas='';
		foreach(array_keys($choices_comunas) as $comuna)
			$comunas.=$comuna.',';	
		$comunas = trim($comunas, ',');					
		
		//CONSULTA
				
		$sql = "SELECT (case when q.hayquiebre = 1 then 1 else 0 END) as quiebre, ic.CODIGOITEM1 as COD_PRODUCTO,i.NOMBRE as NOM_PRODUCTO,ni.NOMBRE as SEGMENTO, ISNULL(sc.CODIGOSALA, UPPER(cad.NOMBRE+' '+com.NOMBRE+' '+s.CALLE+' '+s.NUMEROCALLE)) as ID_SALA, ISNULL(sc.CODIGOSALA,'-') as COD_SALA, UPPER(cad.NOMBRE+' '+com.NOMBRE+' '+s.CALLE+' '+s.NUMEROCALLE) as NOM_SALA FROM QUIEBRE q
		INNER JOIN PLANOGRAMAQ p on p.ID = q.PLANOGRAMAQ_ID and p.MEDICION_ID = {$id_ultima_medicion}
		INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID and sc.CLIENTE_ID = {$user->getClienteID()}
		INNER JOIN SALA s on s.ID = sc.SALA_ID and s.COMUNA_ID in ({$comunas})
		INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID
		INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID
		INNER JOIN COMUNA com on s.COMUNA_ID=com.ID
		INNER JOIN CADENA cad on s.CADENA_ID=cad.ID	
		INNER JOIN ITEM i on i.ID = ic.ITEM_ID	
		ORDER BY SEGMENTO,NOM_PRODUCTO,NOM_SALA";			
		
		$sha1 = sha1($sql);

		if(!$session->has($sha1)){
			$detalle_quiebre = $em->getConnection()->executeQuery($sql)->fetchAll();
			$session->set($sha1,$detalle_quiebre);
		}
		else $detalle_quiebre = $session->get($sha1);
					
		// Obtener totales horizontales por producto
			
		$sql =	"SELECT  i.NOMBRE, ni.NOMBRE, SUM(case when q.HAYQUIEBRE = 1 then 1 else 0 end)*1.0/COUNT(q.HAYQUIEBRE) as QUIEBRE FROM QUIEBRE q
				INNER JOIN PLANOGRAMAQ p on p.ID = q.PLANOGRAMAQ_ID AND p.MEDICION_ID = {$id_ultima_medicion}
				INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID and sc.CLIENTE_ID = {$user->getClienteID()}
				INNER JOIN SALA s on s.ID = sc.SALA_ID and s.COMUNA_ID in ({$comunas})
				INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID
				INNER JOIN ITEM i on i.ID = ic.ITEM_ID
				INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID				
				GROUP BY i.NOMBRE, ni.NOMBRE
				ORDER BY ni.NOMBRE,i.NOMBRE";
			
		$totales_producto = $em->getConnection()->executeQuery($sql)->fetchAll();		

		// Obtener totales verticales por segmento
						
		$sql =	"SELECT ni.NOMBRE as SEGMENTO, ISNULL(sc.CODIGOSALA, UPPER(cad.NOMBRE+' '+com.NOMBRE+' '+s.CALLE+' '+s.NUMEROCALLE)) as ID_SALA, SUM(case when q.HAYQUIEBRE = 1 then 1 else 0 end)*1.0/COUNT(q.HAYQUIEBRE) as QUIEBRE FROM QUIEBRE q
				INNER JOIN PLANOGRAMAQ p on p.ID = q.PLANOGRAMAQ_ID AND p.MEDICION_ID = {$id_ultima_medicion}
				INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID and sc.CLIENTE_ID = {$user->getClienteID()}
				INNER JOIN SALA s on s.ID = sc.SALA_ID and s.COMUNA_ID in( {$comunas} )		
				INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID
				INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID				
				INNER JOIN COMUNA com on s.COMUNA_ID=com.ID
				INNER JOIN CADENA cad on s.CADENA_ID=cad.ID								
				GROUP BY cad.NOMBRE,com.NOMBRE,s.CALLE,s.NUMEROCALLE,ni.NOMBRE, sc.CODIGOSALA
				ORDER BY ni.NOMBRE";			
	
		$totales_segmento = $em->getConnection()->executeQuery($sql)->fetchAll();		
		
		// Obtener totales horizontales por totales segmento (ultima columna de totales verticales por categoria)
		
		$sql =	"SELECT ni.NOMBRE as SEGMENTO, SUM(case when q.HAYQUIEBRE = 1 then 1 else 0 end)*1.0/COUNT(q.HAYQUIEBRE) as QUIEBRE FROM QUIEBRE q
				INNER JOIN PLANOGRAMAQ p on p.ID = q.PLANOGRAMAQ_ID AND p.MEDICION_ID = {$id_ultima_medicion}
				INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID and sc.CLIENTE_ID = {$user->getClienteID()}
				INNER JOIN SALA s on s.ID = sc.SALA_ID and s.COMUNA_ID in ({$comunas})
				INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID
				INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID
				GROUP BY ni.NOMBRE
				ORDER BY ni.NOMBRE";
			
		$totales_horizontales_segmento = $em->getConnection()->executeQuery($sql)->fetchAll();	
		
		// Obtener totales verticales por totales categoria
		
		$sql = "SELECT ISNULL(sc.CODIGOSALA, UPPER(cad.NOMBRE+' '+com.NOMBRE+' '+s.CALLE+' '+s.NUMEROCALLE)) as ID_SALA, SUM(case when q.HAYQUIEBRE = 1 then 1 else 0 end)*1.0/COUNT(q.HAYQUIEBRE) as QUIEBRE FROM QUIEBRE q
				INNER JOIN PLANOGRAMAQ p on p.ID = q.PLANOGRAMAQ_ID AND p.MEDICION_ID = {$id_ultima_medicion}
				INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID and sc.CLIENTE_ID = {$user->getClienteID()}
				INNER JOIN SALA s on s.ID = sc.SALA_ID and s.COMUNA_ID in( {$comunas} )
				INNER JOIN COMUNA com on s.COMUNA_ID=com.ID
				INNER JOIN CADENA cad on s.CADENA_ID=cad.ID								
				GROUP BY cad.NOMBRE,com.NOMBRE,s.CALLE,s.NUMEROCALLE, sc.CODIGOSALA";
		
		$totales_verticales_segmento = $em->getConnection()->executeQuery($sql)->fetchAll();							
		
		// Obtener total horizontal por totales verticales por totales categoria
		
		$sql = "SELECT SUM(case when q.HAYQUIEBRE = 1 then 1 else 0 end)*1.0/COUNT(q.HAYQUIEBRE) as QUIEBRE FROM QUIEBRE q
				INNER JOIN PLANOGRAMAQ p on p.ID = q.PLANOGRAMAQ_ID AND p.MEDICION_ID = {$id_ultima_medicion}
				INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID and sc.CLIENTE_ID = {$user->getClienteID()}
				INNER JOIN SALA s on s.ID = sc.SALA_ID and s.COMUNA_ID in ({$comunas})";			

		$total = $em->getConnection()->executeQuery($sql)->fetchAll();								
				
		// Variable para saber cuantos niveles de agregacion define el cliente, esto debe ser parametrizado en una etapa posterior
		$niveles=2;										
				
		$head=array();
		$salas=array();		
		$salas_aux=array();		
		
		// // Generamos el head de la tabla, y las salas
		foreach($detalle_quiebre as $registro)
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
		
		usort($salas_aux, array($this,"sortFunction"));		
		// CONSTRUIR EL ENCABEZADO DE LA TABLA
			
		$prefixes=array('SKU/SALA','SEGMENTO');				
		
		$head=array();
		
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
		
		foreach($salas_aux as $sala)
		{
			array_push($salas,$sala['ID_SALA']);	
			// $head[$sala['COD_SALA']]=$sala['NOM_SALA'];
			$fila=array();
			$fila['cod_sala']=$sala['COD_SALA'];
			$fila['nom_sala']=$sala['NOM_SALA'];
			array_push($head,$fila);
			$fila=array();
			$fila['aTargets']=array($cont);		
			// $fila['sWidth']="2%";
			array_push($aoColumnDefs,$fila);
			$cont++;
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
		$session->set("detalle_quiebre",$detalle_quiebre);	
		$session->set("totales_producto",$totales_producto);		
		$session->set("totales_segmento",$totales_segmento);		
		$session->set("totales_horizontales_segmento",$totales_horizontales_segmento);	
		$session->set("totales_verticales_segmento",$totales_verticales_segmento);	
		$session->set("total",$total);	

		// Calcula el ancho máximo de la tabla	
		$extension=count($head)*(12+log(count($head),10))-100;
	
		if($extension<0)
			$extension=0;
			
		$max_width=100+$extension;			
				
		//RESPONSE
		$response = $this->render('CademReporteBundle:Detalle:index.html.twig',
		array(
			'forms' => array(
				'form_estudio' 	=> $form_estudio->createView(),
				'form_periodo' 	=> $form_periodo->createView(),
				'form_cadena' => $form_cadena->createView(),
				'form_region' 	=> $form_region->createView(),
				'form_provincia' => $form_provincia->createView(),
				'form_comuna' 	=> $form_comuna->createView(),	
			),
			'head' => $head,
			'max_width' => $max_width,
			'logofilename' => $logofilename,
			'logostyle' => $logostyle,
			'estudios' => $estudios,
			'variable' => 1,
			'header_action' => 'quiebre_detalle_header',
			'body_action' => 'quiebre_detalle_body',	
			'aoColumnDefs' => json_encode($aoColumnDefs),
			'columnas_reservadas' => 2,
			'tag_variable' => $variable	
			)
		);
		$time_taken = microtime(true) - $start;
		//return $time_taken*1000;		
		
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
		$start = microtime(true);
		// Recuperar el usuario y datos de sesión
		$user = $this->getUser();
		$em = $this->getDoctrine()->getManager();
		$session=$this->get("session");			
		$salas=$session->get("salas");		
		$totales_producto=$session->get("totales_producto");		
		$totales_segmento=$session->get("totales_segmento");	
		$totales_horizontales_segmento=$session->get("totales_horizontales_segmento");	
		$totales_verticales_segmento=$session->get("totales_verticales_segmento");	
		$total=$session->get("total");			
		$detalle_quiebre=$session->get("detalle_quiebre");		
		
		// CONSTRUIR EL CUERPO DE LA TABLA						
		$body=array();									
		$num_regs=count($detalle_quiebre);		
		$cont_salas=0;
		$cont_regs=0;
		$num_salas=count($salas);			
		$matriz_totales=array();		

		$variable=$session->get("variable");						
		
		switch($variable)
		{
			case 1: // Si el tag de la variable es quiebre 
				$color_positivo='rgb(174, 247, 81)';
				$color_negativo='rgb(234, 57, 21)';
				break;
			case 5: // Si el tag de la variable es presencia invertimos los colores
				$color_positivo='rgb(234, 57, 21)';
				$color_negativo='rgb(174, 247, 81)';
				break;				
		}
	
		if($num_regs>0)
		{
			$nivel1=$detalle_quiebre[$cont_regs]['COD_PRODUCTO'];		
			// Lleno la fila con vacios, le agrego 1 posiciones, correspondientes al total					
			$fila=array_fill(0,$num_salas+3,"<div style='background:grey;height:2.22em'></div>");								
			// $fila=array_fill(0,$num_salas+3,"-");								
			$nivel2=$detalle_quiebre[$cont_regs]['SEGMENTO'];																								
			$cont_totales_producto=0;				
		
			while($cont_regs<$num_regs)
			{	// Lleno la fila con vacios, le agrego 3 posiciones, correspondientes a los niveles de agregación y al total	
				$columna_quiebre=array_search($detalle_quiebre[$cont_regs]['ID_SALA'],$salas);	
						
				// Mientras el primer nivel de agregación no cambie			
				if($nivel1==$detalle_quiebre[$cont_regs]['COD_PRODUCTO'])
				{									
					$fila[0]=$detalle_quiebre[$cont_regs]['NOM_PRODUCTO'];//.' ['.$detalle_quiebre[$cont_regs]['COD_PRODUCTO'].']';					
					$fila[1]=$detalle_quiebre[$cont_regs]['SEGMENTO'];	
					// $fila[$columna_quiebre+2]=$detalle_quiebre[$cont_regs]['quiebre'];
					switch($detalle_quiebre[$cont_regs]['quiebre'])
					{
						case '0':
							$fila[$columna_quiebre+2]="<div style='background:$color_positivo;height:2.22em'></div>";	
							break;
						case '1':
							$fila[$columna_quiebre+2]="<div style='background:$color_negativo;height:2.22em'></div>";	
							break;
					}																			
					$cont_regs++;						
				}	
				else
				{ // Si el primer nivel de agregacion cambió, lo actualizo, agrego la fila al body y reseteo el contador de cadenas												
					$fila[$num_salas+2]=round($totales_producto[$cont_totales_producto]['QUIEBRE']*100,1);					
					$cont_totales_producto++;																			
					$nivel1=$detalle_quiebre[$cont_regs]['COD_PRODUCTO'];
					array_push($body,$fila);
					$fila=array_fill(0,$num_salas+3,"<div style='background:grey;height:2.22em'></div>");	
					// $fila=array_fill(0,$num_salas+3,"-");	
				}
				if($cont_regs==$num_regs)		
				{						
					$columna_quiebre=array_search($detalle_quiebre[$cont_regs-1]['COD_SALA'],$salas);											
					switch($detalle_quiebre[$cont_regs-1]['quiebre'])
					{
						case '0':
							$fila[$columna_quiebre+2]="<div style='background:$color_positivo;height:2.22em'></div>";	
							break;
						case '1':
							$fila[$columna_quiebre+2]="<div style='background:$color_negativo;height:2.22em'></div>";	
							break;
					}					
					// $fila[$columna_quiebre+2]=$detalle_quiebre[$cont_regs-1]['quiebre'];
					$fila[$num_salas+2]=round($totales_producto[$cont_totales_producto]['QUIEBRE']*100,1);					
					$cont_totales_producto++;								
					array_push($body,$fila);
					$cont_regs++;
				}			
			}	
			// Calculo de totales		
			$fila=array_fill(0,$num_salas+1,"-");	
			$num_regs=count($totales_segmento);
			$cont_regs=0;														
			$nivel2=$totales_segmento[$cont_regs]['SEGMENTO'];	
			$cont_totales_horizontales_segmento=0;	

			while($cont_regs<$num_regs)
			{
				$columna_quiebre=array_search($totales_segmento[$cont_regs]['ID_SALA'],$salas);					
				// Mientras no cambie el segmento
				if($nivel2==$totales_segmento[$cont_regs]['SEGMENTO'])
				{
					$fila[$columna_quiebre]=round($totales_segmento[$cont_regs]['QUIEBRE']*100,1);					
					$cont_regs++;
				}
				else
				{
					$fila[$num_salas]=round($totales_horizontales_segmento[$cont_totales_horizontales_segmento]['QUIEBRE']*100,1);
					$cont_totales_horizontales_segmento++;
					array_push($matriz_totales,$fila);
					$fila=array_fill(0,$num_salas+1,"-");
					$nivel2=$totales_segmento[$cont_regs]['SEGMENTO'];					
				}
				if($cont_regs==$num_regs)		
				{	
					$columna_quiebre=array_search($totales_segmento[$cont_regs-1]['ID_SALA'],$salas);
					$fila[$columna_quiebre]=round($totales_segmento[$cont_regs-1]['QUIEBRE']*100,1);					
					$fila[$num_salas]=round($totales_horizontales_segmento[$cont_totales_horizontales_segmento]['QUIEBRE']*100,1);
					array_push($matriz_totales,(object)$fila);		
					$cont_regs++;					
				}				
			}

			$cont_regs=0;
			$num_regs=count($totales_verticales_segmento);
			$fila=array_fill(0,$num_salas+1,"-");										
			
			while($cont_regs<$num_regs)
			{
				$columna_quiebre=array_search($totales_verticales_segmento[$cont_regs]['ID_SALA'],$salas);					
				// Mientras no cambie la cadena  
				$fila[$columna_quiebre]=round($totales_verticales_segmento[$cont_regs]['QUIEBRE']*100,1);					
				$cont_regs++;
			}	
			
			$fila[$num_salas]=round($total[0]['QUIEBRE']*100,1);			
			
			array_push($matriz_totales,$fila);				
		}				
		/*
		 * Output
		 */
		// $session->close();
		$time_taken = microtime(true) - $start;
		$output = array(
			"sEcho" => intval($_POST['sEcho']),
			"iTotalRecords" => count($detalle_quiebre),
			"iTotalDisplayRecords" => count($body),
			"aaData" => $body,
			"matriz_totales" => $matriz_totales,
			"time_taken" => $time_taken*1000
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

		$cadenas='';
		foreach($parametros['f_cadena']['Cadena'] as $cadena)
			$cadenas.=$cadena.',';
		$cadenas = trim($cadenas, ',');

		//23 SEG
		$start = microtime(true);
		$sql = "SELECT (case when q.hayquiebre = 1 then 1 else 0 END) as quiebre, ic.CODIGOITEM1 as COD_PRODUCTO,i.NOMBRE as NOM_PRODUCTO,ni.NOMBRE as SEGMENTO, ISNULL(sc.CODIGOSALA, UPPER(cad.NOMBRE+' '+com.NOMBRE+' '+s.CALLE+' '+s.NUMEROCALLE)) as ID_SALA, ISNULL(sc.CODIGOSALA,'-') as COD_SALA, UPPER(cad.NOMBRE+' '+com.NOMBRE+' '+s.CALLE+' '+s.NUMEROCALLE) as NOM_SALA FROM QUIEBRE q
				INNER JOIN PLANOGRAMAQ p on p.ID = q.PLANOGRAMAQ_ID and p.MEDICION_ID = {$medicion}
				INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID and sc.CLIENTE_ID = {$user->getClienteID()}
				INNER JOIN SALA s on s.ID = sc.SALA_ID and s.COMUNA_ID in ({$comunas}) and s.CADENA_ID in ({$cadenas})
				INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID
				INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID
				INNER JOIN COMUNA com on s.COMUNA_ID=com.ID
				INNER JOIN CADENA cad on s.CADENA_ID=cad.ID	
				INNER JOIN ITEM i on i.ID = ic.ITEM_ID	
				ORDER BY SEGMENTO,NOM_PRODUCTO,NOM_SALA";																										
		
		$sha1 = sha1($sql);

		if(!$session->has($sha1)){
			$detalle_quiebre = $em->getConnection()->executeQuery($sql)->fetchAll();
			$session->set($sha1,$detalle_quiebre);
		}
		else $detalle_quiebre = $session->get($sha1);
		$time_taken = microtime(true) - $start;
		//return $time_taken*1000;
		
		//680 MS
		// Obtener totales horizontales por producto
		
		$sql =	"SELECT  i.NOMBRE, ni.NOMBRE, SUM(case when q.HAYQUIEBRE = 1 then 1 else 0 end)*1.0/COUNT(q.HAYQUIEBRE) as QUIEBRE FROM QUIEBRE q
				INNER JOIN PLANOGRAMAQ p on p.ID = q.PLANOGRAMAQ_ID AND p.MEDICION_ID = {$medicion}
				INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID and sc.CLIENTE_ID = {$user->getClienteID()}
				INNER JOIN SALA s on s.ID = sc.SALA_ID and s.COMUNA_ID in( {$comunas} )	 and s.CADENA_ID in ({$cadenas})			
				INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID
				INNER JOIN ITEM i on i.ID = ic.ITEM_ID
				INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID				
				GROUP BY i.NOMBRE, ni.NOMBRE
				ORDER BY ni.NOMBRE,i.NOMBRE";
			
		$totales_producto = $em->getConnection()->executeQuery($sql)->fetchAll();		
		
		// Obtener totales verticales por segmento
		
		//720 MS
		$sql =	"SELECT ni.NOMBRE as SEGMENTO, ISNULL(sc.CODIGOSALA, UPPER(cad.NOMBRE+' '+com.NOMBRE+' '+s.CALLE+' '+s.NUMEROCALLE)) as ID_SALA, SUM(case when q.HAYQUIEBRE = 1 then 1 else 0 end)*1.0/COUNT(q.HAYQUIEBRE) as QUIEBRE FROM QUIEBRE q
				INNER JOIN PLANOGRAMAQ p on p.ID = q.PLANOGRAMAQ_ID AND p.MEDICION_ID = {$medicion}
				INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID and sc.CLIENTE_ID = {$user->getClienteID()}
				INNER JOIN SALA s on s.ID = sc.SALA_ID and s.COMUNA_ID in( {$comunas} ) and s.CADENA_ID in ({$cadenas})			
				INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID
				INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID				
				INNER JOIN COMUNA com on s.COMUNA_ID=com.ID
				INNER JOIN CADENA cad on s.CADENA_ID=cad.ID								
				GROUP BY cad.NOMBRE,com.NOMBRE,s.CALLE,s.NUMEROCALLE,ni.NOMBRE, sc.CODIGOSALA
				ORDER BY ni.NOMBRE";
	
		// print_r($sql);
	
		$totales_segmento = $em->getConnection()->executeQuery($sql)->fetchAll();
				
		// Obtener totales horizontales por totales segmento (ultima columna de totales verticales por categoria)
		
		//100 MS
		$sql =	"SELECT ni.NOMBRE as SEGMENTO, SUM(case when q.HAYQUIEBRE = 1 then 1 else 0 end)*1.0/COUNT(q.HAYQUIEBRE) as QUIEBRE FROM QUIEBRE q
				INNER JOIN PLANOGRAMAQ p on p.ID = q.PLANOGRAMAQ_ID AND p.MEDICION_ID = {$medicion}
				INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID and sc.CLIENTE_ID = {$user->getClienteID()}
				INNER JOIN SALA s on s.ID = sc.SALA_ID and s.COMUNA_ID in( {$comunas} ) and s.CADENA_ID in ({$cadenas})
				INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID
				INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID
				GROUP BY ni.NOMBRE
				ORDER BY ni.NOMBRE";				
			
		$totales_horizontales_segmento = $em->getConnection()->executeQuery($sql)->fetchAll();	
		
		// Obtener totales verticales por totales categoria
		
		//100 MS
		$sql = "SELECT ISNULL(sc.CODIGOSALA, UPPER(cad.NOMBRE+' '+com.NOMBRE+' '+s.CALLE+' '+s.NUMEROCALLE)) as ID_SALA, SUM(case when q.HAYQUIEBRE = 1 then 1 else 0 end)*1.0/COUNT(q.HAYQUIEBRE) as QUIEBRE FROM QUIEBRE q
				INNER JOIN PLANOGRAMAQ p on p.ID = q.PLANOGRAMAQ_ID AND p.MEDICION_ID = {$medicion}
				INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID and sc.CLIENTE_ID = {$user->getClienteID()}
				INNER JOIN SALA s on s.ID = sc.SALA_ID and s.COMUNA_ID in( {$comunas} )	and s.CADENA_ID in ({$cadenas})
				INNER JOIN COMUNA com on s.COMUNA_ID=com.ID
				INNER JOIN CADENA cad on s.CADENA_ID=cad.ID								
				GROUP BY cad.NOMBRE,com.NOMBRE,s.CALLE,s.NUMEROCALLE, sc.CODIGOSALA";
		
		$totales_verticales_segmento = $em->getConnection()->executeQuery($sql)->fetchAll();							
		
		// Obtener total horizontal por totales verticales por totales categoria
				
		//90 MS
		$sql = "SELECT SUM(case when q.HAYQUIEBRE = 1 then 1 else 0 end)*1.0/COUNT(q.HAYQUIEBRE) as QUIEBRE FROM QUIEBRE q
				INNER JOIN PLANOGRAMAQ p on p.ID = q.PLANOGRAMAQ_ID AND p.MEDICION_ID = {$medicion}
				INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID and sc.CLIENTE_ID = {$user->getClienteID()}
				INNER JOIN SALA s on s.ID = sc.SALA_ID and s.COMUNA_ID in( {$comunas} ) and s.CADENA_ID in ({$cadenas})";			

		$total = $em->getConnection()->executeQuery($sql)->fetchAll();			
				
		// Variable para saber cuantos niveles de agregacion define el cliente, esto debe ser parametrizado en una etapa posterior
		$niveles=2;												
						
		$head=array();
		$salas=array();		
		$salas_aux=array();		
		
		// Generamos el head de la tabla, y las salas
		foreach($detalle_quiebre as $registro)
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
			
		$prefixes=array('SKU/SALA','SEGMENTO');				
		
		$head=array();
		
		// Oonstruir inicialización de columnas
		$aoColumnDefs=array();
		
		$fila=array();
		$fila['aTargets']=array(0);
		$fila['sWidth']="260px";
		$fila['sClass']="tag";
		array_push($aoColumnDefs,$fila);
		
		$fila=array();
		$fila['aTargets']=array(1);
		$fila['bVisible']=false;
		array_push($aoColumnDefs,$fila);
		
		$cont=2;
		
		$head=array();
		
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
		$session->set("detalle_quiebre",$detalle_quiebre);	
		$session->set("totales_producto",$totales_producto);		
		$session->set("totales_segmento",$totales_segmento);			
		$session->set("totales_horizontales_segmento",$totales_horizontales_segmento);	
		$session->set("totales_verticales_segmento",$totales_verticales_segmento);	
		$session->set("total",$total);		
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

	public function excelAction(Request $request)
    {
    	return new Response($this->get("cadem_reporte.helper.phpexcel")->getExcelDetalle());
    }
}
