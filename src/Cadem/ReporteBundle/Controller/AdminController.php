<?php

namespace Cadem\ReporteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\File;

class AdminController extends Controller
{
    private $uploadDIR;

    public function __construct()
    {
        $this->uploadDIR = __DIR__.'/../../../../web/uploads/';
    }

    public function cargaregistroplanoAction(Request $request, $plano)
    {
        $em = $this->getDoctrine()->getManager();
        $clientemedicion = $request->query->get('f_medicion');
        list($id_cliente, $id_medicion) = explode("-", $clientemedicion['Cliente_Medicion']);
        if(!isset($id_cliente) || !isset($id_medicion)){
            return new JsonResponse(array(
                'status' => false,
                'mensaje' => 'NO SE INDENTIFICA AL CLIENTE O LA MEDICION'
            ));
        }
        
        switch ($plano) {
            case 'planoquiebre':
                $planograma = 'PLANOGRAMAQ';
                break;
            case 'planoprecio':
                $planograma = 'PLANOGRAMAP';
                break;
            
            default:
                //ERROR
                break;
        }
        $sql = "SELECT COUNT(p.ID) as c FROM {$planograma} p
                INNER JOIN MEDICION m on m.ID = p.MEDICION_ID AND m.ID = {$id_medicion}
                INNER JOIN ESTUDIOVARIABLE ev on ev.ID = m.ESTUDIOVARIABLE_ID
                INNER JOIN ESTUDIO e on e.ID = ev.ESTUDIO_ID AND e.CLIENTE_ID = {$id_cliente}
                ";
        $query = $em->getConnection()->executeQuery($sql)->fetchAll();
        $registros = isset($query[0])?intval($query[0]['c']):-1;
        if($registros === -1){
            return new JsonResponse(array(
                'status' => false,
                'mensaje' => 'ERROR AL CONSULTAR LA CANTIDAD DE REGISTROS'
            ));
        }

        return new JsonResponse(array(
            'status' => true,
            'registros' => $registros
        ));
    }


    public function cargaborrarregistroplanoAction(Request $request, $plano)
    {
        $em = $this->getDoctrine()->getManager();
        $clientemedicion = $request->request->get('f_medicion');
        list($id_cliente, $id_medicion) = explode("-", $clientemedicion['Cliente_Medicion']);
        if(!isset($id_cliente) || !isset($id_medicion)){
            return new JsonResponse(array(
                'status' => false,
                'mensaje' => 'NO SE INDENTIFICA AL CLIENTE O LA MEDICION'
            ));
        }

        switch ($plano) {
            case 'planoquiebre':
                $planograma = 'PLANOGRAMAQ';
                break;
            case 'planoprecio':
                $planograma = 'PLANOGRAMAP';
                break;
            
            default:
                //ERROR
                break;
        }

        $sql = "DELETE FROM {$planograma}
                WHERE MEDICION_ID = ?
                ";
        $param = array($id_medicion);
        $row_affected = $em->getConnection()->executeUpdate($sql,$param);
        if(!is_int($row_affected)){
            return new JsonResponse(array(
                'status' => false,
                'mensaje' => 'ERROR AL CONSULTAR LA CANTIDAD DE FILAS BORRADAS'
            ));
        }

        

        return new JsonResponse(array(
            'status' => true,
            'row_affected' => $row_affected
        ));
    }

	public function cargaitemAction()
    {
        
        //RESPONSE
        $response = $this->render('CademReporteBundle:Admin:cargaitem.html.twig',
        array()
        );

        //CACHE
        $response->setPrivate();
        $response->setMaxAge(1);


        return $response;
    }

    public function cargasalaAction()
    {
		
		//RESPONSE
		$response = $this->render('CademReporteBundle:Admin:cargasala.html.twig',
		array()
		);

		//CACHE
		$response->setPrivate();
		$response->setMaxAge(1);


		return $response;
    }

    public function cargaitemclienteAction()
    {
        $em = $this->getDoctrine()->getManager();
        $sql = "SELECT c.ID as idc, m.ID as idm, c.NOMBREFANTASIA as nombre, m.NOMBRE as medicion, v.NOMBRE as variable FROM CLIENTE c
                INNER JOIN ESTUDIO e on e.CLIENTE_ID = c.ID
                INNER JOIN ESTUDIOVARIABLE ev on ev.ESTUDIO_ID = e.ID
                INNER JOIN MEDICION m on m.ESTUDIOVARIABLE_ID = ev.ID
                INNER JOIN VARIABLE v on v.ID = ev.VARIABLE_ID
                ORDER BY c.NOMBREFANTASIA, v.NOMBRE, m.NOMBRE";
        $query = $em->getConnection()->executeQuery($sql)->fetchAll();
        $choices_medicion = array();
        foreach($query as $r)
        {
            $choices_medicion[$r['idc'].'-'.$r['idm']] = strtoupper($r['nombre'].'['.$r['variable'].'] '.$r['medicion']);
        }

        $form_medicion = $this->get('form.factory')->createNamedBuilder('f_medicion', 'form')
            ->add('Cliente_Medicion', 'choice', array(
                'choices'   => $choices_medicion,
                'required'  => true,
                'multiple'  => false
            ))
            ->getForm();


        //RESPONSE
        $response = $this->render('CademReporteBundle:Admin:cargaitemcliente.html.twig',
        array('form_medicion' => $form_medicion->createView())
        );

        //CACHE
        $response->setPrivate();
        $response->setMaxAge(1);


        return $response;
    }

    public function cargasalaclienteAction()
    {
        $em = $this->getDoctrine()->getManager();
        $sql = "SELECT c.ID as idc, m.ID as idm, c.NOMBREFANTASIA as nombre, m.NOMBRE as medicion, v.NOMBRE as variable FROM CLIENTE c
                INNER JOIN ESTUDIO e on e.CLIENTE_ID = c.ID
                INNER JOIN ESTUDIOVARIABLE ev on ev.ESTUDIO_ID = e.ID
                INNER JOIN MEDICION m on m.ESTUDIOVARIABLE_ID = ev.ID
                INNER JOIN VARIABLE v on v.ID = ev.VARIABLE_ID
                ORDER BY c.NOMBREFANTASIA, v.NOMBRE, m.NOMBRE";
        $query = $em->getConnection()->executeQuery($sql)->fetchAll();
        $choices_medicion = array();
        foreach($query as $r)
        {
            $choices_medicion[$r['idc'].'-'.$r['idm']] = strtoupper($r['nombre'].'['.$r['variable'].'] '.$r['medicion']);
        }

        $form_medicion = $this->get('form.factory')->createNamedBuilder('f_medicion', 'form')
            ->add('Cliente_Medicion', 'choice', array(
                'choices'   => $choices_medicion,
                'required'  => true,
                'multiple'  => false
            ))
            ->getForm();


        //RESPONSE
        $response = $this->render('CademReporteBundle:Admin:cargasalacliente.html.twig',
        array('form_medicion' => $form_medicion->createView())
        );

        //CACHE
        $response->setPrivate();
        $response->setMaxAge(1);


        return $response;
    }

    public function cargaplanoquiebreAction()
    {
        $em = $this->getDoctrine()->getManager();
        $sql = "SELECT c.ID as idc, m.ID as idm, c.NOMBREFANTASIA as nombre, m.NOMBRE as medicion FROM CLIENTE c
                INNER JOIN ESTUDIO e on e.CLIENTE_ID = c.ID
                INNER JOIN ESTUDIOVARIABLE ev on ev.ESTUDIO_ID = e.ID AND ev.VARIABLE_ID IN (1,5)
                INNER JOIN MEDICION m on m.ESTUDIOVARIABLE_ID = ev.ID
                ORDER BY c.NOMBREFANTASIA, m.NOMBRE";
        $query = $em->getConnection()->executeQuery($sql)->fetchAll();
        $choices_medicion = array();
        foreach($query as $k => $r)
        {
            if($k === 0){
                $id_cliente = intval($r['idc']);
                $id_medicion = intval($r['idm']);
            }
            $choices_medicion[$r['idc'].'-'.$r['idm']] = strtoupper($r['nombre'].'-'.$r['medicion']);
        }

        $form_medicion = $this->get('form.factory')->createNamedBuilder('f_medicion', 'form')
            ->add('Cliente_Medicion', 'choice', array(
                'choices'   => $choices_medicion,
                'required'  => true,
                'multiple'  => false
            ))
            ->getForm();

        if(isset($id_cliente) && isset($id_medicion)){
            $sql = "SELECT COUNT(p.ID) as c FROM PLANOGRAMAQ p
                    INNER JOIN MEDICION m on m.ID = p.MEDICION_ID AND m.ID = {$id_medicion}
                    INNER JOIN ESTUDIOVARIABLE ev on ev.ID = m.ESTUDIOVARIABLE_ID
                    INNER JOIN ESTUDIO e on e.ID = ev.ESTUDIO_ID AND e.CLIENTE_ID = {$id_cliente}
                    ";
            $query = $em->getConnection()->executeQuery($sql)->fetchAll();
            $registros = isset($query[0])?intval($query[0]['c']):0;
        }
        else $registros = 0;
        


        //RESPONSE
        $response = $this->render('CademReporteBundle:Admin:cargaplanoquiebre.html.twig',
        array(
            'form_medicion' => $form_medicion->createView(),
            'registros' => $registros
            )
        );

        //CACHE
        $response->setPrivate();
        $response->setMaxAge(1);


        return $response;
    }

    public function cargaplanoprecioAction()
    {
        $em = $this->getDoctrine()->getManager();
        $sql = "SELECT c.ID as idc, m.ID as idm, c.NOMBREFANTASIA as nombre, m.NOMBRE as medicion FROM CLIENTE c
                INNER JOIN ESTUDIO e on e.CLIENTE_ID = c.ID
                INNER JOIN ESTUDIOVARIABLE ev on ev.ESTUDIO_ID = e.ID AND ev.VARIABLE_ID = 2
                INNER JOIN MEDICION m on m.ESTUDIOVARIABLE_ID = ev.ID
                ORDER BY c.NOMBREFANTASIA, m.NOMBRE";
        $query = $em->getConnection()->executeQuery($sql)->fetchAll();
        $choices_medicion = array();
        foreach($query as $r)
        {
            if($k === 0){
                $id_cliente = intval($r['idc']);
                $id_medicion = intval($r['idm']);
            }
            $choices_medicion[$r['idc'].'-'.$r['idm']] = strtoupper($r['nombre'].'-'.$r['medicion']);
        }

        $form_medicion = $this->get('form.factory')->createNamedBuilder('f_medicion', 'form')
            ->add('Cliente_Medicion', 'choice', array(
                'choices'   => $choices_medicion,
                'required'  => true,
                'multiple'  => false
            ))
            ->getForm();

        if(isset($id_cliente) && isset($id_medicion)){
            $sql = "SELECT COUNT(p.ID) as c FROM PLANOGRAMAP p
                    INNER JOIN MEDICION m on m.ID = p.MEDICION_ID AND m.ID = {$id_medicion}
                    INNER JOIN ESTUDIOVARIABLE ev on ev.ID = m.ESTUDIOVARIABLE_ID
                    INNER JOIN ESTUDIO e on e.ID = ev.ESTUDIO_ID AND e.CLIENTE_ID = {$id_cliente}
                    ";
            $query = $em->getConnection()->executeQuery($sql)->fetchAll();
            $registros = isset($query[0])?intval($query[0]['c']):0;
        }
        else $registros = 0;


        //RESPONSE
        $response = $this->render('CademReporteBundle:Admin:cargaplanoprecio.html.twig',
        array(
            'form_medicion' => $form_medicion->createView(),
            'registros' => $registros
            )
        );

        //CACHE
        $response->setPrivate();
        $response->setMaxAge(1);


        return $response;
    }

    public function fileuploadAction(Request $request)
    {
        $uf = $request->files->get('file1');
    	$tipo_carga = $request->request->get('tipo_carga');
    	if (null === $uf || !isset($tipo_carga)) return new JsonResponse(array('status' => false));//ERROR
        $datos_itemcliente = array();
        if(in_array($tipo_carga, array('itemcliente', 'salacliente', 'planoquiebre', 'planoprecio'))){
            $clientemedicion = $request->request->get('f_medicion');
            list($id_cliente, $id_medicion) = explode("-", $clientemedicion['Cliente_Medicion']);
            $datos_itemcliente['id_cliente'] = intval($id_cliente);
            $datos_itemcliente['id_medicion'] = intval($id_medicion);
        }

    	//EXISTE UPLOADS?
        if(!is_dir($this->uploadDIR)) mkdir($this->uploadDIR);
        $uf = $uf->move($this->uploadDIR,$uf->getClientOriginalName().'__'.date("d_m_Y_H_i_s").'.'.$uf->getClientOriginalExtension());
    	return new JsonResponse(array_merge(array('status' => true, 'name' =>  $uf->getFilename(), 'tipo_carga' => $tipo_carga), $datos_itemcliente));
    }

    public function filevalidAction(Request $request)
    {
        $start = microtime(true);
    	$em = $this->getDoctrine()->getManager();
        $data = $request->query->all();
        $tipo_carga = $data['tipo_carga'];
    	$name = $data['name'];
        if(in_array($tipo_carga, array('itemcliente', 'salacliente', 'planoquiebre', 'planoprecio'))){
            $id_cliente = intval($data['id_cliente']);
            $id_medicion = intval($data['id_medicion']);
        }
    	$file = new File($this->uploadDIR.$name);
        $item_descartados = 0;
        
    	if($file->isReadable() && strcasecmp($file->getExtension(),'csv') === 0){
    		//LEER Y VALIDAR. SE GENERA UN ARCHIVO CON DATOS DE CARGA. LUEGO DEBERIA ESTAR EN MEMORIA
    		$fileobj = $file->openFile('r');
    		while (!$fileobj->eof()) {
			    $row = $fileobj->fgetcsv(';');
                $row = array_map("utf8_encode", $row);//SE PASA DE ANSI A UTF-8
                $row = array_map("trim", $row);//SE ELIMINAN ESPACIOS
                if(isset($row[1])){
                    $m[] = $row;
                }
			}


            switch ($tipo_carga) {
                case 'item'://DATOS DE ITEM
                    
                    //FORMATO ES: TIPOCODIGO;FABRICANTE;MARCA;NOMBRE;CODIGO
                    //SI LA PRIMERA FILA TIENE LOS ENCABEZADOS SE BORRA
                    if($m[0][0] === 'TIPOCODIGO' || $m[0][4] === 'CODIGO') unset($m[0]);
                    //SE SEPARA EN CHUNK PARA NO SOBRECARGAR
                    $chunk = 0;
                    foreach($m as $value){
                        $cod_item[floor($chunk/2000)][] = $value[4];
                        $chunk++;
                    }


                    

                    //SE VERIFICA QUE TODOS LOS SKU TENGAN 13 DIG, ADEMAS SE VALIDA QUE EL SKU NO ESTE EN LA BD
                    $cod_encontrados = array();
                    $start_select = microtime(true);
                    foreach($cod_item as $k => $chunk){
                        $sql = "SELECT i.codigo as codigo FROM ITEM i
                                WHERE i.codigo IN ( ? )
                                ORDER BY i.codigo";
                        $param = array($chunk);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();
                        foreach ($query as $v) $cod_encontrados[] = $v['codigo'];
                        set_time_limit(10);
                        $time_taken = microtime(true) - $start_select;
                        if($time_taken >= 600){
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'TIEMPO EXCEDIDO ('.round($time_taken,1).' SEG) EN CONSULTAR. EL TIEMPO MAX ES DE 600 SEG. LO QUE DEBERIA ALCANZAR PARA PROCESAR APROX 45 MIL FILAS. SI SU ARCHIVO TIENE MAS, POR FAVOR SAQUE LAS SUFICIENTES FILAS.'
                            ));
                        }
                    }
                    
                    $start_valid = microtime(true);
                    foreach ($m as $k => $fila) {
                        if(count($fila) !== 5){//SIEMPRE DEBEN HABER 5 COLUMNAS
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'NO HAY 5 COLUMNAS CERCA DE LA LINEA '.$k
                            ));
                        }
                        if(strlen($fila[4]) !== 13){//SE VERIFICA QUE TODOS LOS SKU TENGAN 13 DIG
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'EL SKU '.$fila[4].' CERCA DE LA LINEA '.$k.', NO TIENE 13 DIGITOS'
                            ));
                        }
                        if(strlen($fila[0]) === 0){//QUE LOS TIPOCODIGO NO SEAN VACIOS
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'NO EXISTE CODIGO PARA EL SKU '.$fila[4].' CERCA DE LA LINEA '.$k
                            ));
                        }
                        if(strlen($fila[3]) === 0){//EL NOMBRE NO PUEDE SER VACIO
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'EL NOMBRE NO PUEDE ESTAR VACIO, CERCA DE LA LINEA '.$k
                            ));
                        }
                        
                        if(in_array($fila[4], $cod_encontrados)){//SE BUSCAN Y DESCARTA LOS SKU ENCONTRADOS Y SE REGISTRA
                            unset($m[$k]);
                            $item_descartados++;
                        }
                        else{
                            $tipo_codigo[] = $fila[0];
                            if($fila[1] !== '') $fabricante[] = $fila[1];
                            if($fila[2] !== '') $marca[] = $fila[2];
                        }
                        set_time_limit(10);
                        $time_taken = microtime(true) - $start_valid;
                        if($time_taken >= 600){
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'TIEMPO EXCEDIDO ('.round($time_taken,1).' SEG) EN VALIDAR. EL TIEMPO MAX ES DE 600 SEG. LO QUE DEBERIA ALCANZAR PARA PROCESAR APROX 45 MIL FILAS. SI SU ARCHIVO TIENE MAS, POR FAVOR SAQUE LAS SUFICIENTES FILAS.'
                            ));
                        }
                    }

                    

                    

                    if(count($m) === 0){//NO SE INGRESAN DATOS
                        return new JsonResponse(array(
                            'status' => false,
                            'mensaje' => 'VERIFIQUE QUE EL CSV TIENE DATOS Y QUE LOS SKU NO EXISTEN EN LA BD'
                        ));
                    }

                    //SE VALIDA QUE EXISTA TIPOCODIGO
                    if(isset($tipo_codigo)){
                        $tipo_codigo = array_unique($tipo_codigo);
                        sort($tipo_codigo);

                        $sql = "SELECT tc.NOMBRE as nombre, tc.ID as id FROM TIPOCODIGO tc
                                WHERE tc.NOMBRE IN ( ? )";
                        $param = array($tipo_codigo);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

                        usort($query, array($this,"cmp"));

                        foreach ($tipo_codigo as $k => $v) {
                            if(!isset($query[$k]['nombre']) || $v !== $query[$k]['nombre']){
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'EL TIPO CODIGO "'.$v.'" NO EXISTE EN LA BD O NO CONCUERDA.'
                                ));
                            }
                            $tipo_codigo_[$v] = $query[$k]['id'];
                        }
                    }
                    

                    //SE VALIDA QUE EXISTA FABRICANTE
                    if(isset($fabricante)){
                         $fabricante = array_unique($fabricante);
                        sort($fabricante);

                        $sql = "SELECT f.NOMBRE as nombre, f.ID as id FROM FABRICANTE f
                                WHERE f.NOMBRE IN ( ? )";
                        $param = array($fabricante);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

                        usort($query, array($this,"cmp"));

                        foreach ($fabricante as $k => $v) {
                            if(!isset($query[$k]['nombre']) || $v !== $query[$k]['nombre']){
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'EL FABRICANTE "'.$v.'" NO EXISTE EN LA BD O NO CONCUERDA.'
                                ));
                            }
                            $fabricante_[$v] = $query[$k]['id'];
                        }
                    }
                       

                    //SE VALIDA QUE EXITA MARCA
                    if(isset($marca)){
                        $marca = array_unique($marca);
                        sort($marca);

                        $sql = "SELECT m.NOMBRE as nombre, m.ID as id FROM MARCA m
                                WHERE m.NOMBRE IN ( ? )
                                ORDER BY m.NOMBRE";
                        $param = array($marca);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

                        usort($query, array($this,"cmp"));

                        foreach ($marca as $k => $v) {
                            if(!isset($query[$k]['nombre']) || $v !== $query[$k]['nombre']){
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'LA MARCA "'.$v.'" NO EXISTE EN LA BD O NO CONCUERDA.'
                                ));
                            }
                            $marca_[$v] = $query[$k]['id'];
                        }
                    }

                    set_time_limit(10);
                    //FORMATO ES: TIPOCODIGO_ID;FABRICANTE;MARCA;NOMBRE;CODIGO
                    //ARCHIVO A ESCRIBIR CON LOS IDs FINALES
                    $fp = fopen($this->uploadDIR.$name.'_proc.csv', 'w');

                    foreach ($m as $fields) {
                        $id_tipo_codigo = (isset($tipo_codigo_[$fields[0]]))?$tipo_codigo_[$fields[0]]:"NULL";
                        $id_fabricante = (isset($fabricante_[$fields[1]]))?$fabricante_[$fields[1]]:"NULL";
                        $id_marca = (isset($marca_[$fields[2]]))?$marca_[$fields[2]]:"NULL";
                        $fila = array_merge($fields, array($id_tipo_codigo, $id_fabricante, $id_marca));
                        fputcsv($fp, $fila,";");
                    }
                    fclose($fp);


                    break;//TERMINA ITEM
                
                case 'sala'://DATOS DE SALA
                    

                    
                    //FORMATO ES: COMUNA;CANAL;CADENA;FORMATO;FOLIO;CALLE;NUMERO
                    //SI LA PRIMERA FILA TIENE LOS ENCABEZADOS SE BORRA
                    if($m[0][0] === 'COMUNA' || $m[0][4] === 'FOLIO') unset($m[0]);
                    //SE SEPARA EN CHUNK PARA NO SOBRECARGAR
                    $chunk = 0;
                    foreach($m as $value){
                        $folio[floor($chunk/2000)][] = $value[4];
                        $chunk++;
                    }
                    

                    //SE VALIDA QUE EL FOLIO NO ESTE EN LA BD
                    $folio_encontrados = array();
                    foreach($folio as $k => $chunk){
                        $sql = "SELECT s.FOLIOCADEM as folio FROM SALA s
                                WHERE s.FOLIOCADEM IN ( ? )
                                ORDER BY s.FOLIOCADEM";
                        $param = array($chunk);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();
                        
                        foreach ($query as $v) $folio_encontrados[] = $v['folio'];
                    }
                    

                    foreach ($m as $k => $fila) {
                        if(count($fila) !== 7){//SIEMPRE DEBEN HABER 7 COLUMNAS
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'NO HAY 7 COLUMNAS CERCA DE LA LINEA '.$k
                            ));
                        }
                        if(strlen($fila[0]) === 0){//LA COMUNA NO PUEDE SER VACIO
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'LA COMUNA NO PUEDE ESTAR VACIA, CERCA DE LA LINEA '.$k
                            ));
                        }
                        if(strlen($fila[1]) === 0){//EL CANAL NO PUEDE SER VACIO
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'EL CANAL NO PUEDE ESTAR VACIA, CERCA DE LA LINEA '.$k
                            ));
                        }
                        if(strlen($fila[4]) === 0){//EL FOLIO NO PUEDE SER VACIO
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'EL FOLIO NO PUEDE ESTAR VACIO, CERCA DE LA LINEA '.$k
                            ));
                        }
                        if(in_array($fila[4], $folio_encontrados)){//SE BUSCAN Y DESCARTA LOS FOLIOS ENCONTRADOS Y SE REGISTRA
                            unset($m[$k]);
                            $item_descartados++;
                        }
                        else{
                            if($fila[0] !== '') $comuna[] = $fila[0];
                            if($fila[1] !== '') $canal[] = $fila[1];
                            if($fila[2] !== '') $cadena[] = $fila[2];
                            if($fila[3] !== '') $formato[] = $fila[3];
                        }
                    }

                    if(count($m) === 0){//NO SE INGRESAN DATOS
                        return new JsonResponse(array(
                            'status' => false,
                            'mensaje' => 'VERIFIQUE QUE EL CSV TIENE DATOS Y QUE LOS SKU NO EXISTEN EN LA BD'
                        ));
                    }

                    //SE VALIDA QUE EXISTA COMUNA
                    if(isset($comuna)){
                        $comuna = array_unique($comuna);
                        sort($comuna);

                        $sql = "SELECT c.NOMBRE as nombre, c.ID as id FROM COMUNA c
                                WHERE c.NOMBRE IN ( ? )";
                        $param = array($comuna);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

                        usort($query, array($this,"cmp"));

                        foreach ($comuna as $k => $v) {
                            if(!isset($query[$k]['nombre']) || $v !== $query[$k]['nombre']){
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'LA COMUNA "'.$v.'" NO EXISTE EN LA BD.'
                                ));
                            }
                            $comuna_[$v] = $query[$k]['id'];
                        }
                    }
                    

                    //SE VALIDA QUE EXISTA CANAL
                    if(isset($canal)){
                         $canal = array_unique($canal);
                        sort($canal);

                        $sql = "SELECT c.NOMBRE as nombre, c.ID as id FROM CANAL c
                                WHERE c.NOMBRE IN ( ? )";
                        $param = array($canal);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

                        usort($query, array($this,"cmp"));

                        foreach ($canal as $k => $v) {
                            if(!isset($query[$k]['nombre']) || $v !== $query[$k]['nombre']){
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'EL CANAL "'.$v.'" NO EXISTE EN LA BD.'
                                ));
                            }
                            $canal_[$v] = $query[$k]['id'];
                        }
                    }
                       

                    //SE VALIDA QUE EXISTA CADENA
                    if(isset($cadena)){
                        $cadena = array_unique($cadena);
                        sort($cadena);

                        $sql = "SELECT c.NOMBRE as nombre, c.ID as id FROM CADENA c
                                WHERE c.NOMBRE IN ( ? )";
                        $param = array($cadena);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

                        usort($query, array($this,"cmp"));

                        foreach ($cadena as $k => $v) {
                            if(!isset($query[$k]['nombre']) || $v !== $query[$k]['nombre']){
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'LA CADENA "'.$v.'" NO EXISTE EN LA BD.'
                                ));
                            }
                            $cadena_[$v] = $query[$k]['id'];
                        }
                    }


                    //SE VALIDA QUE EXISTA FORMATO
                    if(isset($formato)){
                        $formato = array_unique($formato);
                        sort($formato);

                        $sql = "SELECT f.NOMBRE as nombre, f.ID as id FROM FORMATO f
                                WHERE f.NOMBRE IN ( ? )";
                        $param = array($formato);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

                        usort($query, array($this,"cmp"));

                        foreach ($formato as $k => $v) {
                            if(!isset($query[$k]['nombre']) || $v !== $query[$k]['nombre']){
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'LA CADENA "'.$v.'" NO EXISTE EN LA BD.'
                                ));
                            }
                            $formato_[$v] = $query[$k]['id'];
                        }
                    }


                    //FORMATO ES: COMUNA;CANAL;CADENA;FORMATO;FOLIO;CALLE;NUMERO;ID_COMUNA;ID_CANAL;ID_CADENA;ID_FORMATO
                    //ARCHIVO A ESCRIBIR CON LOS IDs FINALES
                    $fp = fopen($this->uploadDIR.$name.'_proc.csv', 'w');

                    foreach ($m as $fields) {
                        $id_comuna = (isset($comuna_[$fields[0]]))?$comuna_[$fields[0]]:"NULL";
                        $id_canal = (isset($canal_[$fields[1]]))?$canal_[$fields[1]]:"NULL";
                        $id_cadena = (isset($cadena_[$fields[2]]))?$cadena_[$fields[2]]:"NULL";
                        $id_formato = (isset($formato_[$fields[3]]))?$formato_[$fields[3]]:"NULL";
                        $fila = array_merge($fields, array($id_comuna, $id_canal, $id_cadena, $id_formato));
                        fputcsv($fp, $fila,";");
                    }
                    fclose($fp);




                    break;

                case 'itemcliente'://DATOS DE ITEMCLIENTE


                    //FORMATO ES:
                    // 0->NOMBRE NIVELITEM_ID1;
                    // 1->NIVELITEM_ID1;
                    // 2->NOMBRE NIVELITEM_ID2;
                    // 3->NIVELITEM_ID2;
                    // 4->NOMBRE NIVELITEM_ID3;
                    // 5->NIVELITEM_ID3;
                    // 6->NOMBRE NIVELITEM_ID4;
                    // 7->NIVELITEM_ID4;
                    // 8->NOMBRE NIVELITEM_ID5;
                    // 9->NIVELITEM_ID5;
                    // 10->EAN_ITEM
                    // 11->EAN_PADRE
                    // 12->TIPOCODIGO_ID;
                    // 13->CODIGOITEM1;
                    // 14->CODIGOITEM2

                    
                    //SI LA PRIMERA FILA TIENE LOS ENCABEZADOS SE BORRA
                    if($m[0][0] === 'NOMBRE NIVELITEM_ID1' || $m[0][1] === 'NIVELITEM_ID1' || $m[0][13] === 'CODIGOITEM1') unset($m[0]);
                    

                    //SE VALIDA QUE EL ITEM NO ESTE EN LA BD
                    $chunk = 0;
                    foreach($m as $value){
                        $item[floor($chunk/2000)][] = $value[13];
                        $item_ean[floor($chunk/2000)][] = $value[10];
                        $chunk++;
                    }

                    $item_encontrados = array();
                    $start_select = microtime(true);
                    foreach($item as $k => $chunk){
                        $sql = "SELECT ic.CODIGOITEM1 as codigo FROM ITEMCLIENTE ic
                                WHERE ic.CODIGOITEM1 IN ( ? ) and ic.CLIENTE_ID = ? and ic.MEDICION_ID = ?
                                ORDER BY ic.CODIGOITEM1";
                        $param = array($chunk, $id_cliente, $id_medicion);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY, \PDO::PARAM_INT, \PDO::PARAM_INT);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();
                        foreach ($query as $v) $item_encontrados[] = $v['codigo'];
                        set_time_limit(10);
                        $time_taken = microtime(true) - $start_select;
                        if($time_taken >= 600){
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'TIEMPO EXCEDIDO ('.round($time_taken,1).' SEG) EN CONSULTAR. EL TIEMPO MAX ES DE 600 SEG. LO QUE DEBERIA ALCANZAR PARA PROCESAR APROX 45 MIL FILAS. SI SU ARCHIVO TIENE MAS, POR FAVOR SAQUE LAS SUFICIENTES FILAS.'
                            ));
                        }
                    }
                    
                    $start_valid = microtime(true);
                    foreach ($m as $k => $fila) {
                        if(count($fila) !== 15){//SIEMPRE DEBEN HABER 14 COLUMNAS
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'NO HAY 15 COLUMNAS CERCA DE LA LINEA '.$k
                            ));
                        }
                        if(strlen($fila[0]) === 0){//LA "NOMBRE NIVELITEM_ID1" NO PUEDE SER VACIA
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'LA "NOMBRE NIVELITEM_ID1" NO PUEDE ESTAR VACIA, CERCA DE LA LINEA '.$k
                            ));
                        }
                        if(strlen($fila[1]) === 0){//EL "NIVELITEM_ID1" NO PUEDE SER VACIO
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'EL "NIVELITEM_ID1" NO PUEDE ESTAR VACIA, CERCA DE LA LINEA '.$k
                            ));
                        }
                        if(strlen($fila[10]) != 13){//EL EAN_ITEM DEBE SER VALIDO
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'EL "EAN_ITEM" NO PUEDE ESTAR VACIO Y DEBE SER VALIDO, CERCA DE LA LINEA '.$k
                            ));
                        }
                        if(strlen($fila[13]) === 0){//EL CODIGOITEM1 NO PUEDE SER VACIO
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'EL "CODIGOITEM1" NO PUEDE ESTAR VACIO, CERCA DE LA LINEA '.$k
                            ));
                        }
                        if(in_array($fila[13], $item_encontrados)){//SE BUSCAN Y DESCARTA LOS CODIGOITEM1 ENCONTRADOS Y SE REGISTRA
                            unset($m[$k]);
                            $item_descartados++;
                        }
                        else{
                            if($fila[0] !== '') $nni1[] = $fila[0];
                            if($fila[1] !== '') $ni1[] = $fila[1];
                            if($fila[2] !== '') $nni2[] = $fila[2];
                            if($fila[3] !== '') $ni2[] = $fila[3];
                            if($fila[4] !== '') $nni3[] = $fila[4];
                            if($fila[5] !== '') $ni3[] = $fila[5];
                            if($fila[6] !== '') $nni4[] = $fila[6];
                            if($fila[7] !== '') $ni4[] = $fila[7];
                            if($fila[8] !== '') $nni5[] = $fila[8];
                            if($fila[9] !== '') $ni5[] = $fila[9];
                            
                            if($fila[11] !== '') $item_padre[] = $fila[11];
                            if($fila[12] !== '') $tipo_codigo[] = $fila[12];
                        }
                        set_time_limit(10);
                        $time_taken = microtime(true) - $start_valid;
                        if($time_taken >= 600){
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'TIEMPO EXCEDIDO ('.round($time_taken,1).' SEG) EN VALIDAR. EL TIEMPO MAX ES DE 600 SEG. LO QUE DEBERIA ALCANZAR PARA PROCESAR APROX 45 MIL FILAS. SI SU ARCHIVO TIENE MAS, POR FAVOR SAQUE LAS SUFICIENTES FILAS.'
                            ));
                        }
                    }

                    if(count($m) === 0){//NO SE INGRESAN DATOS
                        return new JsonResponse(array(
                            'status' => false,
                            'mensaje' => 'VERIFIQUE QUE EL CSV TIENE DATOS Y QUE LOS SKU NO EXISTEN EN LA BD'
                        ));
                    }

                    //SE VALIDA QUE EXISTA "NOMBRE NIVELITEM_ID1" y "NIVELITEM_ID1"
                    if(isset($nni1) && count($nni1) > 0 && isset($ni1) && count($ni1) > 0){
                        foreach($ni1 as $k => $v) $nni_ni1[$k] = $nni1[$k].'-'.$v;
                        $nni_ni1 = array_unique($nni_ni1);
                        sort($nni_ni1);

                        $sql = "SELECT cni.NOMBRE + '-' + ni.NOMBRE as nombre, ni.ID as id FROM NIVELITEM ni
                                INNER JOIN CLASNIVELITEM cni on cni.ID = ni.CLASNIVELITEM_ID
                                WHERE cni.NOMBRE + '-' + ni.NOMBRE IN ( ? ) and cni.CLIENTE_ID = ?";
                        $param = array($nni_ni1,$id_cliente);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY, \PDO::PARAM_INT);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

                        usort($query, array($this,"cmp"));

                        foreach ($nni_ni1 as $k => $v) {
                            if(!isset($query[$k]['nombre']) || $v !== $query[$k]['nombre']){
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'LA CLASE "'.$v.'" DEL NIVEL1 NO EXISTE EN LA BD.'
                                ));
                            }
                            $nni_ni1_[$v] = $query[$k]['id'];
                        }
                    }


                    //SE VALIDA QUE EXISTA "NOMBRE NIVELITEM_ID2" y "NIVELITEM_ID2"
                    if(isset($nni2) && count($nni2) > 0 && isset($ni2) && count($ni2) > 0){
                        foreach($ni2 as $k => $v) $nni_ni2[$k] = $nni2[$k].'-'.$v;
                        $nni_ni2 = array_unique($nni_ni2);
                        sort($nni_ni2);

                        $sql = "SELECT cni.NOMBRE + '-' + ni.NOMBRE as nombre, ni.ID as id FROM NIVELITEM ni
                                INNER JOIN CLASNIVELITEM cni on cni.ID = ni.CLASNIVELITEM_ID
                                WHERE cni.NOMBRE + '-' + ni.NOMBRE IN ( ? ) and cni.CLIENTE_ID = ?";
                        $param = array($nni_ni2,$id_cliente);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY, \PDO::PARAM_INT);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

                        usort($query, array($this,"cmp"));

                        foreach ($nni_ni2 as $k => $v) {
                            if(!isset($query[$k]['nombre']) || $v !== $query[$k]['nombre']){
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'LA CLASE "'.$v.'" DEL NIVEL2 NO EXISTE EN LA BD.'
                                ));
                            }
                            $nni_ni2_[$v] = $query[$k]['id'];
                        }
                    }

                    //SE VALIDA QUE EXISTA "NOMBRE NIVELITEM_ID3" y "NIVELITEM_ID3"
                    if(isset($nni3) && count($nni3) > 0 && isset($ni3) && count($ni3) > 0){
                        foreach($ni3 as $k => $v) $nni_ni3[$k] = $nni3[$k].'-'.$v;
                        $nni_ni3 = array_unique($nni_ni3);
                        sort($nni_ni3);

                        $sql = "SELECT cni.NOMBRE + '-' + ni.NOMBRE as nombre, ni.ID as id FROM NIVELITEM ni
                                INNER JOIN CLASNIVELITEM cni on cni.ID = ni.CLASNIVELITEM_ID
                                WHERE cni.NOMBRE + '-' + ni.NOMBRE IN ( ? ) and cni.CLIENTE_ID = ?";
                        $param = array($nni_ni3,$id_cliente);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY, \PDO::PARAM_INT);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

                        usort($query, array($this,"cmp"));

                        foreach ($nni_ni3 as $k => $v) {
                            if(!isset($query[$k]['nombre']) || $v !== $query[$k]['nombre']){
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'LA CLASE "'.$v.'" DEL NIVEL3 NO EXISTE EN LA BD.'
                                ));
                            }
                            $nni_ni3_[$v] = $query[$k]['id'];
                        }
                    }

                    //SE VALIDA QUE EXISTA "NOMBRE NIVELITEM_ID4" y "NIVELITEM_ID4"
                    if(isset($nni4) && count($nni4) > 0 && isset($ni4) && count($ni4) > 0){
                        foreach($ni4 as $k => $v) $nni_ni4[$k] = $nni4[$k].'-'.$v;
                        $nni_ni4 = array_unique($nni_ni4);
                        sort($nni_ni4);

                        $sql = "SELECT cni.NOMBRE + '-' + ni.NOMBRE as nombre, ni.ID as id FROM NIVELITEM ni
                                INNER JOIN CLASNIVELITEM cni on cni.ID = ni.CLASNIVELITEM_ID
                                WHERE cni.NOMBRE + '-' + ni.NOMBRE IN ( ? ) and cni.CLIENTE_ID = ?";
                        $param = array($nni_ni4,$id_cliente);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY, \PDO::PARAM_INT);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

                        usort($query, array($this,"cmp"));

                        foreach ($nni_ni4 as $k => $v) {
                            if(!isset($query[$k]['nombre']) || $v !== $query[$k]['nombre']){
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'LA CLASE "'.$v.'" DEL NIVEL4 NO EXISTE EN LA BD.'
                                ));
                            }
                            $nni_ni4_[$v] = $query[$k]['id'];
                        }
                    }


                    //SE VALIDA QUE EXISTA "NOMBRE NIVELITEM_ID5" y "NIVELITEM_ID5"
                    if(isset($nni5) && count($nni5) > 0 && isset($ni5) && count($ni5) > 0){
                        foreach($ni5 as $k => $v) $nni_ni5[$k] = $nni5[$k].'-'.$v;
                        $nni_ni5 = array_unique($nni_ni5);
                        sort($nni_ni5);

                        $sql = "SELECT cni.NOMBRE + '-' + ni.NOMBRE as nombre, ni.ID as id FROM NIVELITEM ni
                                INNER JOIN CLASNIVELITEM cni on cni.ID = ni.CLASNIVELITEM_ID
                                WHERE cni.NOMBRE + '-' + ni.NOMBRE IN ( ? ) and cni.CLIENTE_ID = ?";
                        $param = array($nni_ni5,$id_cliente);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY, \PDO::PARAM_INT);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

                        usort($query, array($this,"cmp"));

                        foreach ($nni_ni5 as $k => $v) {
                            if(!isset($query[$k]['nombre']) || $v !== $query[$k]['nombre']){
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'LA CLASE "'.$v.'" DEL NIVEL5 NO EXISTE EN LA BD.'
                                ));
                            }
                            $nni_ni5_[$v] = $query[$k]['id'];
                        }
                    }
                       

                    //SE VALIDA QUE EXISTA EL ITEM
                    foreach($item_ean as $chunk){
                        if(count($chunk) > 0){
                            $chunk = array_unique($chunk);
                            sort($chunk);

                            $sql = "SELECT i.CODIGO as nombre, i.ID as id FROM ITEM i
                                    WHERE i.CODIGO IN ( ? )";
                            $param = array($chunk);
                            $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
                            $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

                            usort($query, array($this,"cmp"));

                            foreach ($chunk as $k => $v) {
                                if(!isset($query[$k]['nombre']) || $v !== $query[$k]['nombre']){
                                    return new JsonResponse(array(
                                        'status' => false,
                                        'mensaje' => 'EL ITEM "'.$v.'" NO EXISTE EN LA BD.'
                                    ));
                                }
                                $item_ean_[$v] = $query[$k]['id'];
                            }
                        }
                    }

                    //SE VALIDA QUE EXISTA EL ITEMPADRE
                    if(isset($item_padre)){
                        $item_padre = array_unique($item_padre);
                        sort($item_padre);

                        $sql = "SELECT i.NOMBRE as nombre, i.ID as id FROM ITEM i
                                WHERE i.NOMBRE IN ( ? )";
                        $param = array($item_padre);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

                        usort($query, array($this,"cmp"));

                        foreach ($item_padre as $k => $v) {
                            if(!isset($query[$k]['nombre']) || $v !== $query[$k]['nombre']){
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'EL ITEM PADRE "'.$v.'" NO EXISTE EN LA BD.'
                                ));
                            }
                            $item_padre_[$v] = $query[$k]['id'];
                        }
                    }


                    //SE VALIDA QUE EXISTA EL TIPO DE CODIGO
                    if(isset($tipo_codigo)){
                        $tipo_codigo = array_unique($tipo_codigo);
                        sort($tipo_codigo);

                        $sql = "SELECT tc.NOMBRE as nombre, tc.ID as id FROM TIPOCODIGO tc
                                WHERE tc.NOMBRE IN ( ? )";
                        $param = array($tipo_codigo);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

                        usort($query, array($this,"cmp"));

                        foreach ($tipo_codigo as $k => $v) {
                            if(!isset($query[$k]['nombre']) || $v !== $query[$k]['nombre']){
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'EL TIPOCODIGO "'.$v.'" NO EXISTE EN LA BD.'
                                ));
                            }
                            $tipo_codigo_[$v] = $query[$k]['id'];
                        }
                    }


                    //FORMATO ES:
                    // 0->NOMBRE NIVELITEM_ID1;
                    // 1->NIVELITEM_ID1;
                    // 2->NOMBRE NIVELITEM_ID2;
                    // 3->NIVELITEM_ID2;
                    // 4->NOMBRE NIVELITEM_ID3;
                    // 5->NIVELITEM_ID3;
                    // 6->NOMBRE NIVELITEM_ID4;
                    // 7->NIVELITEM_ID4;
                    // 8->NOMBRE NIVELITEM_ID5;
                    // 9->NIVELITEM_ID5;
                    // 10->EAN_ITEM
                    // 11->EAN_PADRE
                    // 12->TIPOCODIGO_ID;
                    // 13->CODIGOITEM1;
                    // 14->CODIGOITEM2

                    // 15->ID_NIVELITEM1
                    // 16->ID_NIVELITEM2
                    // 17->ID_NIVELITEM3
                    // 18->ID_NIVELITEM4
                    // 19->ID_NIVELITEM5
                    // 20->ID_ITEM
                    // 21->ID_ITEM2
                    // 22->ID_TIPOCODIGO

                    //ARCHIVO A ESCRIBIR CON LOS IDs FINALES
                    $fp = fopen($this->uploadDIR.$name.'_proc.csv', 'w');

                    foreach ($m as $fields) {
                        $id_ni1 = (isset($nni_ni1_[$fields[0].'-'.$fields[1]]))?$nni_ni1_[$fields[0].'-'.$fields[1]]:"NULL";
                        $id_ni2 = (isset($nni_ni2_[$fields[2].'-'.$fields[3]]))?$nni_ni2_[$fields[2].'-'.$fields[3]]:"NULL";
                        $id_ni3 = (isset($nni_ni3_[$fields[4].'-'.$fields[5]]))?$nni_ni3_[$fields[4].'-'.$fields[5]]:"NULL";
                        $id_ni4 = (isset($nni_ni4_[$fields[6].'-'.$fields[7]]))?$nni_ni4_[$fields[6].'-'.$fields[7]]:"NULL";
                        $id_ni5 = (isset($nni_ni5_[$fields[8].'-'.$fields[9]]))?$nni_ni5_[$fields[8].'-'.$fields[9]]:"NULL";

                        $id_item = (isset($item_ean_[$fields[10]]))?$item_ean_[$fields[10]]:"NULL";
                        $id_item_padre = (isset($item_padre_[$fields[11]]))?$item_padre_[$fields[11]]:"NULL";
                        $id_tipo_codigo = (isset($tipo_codigo_[$fields[12]]))?$tipo_codigo_[$fields[12]]:"NULL";

                        $fila = array_merge($fields, array($id_ni1, $id_ni2, $id_ni3, $id_ni4, $id_ni5, $id_item, $id_item_padre, $id_tipo_codigo));
                        fputcsv($fp, $fila,";");
                    }
                    fclose($fp);

                    break;





                case 'salacliente'://DATOS DE SALACLIENTE


                    //FORMATO ES:
                    //FOLIO_CADEM;CODIGOSALA;RESPONSABLE

                    
                    //SI LA PRIMERA FILA TIENE LOS ENCABEZADOS SE BORRA
                    if($m[0][0] === 'FOLIO_CADEM' || $m[0][1] === 'CODIGOSALA' || $m[0][2] === 'RESPONSABLE') unset($m[0]);
                    

                    //SE VALIDA QUE EL CODIGOSALA NO ESTE EN LA BD
                    $chunk = 0;
                    foreach($m as $value){
                        $codigo_sala[floor($chunk/2000)][] = $value[1];
                        $chunk++;
                    }

                    $codigo_sala_encontrados = array();
                    $start_select = microtime(true);
                    foreach($codigo_sala as $k => $chunk){
                        $sql = "SELECT sc.CODIGOSALA as codigo FROM SALACLIENTE sc
                                WHERE sc.CODIGOSALA IN ( ? ) and sc.CLIENTE_ID = ? and sc.MEDICION_ID = ?
                                ORDER BY sc.CODIGOSALA";
                        $param = array($chunk, $id_cliente, $id_medicion);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY, \PDO::PARAM_INT, \PDO::PARAM_INT);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();
                        foreach ($query as $v) $codigo_sala_encontrados[] = $v['codigo'];
                        set_time_limit(10);
                        $time_taken = microtime(true) - $start_select;
                        if($time_taken >= 600){
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'TIEMPO EXCEDIDO ('.round($time_taken,1).' SEG) EN CONSULTAR. EL TIEMPO MAX ES DE 600 SEG. LO QUE DEBERIA ALCANZAR PARA PROCESAR APROX 45 MIL FILAS. SI SU ARCHIVO TIENE MAS, POR FAVOR SAQUE LAS SUFICIENTES FILAS.'
                            ));
                        }
                    }
                    
                    $start_valid = microtime(true);
                    foreach ($m as $k => $fila) {
                        if(count($fila) !== 3){//SIEMPRE DEBEN HABER 3 COLUMNAS
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'NO HAY 15 COLUMNAS CERCA DE LA LINEA '.$k
                            ));
                        }
                        if(strlen($fila[0]) === 0){//LA "FOLIO_CADEM" NO PUEDE SER VACIA
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'EL "FOLIO_CADEM" NO PUEDE ESTAR VACIA, CERCA DE LA LINEA '.$k
                            ));
                        }
                        // if(strlen($fila[1]) === 0){//EL "CODIGOSALA" NO PUEDE SER VACIO
                        //     return new JsonResponse(array(
                        //         'status' => false,
                        //         'mensaje' => 'EL "CODIGOSALA" NO PUEDE ESTAR VACIA, CERCA DE LA LINEA '.$k
                        //     ));
                        // }
                        if(in_array($fila[1], $codigo_sala_encontrados)){//SE BUSCAN Y DESCARTA LOS CODIGOSALA ENCONTRADOS Y SE REGISTRA
                            unset($m[$k]);
                            $item_descartados++;
                        }
                        else{
                            if($fila[0] !== '') $folio[] = $fila[0];
                            if($fila[1] === '') $m[$k][1] = "NULL";
                            if($fila[2] !== '') $empleado[] = $fila[2];
                        }
                        set_time_limit(10);
                        $time_taken = microtime(true) - $start_valid;
                        if($time_taken >= 600){
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'TIEMPO EXCEDIDO ('.round($time_taken,1).' SEG) EN VALIDAR. EL TIEMPO MAX ES DE 600 SEG. LO QUE DEBERIA ALCANZAR PARA PROCESAR APROX 45 MIL FILAS. SI SU ARCHIVO TIENE MAS, POR FAVOR SAQUE LAS SUFICIENTES FILAS.'
                            ));
                        }
                    }

                    if(count($m) === 0){//NO SE INGRESAN DATOS
                        return new JsonResponse(array(
                            'status' => false,
                            'mensaje' => 'VERIFIQUE QUE EL CSV TIENE DATOS Y QUE LOS SKU NO EXISTEN EN LA BD'
                        ));
                    }

                    
                    //SE VALIDA QUE EXISTA EL FOLIO_CADEM
                    if(isset($folio)){
                        $folio = array_unique($folio);
                        sort($folio);

                        $sql = "SELECT s.FOLIOCADEM as nombre, s.ID as id FROM SALA s
                                WHERE s.FOLIOCADEM IN ( ? )";
                        $param = array($folio);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

                        usort($query, array($this,"cmp"));

                        foreach ($folio as $k => $v) {
                            if(!isset($query[$k]['nombre']) || $v !== $query[$k]['nombre']){
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'LA SALA "'.$v.'" NO EXISTE EN LA BD.'
                                ));
                            }
                            $folio_[$v] = $query[$k]['id'];
                        }
                    }


                    //SE VALIDA QUE EXISTA EL RESPONSABLE
                    if(isset($empleado)){
                        $empleado = array_unique($empleado);
                        sort($empleado);

                        $sql = "SELECT e.NOMBRE as nombre, e.ID as id FROM EMPLEADO e
                                WHERE e.NOMBRE IN ( ? )";
                        $param = array($empleado);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

                        usort($query, array($this,"cmp"));

                        foreach ($empleado as $k => $v) {
                            if(!isset($query[$k]['nombre']) || $v !== $query[$k]['nombre']){
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'EL EMPLEADO "'.$v.'" NO EXISTE EN LA BD.'
                                ));
                            }
                            $empleado_[$v] = $query[$k]['id'];
                        }
                    }
                       

                    


                    //FORMATO ES:
                    //FOLIO_CADEM;CODIGOSALA;RESPONSABLE;ID_EMPLEADO;ID_SALA

                    //ARCHIVO A ESCRIBIR CON LOS IDs FINALES
                    $fp = fopen($this->uploadDIR.$name.'_proc.csv', 'w');

                    foreach ($m as $fields) {
                        $id_empleado = (isset($empleado_[$fields[2]]))?$empleado_[$fields[2]]:"NULL";
                        $id_sala = (isset($folio_[$fields[0]]))?$folio_[$fields[0]]:"NULL";


                        $fila = array_merge($fields, array($id_empleado, $id_sala));
                        fputcsv($fp, $fila,";");
                    }
                    fclose($fp);

                    break;








                case 'planoquiebre'://DATOS DE PLANOGRAMA QUIEBRE


                    //FORMATO ES:
                    // FOLIO;EAN;FECHA;QUIEBRE;CANTIDAD

                    
                    //SI LA PRIMERA FILA TIENE LOS ENCABEZADOS SE BORRA
                    if($m[0][0] === 'FOLIO' || $m[0][1] === 'EAN' || $m[0][2] === 'FECHA') unset($m[0]);
                    

                    //SE VALIDA QUE EL CODIGOSALA NO ESTE EN LA BD
                    $chunk = 0;
                    foreach($m as $k => $value){
                        // $folio_sala[] = $value[0];
                        // $item[] = $value[1];
                        $folioean[floor($chunk/2000)][] = $value[0].'-'.$value[1];
                        if(strlen($value[4]) === 0) $m[$k][4] = "NULL"; //SI NO HAY CANTIDAD SE PASA A NULL PARA EL INSERT
                        if(strlen($value[2]) === 0) $m[$k][2] = "NULL"; //SI NO HAY FECHA SE PASA A NULL PARA EL INSERT
                        $chunk++;
                    }

                    $folioean_encontrados = array();
                    $start_select = microtime(true);
                    foreach($folioean as $k => $chunk){
                        $sql = "SELECT s.FOLIOCADEM+'-'+i.CODIGO as folioean FROM PLANOGRAMAQ p
                                INNER JOIN SALACLIENTE sc on p.SALACLIENTE_ID = sc.ID
                                INNER JOIN SALA s on s.ID = sc.SALA_ID
                                INNER JOIN ITEMCLIENTE ic on p.ITEMCLIENTE_ID = ic.ID
                                INNER JOIN ITEM i on i.ID = ic.ITEM_ID
                                WHERE s.FOLIOCADEM+'-'+i.CODIGO IN ( ? ) and sc.CLIENTE_ID = ? and sc.MEDICION_ID = ? and ic.CLIENTE_ID = ? and ic.MEDICION_ID = ?
                                ";
                        $param = array($chunk, $id_cliente, $id_medicion, $id_cliente, $id_medicion);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();
                        foreach ($query as $v) $folioean_encontrados[] = $v['folioean'];
                        set_time_limit(10);
                        $time_taken = microtime(true) - $start_select;
                        if($time_taken >= 600){
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'TIEMPO EXCEDIDO ('.round($time_taken,1).' SEG) EN CONSULTAR. EL TIEMPO MAX ES DE 600 SEG. LO QUE DEBERIA ALCANZAR PARA PROCESAR APROX 45 MIL FILAS. SI SU ARCHIVO TIENE MAS, POR FAVOR SAQUE LAS SUFICIENTES FILAS.'
                            ));
                        }
                    }
                    
                    $start_valid = microtime(true);
                    foreach ($m as $k => $fila) {
                        if(count($fila) !== 5){//SIEMPRE DEBEN HABER 5 COLUMNAS
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'NO HAY 5 COLUMNAS CERCA DE LA LINEA '.$k
                            ));
                        }
                        if(strlen($fila[0]) === 0){//LA "FOLIO" NO PUEDE SER VACIA
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'EL "FOLIO" NO PUEDE ESTAR VACIA, CERCA DE LA LINEA '.$k
                            ));
                        }
                        if(strlen($fila[1]) === 0){//EL "EAN" NO PUEDE SER VACIO
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'EL "EAN" NO PUEDE ESTAR VACIA, CERCA DE LA LINEA '.$k
                            ));
                        }
                        if(strlen($fila[3]) === 0){//EL "QUIEBRE" NO PUEDE SER VACIO
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'EL "QUIEBRE" NO PUEDE ESTAR VACIA, CERCA DE LA LINEA '.$k
                            ));
                        }
                        if(in_array($fila[0].'-'.$fila[1], $folioean_encontrados)){//SE BUSCAN Y DESCARTA LOS CODIGOSALA ENCONTRADOS Y SE REGISTRA
                            unset($m[$k]);
                            $item_descartados++;
                        }
                        else{
                            if($fila[0] !== '') $folio[] = $fila[0];
                            if($fila[1] !== '') $item[] = $fila[1];
                        }
                        set_time_limit(10);
                        $time_taken = microtime(true) - $start_valid;
                        if($time_taken >= 600){
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'TIEMPO EXCEDIDO ('.round($time_taken,1).' SEG) EN VALIDAR. EL TIEMPO MAX ES DE 600 SEG. LO QUE DEBERIA ALCANZAR PARA PROCESAR APROX 45 MIL FILAS. SI SU ARCHIVO TIENE MAS, POR FAVOR SAQUE LAS SUFICIENTES FILAS.'
                            ));
                        }
                    }

                    if(count($m) === 0){//NO SE INGRESAN DATOS
                        return new JsonResponse(array(
                            'status' => false,
                            'mensaje' => 'VERIFIQUE QUE EL CSV TIENE DATOS Y QUE LOS SKU NO EXISTEN EN LA BD'
                        ));
                    }

                    
                    //SE VALIDA QUE EXISTA EL FOLIO_CADEM
                    if(isset($folio)){
                        $folio = array_unique($folio);
                        sort($folio);

                        $sql = "SELECT s.FOLIOCADEM as nombre, sc.ID as id FROM SALA s
                                INNER JOIN SALACLIENTE sc on s.ID = sc.SALA_ID
                                WHERE s.FOLIOCADEM IN ( ? ) and sc.CLIENTE_ID = ? and sc.MEDICION_ID = ? ";
                        $param = array($folio, $id_cliente, $id_medicion);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY, \PDO::PARAM_INT, \PDO::PARAM_INT);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

                        usort($query, array($this,"cmp"));

                        foreach ($folio as $k => $v) {
                            if(!isset($query[$k]['nombre']) || $v !== $query[$k]['nombre']){
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'LA SALA "'.$v.'" NO EXISTE EN LA BD.'
                                ));
                            }
                            $folio_[$v] = $query[$k]['id'];
                        }
                    }


                    //SE VALIDA QUE EXISTA EL ITEM
                    if(isset($item)){
                        $item = array_unique($item);
                        sort($item);

                        $sql = "SELECT i.CODIGO as nombre, ic.ID as id FROM ITEM i
                                INNER JOIN ITEMCLIENTE ic on i.ID = ic.ITEM_ID
                                WHERE i.CODIGO IN ( ? ) and ic.CLIENTE_ID = ? and ic.MEDICION_ID = ? ";
                        $param = array($item, $id_cliente, $id_medicion);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY, \PDO::PARAM_INT, \PDO::PARAM_INT);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

                        usort($query, array($this,"cmp"));

                        foreach ($item as $k => $v) {
                            if(!isset($query[$k]['nombre']) || $v !== $query[$k]['nombre']){
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'EL ITEM "'.$v.'" NO EXISTE EN LA BD.'
                                ));
                            }
                            $item_[$v] = $query[$k]['id'];
                        }
                    }
                       

                    


                    //FORMATO ES:
                    // FOLIO;EAN;FECHA;QUIEBRE;CANTIDAD;ID_SALACLIENTE;ID_ITEMCLIENTE

                    //ARCHIVO A ESCRIBIR CON LOS IDs FINALES
                    $fp = fopen($this->uploadDIR.$name.'_proc.csv', 'w');

                    foreach ($m as $fields) {
                        $id_sala = (isset($folio_[$fields[0]]))?$folio_[$fields[0]]:"NULL";
                        $id_item = (isset($item_[$fields[1]]))?$item_[$fields[1]]:"NULL";


                        $fila = array_merge($fields, array($id_sala, $id_item));
                        fputcsv($fp, $fila,";");
                    }
                    fclose($fp);

                    break;





                case 'planoprecio'://DATOS DE PLANOGRAMA PRECIO


                    //FORMATO ES:
                    // FOLIO;EAN;PRECIO;POLITICAPRECIO;FECHAHORACAPTURA

                    
                    //SI LA PRIMERA FILA TIENE LOS ENCABEZADOS SE BORRA
                    if($m[0][0] === 'FOLIO' || $m[0][1] === 'EAN' || $m[0][2] === 'PRECIO') unset($m[0]);
                    

                    //SE VALIDA QUE EL FOLIO-EAN NO ESTE EN LA BD
                    $chunk = 0;
                    foreach($m as $k => $value){
                        $folioean[floor($chunk/2000)][] = $value[0].'-'.$value[1];
                        if(strlen($value[3]) === 0) $m[$k][3] = "NULL"; //SI NO HAY POLITICAPRECIO SE PASA A NULL PARA EL INSERT
                        if(strlen($value[4]) === 0) $m[$k][4] = "NULL"; //SI NO HAY FECHAHORACAPTURA SE PASA A NULL PARA EL INSERT
                        $chunk++;
                    }

                    $folioean_encontrados = array();
                    $start_select = microtime(true);
                    foreach($folioean as $k => $chunk){
                        $sql = "SELECT s.FOLIOCADEM+'-'+i.CODIGO as folioean FROM PLANOGRAMAP p
                                INNER JOIN SALACLIENTE sc on p.SALACLIENTE_ID = sc.ID
                                INNER JOIN SALA s on s.ID = sc.SALA_ID
                                INNER JOIN ITEMCLIENTE ic on p.ITEMCLIENTE_ID = ic.ID
                                INNER JOIN ITEM i on i.ID = ic.ITEM_ID
                                WHERE s.FOLIOCADEM+'-'+i.CODIGO IN ( ? ) and sc.CLIENTE_ID = ? and sc.MEDICION_ID = ? and ic.CLIENTE_ID = ? and ic.MEDICION_ID = ?
                                ";
                        $param = array($chunk, $id_cliente, $id_medicion, $id_cliente, $id_medicion);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT, \PDO::PARAM_INT);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();
                        foreach ($query as $v) $folioean_encontrados[] = $v['folioean'];
                        set_time_limit(10);
                        $time_taken = microtime(true) - $start_select;
                        if($time_taken >= 600){
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'TIEMPO EXCEDIDO ('.round($time_taken,1).' SEG) EN CONSULTAR. EL TIEMPO MAX ES DE 600 SEG. LO QUE DEBERIA ALCANZAR PARA PROCESAR APROX 45 MIL FILAS. SI SU ARCHIVO TIENE MAS, POR FAVOR SAQUE LAS SUFICIENTES FILAS.'
                            ));
                        }
                    }
                    
                    $start_valid = microtime(true);
                    foreach ($m as $k => $fila) {
                        if(count($fila) !== 5){//SIEMPRE DEBEN HABER 5 COLUMNAS
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'NO HAY 5 COLUMNAS CERCA DE LA LINEA '.$k
                            ));
                        }
                        if(strlen($fila[0]) === 0){//EL "FOLIO" NO PUEDE SER VACIA
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'EL "FOLIO" NO PUEDE ESTAR VACIA, CERCA DE LA LINEA '.$k
                            ));
                        }
                        if(strlen($fila[1]) === 0){//EL "EAN" NO PUEDE SER VACIO
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'EL "EAN" NO PUEDE ESTAR VACIA, CERCA DE LA LINEA '.$k
                            ));
                        }
                        if(strlen($fila[2]) === 0){//EL "PRECIO" NO PUEDE SER VACIO
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'EL "PRECIO" NO PUEDE ESTAR VACIA, CERCA DE LA LINEA '.$k
                            ));
                        }
                        if($fila[2] != (string) intval($fila[2]) || intval($fila[2]) < 0){//EL "PRECIO" DEBE SER ENTERO MAYOR O IGUAL A CERO
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'EL "PRECIO" DEBE SER ENTERO MAYOR O IGUAL A CERO, CERCA DE LA LINEA '.$k
                            ));
                        }
                        if($fila[3] !== "NULL" && ($fila[2] != (string) intval($fila[2]) || intval($fila[2]) < 0) ){//EL "POLITICAPRECIO" DEBE SER ENTERO MAYOR O IGUAL A CERO
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'LA "POLITICAPRECIO" DEBE SER ENTERO MAYOR O IGUAL A CERO, CERCA DE LA LINEA '.$k
                            ));
                        }

                        if(in_array($fila[0].'-'.$fila[1], $folioean_encontrados)){//SE BUSCAN Y DESCARTA LOS CODIGOSALA ENCONTRADOS Y SE REGISTRA
                            unset($m[$k]);
                            $item_descartados++;
                        }
                        else{
                            if($fila[0] !== '') $folio[] = $fila[0];
                            if($fila[1] !== '') $item[] = $fila[1];
                        }
                        set_time_limit(10);
                        $time_taken = microtime(true) - $start_valid;
                        if($time_taken >= 600){
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'TIEMPO EXCEDIDO ('.round($time_taken,1).' SEG) EN VALIDAR. EL TIEMPO MAX ES DE 600 SEG. LO QUE DEBERIA ALCANZAR PARA PROCESAR APROX 45 MIL FILAS. SI SU ARCHIVO TIENE MAS, POR FAVOR SAQUE LAS SUFICIENTES FILAS.'
                            ));
                        }
                    }

                    if(count($m) === 0){//NO SE INGRESAN DATOS
                        return new JsonResponse(array(
                            'status' => false,
                            'mensaje' => 'VERIFIQUE QUE EL CSV TIENE DATOS Y QUE LOS SKU NO EXISTEN EN LA BD'
                        ));
                    }

                    
                    //SE VALIDA QUE EXISTA EL FOLIO_CADEM
                    if(isset($folio)){
                        $folio = array_unique($folio);
                        sort($folio);

                        $sql = "SELECT s.FOLIOCADEM as nombre, sc.ID as id FROM SALA s
                                INNER JOIN SALACLIENTE sc on s.ID = sc.SALA_ID
                                WHERE s.FOLIOCADEM IN ( ? ) and sc.CLIENTE_ID = ? and sc.MEDICION_ID = ? ";
                        $param = array($folio, $id_cliente, $id_medicion);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY, \PDO::PARAM_INT, \PDO::PARAM_INT);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

                        usort($query, array($this,"cmp"));

                        foreach ($folio as $k => $v) {
                            if(!isset($query[$k]['nombre']) || $v !== $query[$k]['nombre']){
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'LA SALA "'.$v.'" NO EXISTE EN LA BD.'
                                ));
                            }
                            $folio_[$v] = $query[$k]['id'];
                        }
                    }


                    //SE VALIDA QUE EXISTA EL ITEM
                    if(isset($item)){
                        $item = array_unique($item);
                        sort($item);

                        $sql = "SELECT i.CODIGO as nombre, ic.ID as id FROM ITEM i
                                INNER JOIN ITEMCLIENTE ic on i.ID = ic.ITEM_ID
                                WHERE i.CODIGO IN ( ? ) and ic.CLIENTE_ID = ? and ic.MEDICION_ID = ? ";
                        $param = array($item, $id_cliente, $id_medicion);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY, \PDO::PARAM_INT, \PDO::PARAM_INT);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

                        usort($query, array($this,"cmp"));

                        foreach ($item as $k => $v) {
                            if(!isset($query[$k]['nombre']) || $v !== $query[$k]['nombre']){
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'EL ITEM "'.$v.'" NO EXISTE EN LA BD.'
                                ));
                            }
                            $item_[$v] = $query[$k]['id'];
                        }
                    }
                       

                    


                    //FORMATO ES:
                    // FOLIO;EAN;PRECIO;POLITICAPRECIO;ID_SALACLIENTE;ID_ITEMCLIENTE

                    //ARCHIVO A ESCRIBIR CON LOS IDs FINALES
                    $fp = fopen($this->uploadDIR.$name.'_proc.csv', 'w');

                    foreach ($m as $fields) {
                        $id_sala = (isset($folio_[$fields[0]]))?$folio_[$fields[0]]:"NULL";
                        $id_item = (isset($item_[$fields[1]]))?$item_[$fields[1]]:"NULL";


                        $fila = array_merge($fields, array($id_sala, $id_item));
                        fputcsv($fp, $fila,";");
                    }
                    fclose($fp);

                    break;












            }
                    
                

            $time_taken = microtime(true) - $start;


            //DATOS ADICIONALES
            $dat = array();
            if(in_array($tipo_carga, array('itemcliente', 'salacliente', 'planoquiebre', 'planoprecio'))){
                $dat['id_cliente'] = $id_cliente;
                $dat['id_medicion'] = $id_medicion;
            }

            return new JsonResponse(array_merge(array(
                'status' => true,
                'name' => $name.'_proc.csv',
                'tipo_carga' => $tipo_carga,
                'time_taken' => $time_taken*1000
            ),$dat));

    	}else{
    		return new JsonResponse(array(
    			'status' => false, 
    			'mensaje' => 'EL ARCHIVO NO SE PUEDE LEER O NO TIENE EXTENSION CSV'
    			));
    	}
    	
    }

    public function fileprocessAction(Request $request)
    {
        $start = microtime(true);
        $em = $this->getDoctrine()->getManager();
        $data = $request->query->all();
        $name = $data['name'];
        $tipo_carga = $data['tipo_carga'];
        if(in_array($tipo_carga, array('itemcliente', 'salacliente', 'planoquiebre', 'planoprecio'))){
            $id_cliente = intval($data['id_cliente']);
            $id_medicion = intval($data['id_medicion']);
        }
        $file = new File($this->uploadDIR.$name);
        if($file->isReadable() && strcasecmp($file->getExtension(),'csv') === 0){
            //LEER Y PROCESAR
            $fileobj = $file->openFile('r');

            while (!$fileobj->eof()) {
                $row = $fileobj->fgetcsv(';');
                // $row = array_map("utf8_encode", $row);//SE PASA DE ANSI A UTF-8
                if(isset($row[4])){
                    $m[] = $row;
                }
            }

            $row_affected = 0;
            $conn = $em->getConnection();

            switch ($tipo_carga) {
                case 'item'://DATOS DE ITEM

                    //FORMATO ES: TIPOCODIGO_ID;FABRICANTE;MARCA;NOMBRE;CODIGO;ID_TIPOCODIGO;ID_FABRICANTE;ID_MARCA
                    //SE CARGA EN LA BD, USANDO TRANSACCIONES
                    
                    $conn->beginTransaction();
                    

                    //OBTENEMOS EL ULTIMO ID INGRESADO
                    $sql = "SELECT TOP(1) i.ID as id FROM ITEM i
                            ORDER BY i.ID DESC";
                    $query = $em->getConnection()->executeQuery($sql)->fetchAll();
                    $id = (isset($query[0]))?intval($query[0]['id']):0;

                    try{
                        $start_insert = microtime(true);
                        foreach ($m as $key => $fila) {
                            $id++;
                            $sql = "INSERT INTO ITEM
                                   ([ID]
                                   ,[TIPOCODIGO_ID]
                                   ,[FABRICANTE_ID]
                                   ,[MARCA_ID]
                                   ,[NOMBRE]
                                   ,[CODIGO]
                                   ,[ACTIVO])
                             VALUES
                                   ( ? , ? , ? , ? , ? , ? , 1 )";
                            $param = array($id , $fila[5] , $fila[6] , $fila[7] , $fila[3] , $fila[4]);
                            $tipo_param = array(
                                \PDO::PARAM_INT,
                                ($fila[5] === "NULL")?\PDO::PARAM_NULL:\PDO::PARAM_INT,
                                ($fila[6] === "NULL")?\PDO::PARAM_NULL:\PDO::PARAM_INT,
                                ($fila[7] === "NULL")?\PDO::PARAM_NULL:\PDO::PARAM_INT,
                                \PDO::PARAM_STR,
                                \PDO::PARAM_STR
                                );
                            $row_affected += $conn->executeUpdate($sql,$param,$tipo_param);
                            set_time_limit(10);
                            $time_taken = microtime(true) - $start_insert;
                            if($time_taken >= 600){
                                $conn->rollback();
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'TIEMPO EXCEDIDO ('.round($time_taken,1).' SEG) EN INSERT. EL TIEMPO MAX ES DE 600 SEG. LO QUE DEBERIA ALCANZAR PARA PROCESAR APROX 45 MIL FILAS. SI SU ARCHIVO TIENE MAS, POR FAVOR SAQUE LAS SUFICIENTES FILAS.'
                                ));
                            }
                        }

                        $conn->commit();
                    } catch(Exception $e) {
                        $conn->rollback();
                        return new JsonResponse(array(
                            'status' => false, 
                            'mensaje' => 'ERROR EN EL INSERT DE DATOS. NO SE INGRESO NADA'
                        ));
                    }

                    break;
                case 'sala'://DATOS DE LA SALA



                    //FORMATO ES: COMUNA;CANAL;CADENA;FORMATO;FOLIO;CALLE;NUMERO;ID_COMUNA;ID_CANAL;ID_CADENA;ID_FORMATO
                    //SE CARGA EN LA BD, USANDO TRANSACCIONES
                    
                    $conn->beginTransaction();
                    

                    //OBTENEMOS EL ULTIMO ID INGRESADO
                    $sql = "SELECT TOP(1) s.ID as id FROM SALA s
                            ORDER BY s.ID DESC";
                    $query = $em->getConnection()->executeQuery($sql)->fetchAll();
                    $id = (isset($query[0]))?intval($query[0]['id']):0;

                    try{

                        foreach ($m as $key => $fila) {
                            $id++;
                            $sql = "INSERT INTO SALA
                                   ([ID]
                                   ,[COMUNA_ID]
                                   ,[CANAL_ID]
                                   ,[CADENA_ID]
                                   ,[FORMATO_ID]
                                   ,[FOLIOCADEM]
                                   ,[CALLE]
                                   ,[NUMEROCALLE]
                                   ,[LATITUD]
                                   ,[LONGITUD]
                                   ,[RESPUESTA_GMAP]
                                   ,[TIPO_GMAP]
                                   ,[ACTIVO])
                             VALUES
                                   (?
                                   ,?
                                   ,?
                                   ,?
                                   ,?
                                   ,?
                                   ,?
                                   ,?
                                   ,NULL
                                   ,NULL
                                   ,NULL
                                   ,NULL
                                   ,1 )";
                            $param = array($id , $fila[7] , $fila[8] , $fila[9] , $fila[10] , $fila[4], $fila[5], $fila[6]);
                            $tipo_param = array(
                                \PDO::PARAM_INT,
                                ($fila[7] === "NULL")?\PDO::PARAM_NULL:\PDO::PARAM_INT,
                                ($fila[8] === "NULL")?\PDO::PARAM_NULL:\PDO::PARAM_INT,
                                ($fila[9] === "NULL")?\PDO::PARAM_NULL:\PDO::PARAM_INT,
                                ($fila[10] === "NULL")?\PDO::PARAM_NULL:\PDO::PARAM_INT,
                                \PDO::PARAM_STR,
                                \PDO::PARAM_STR,
                                \PDO::PARAM_STR
                                );
                            $row_affected += $conn->executeUpdate($sql,$param,$tipo_param);
                        }

                        $conn->commit();
                    } catch(Exception $e) {
                        $conn->rollback();
                        return new JsonResponse(array(
                            'status' => false, 
                            'mensaje' => 'ERROR EN EL INSERT DE DATOS. NO SE INGRESO NADA'
                        ));
                    }



                    break;


                case 'itemcliente'://DATOS DE ITEMCLIENTE

                    //FORMATO ES:
                    // 0->NOMBRE NIVELITEM_ID1;
                    // 1->NIVELITEM_ID1;
                    // 2->NOMBRE NIVELITEM_ID2;
                    // 3->NIVELITEM_ID2;
                    // 4->NOMBRE NIVELITEM_ID3;
                    // 5->NIVELITEM_ID3;
                    // 6->NOMBRE NIVELITEM_ID4;
                    // 7->NIVELITEM_ID4;
                    // 8->NOMBRE NIVELITEM_ID5;
                    // 9->NIVELITEM_ID5;
                    // 10->EAN_ITEM
                    // 11->EAN_PADRE
                    // 12->TIPOCODIGO_ID;
                    // 13->CODIGOITEM1;
                    // 14->CODIGOITEM2

                    // 15->ID_NIVELITEM1
                    // 16->ID_NIVELITEM2
                    // 17->ID_NIVELITEM3
                    // 18->ID_NIVELITEM4
                    // 19->ID_NIVELITEM5
                    // 20->ID_ITEM
                    // 21->ID_ITEM2
                    // 22->ID_TIPOCODIGO

                    //SE CARGA EN LA BD, USANDO TRANSACCIONES
                    
                    $conn->beginTransaction();
                    

                    //OBTENEMOS EL ULTIMO ID INGRESADO
                    $sql = "SELECT TOP(1) i.ID as id FROM ITEMCLIENTE i
                            ORDER BY i.ID DESC";
                    $query = $em->getConnection()->executeQuery($sql)->fetchAll();
                    $id = (isset($query[0]))?intval($query[0]['id']):0;

                    try{
                        $start_insert = microtime(true);
                        foreach ($m as $key => $fila) {
                            $id++;
                            $sql = "INSERT INTO ITEMCLIENTE
                                   ([ID]
                                   ,[NIVELITEM_ID]
                                   ,[CLIENTE_ID]
                                   ,[MEDICION_ID]
                                   ,[NIVELITEM_ID2]
                                   ,[NIVELITEM_ID3]
                                   ,[NIVELITEM_ID4]
                                   ,[ITEM_ID]
                                   ,[NIVELITEM_ID5]
                                   ,[ITEM_ID2]
                                   ,[TIPOCODIGO_ID]
                                   ,[CODIGOITEM1]
                                   ,[CODIGOITEM2]
                                   ,[ACTIVO])
                             VALUES
                                   (?
                                   ,?
                                   ,?
                                   ,?
                                   ,?
                                   ,?
                                   ,?
                                   ,?
                                   ,?
                                   ,?
                                   ,?
                                   ,?
                                   ,?
                                   ,1)";
                            $param = array(
                                $id,
                                $fila[15],
                                $id_cliente,
                                $id_medicion,
                                $fila[16],
                                $fila[17],
                                $fila[18],
                                $fila[20],
                                $fila[19],
                                $fila[21],
                                $fila[22],
                                $fila[13],
                                $fila[14],
                                );
                            $tipo_param = array(
                                \PDO::PARAM_INT,
                                ($fila[15] === "NULL")?\PDO::PARAM_NULL:\PDO::PARAM_INT,
                                \PDO::PARAM_INT,
                                \PDO::PARAM_INT,
                                ($fila[16] === "NULL")?\PDO::PARAM_NULL:\PDO::PARAM_INT,
                                ($fila[17] === "NULL")?\PDO::PARAM_NULL:\PDO::PARAM_INT,
                                ($fila[18] === "NULL")?\PDO::PARAM_NULL:\PDO::PARAM_INT,
                                \PDO::PARAM_INT,
                                ($fila[19] === "NULL")?\PDO::PARAM_NULL:\PDO::PARAM_INT,
                                ($fila[21] === "NULL")?\PDO::PARAM_NULL:\PDO::PARAM_INT,
                                \PDO::PARAM_INT,
                                ($fila[13] === "NULL")?\PDO::PARAM_NULL:\PDO::PARAM_STR,
                                ($fila[14] === "NULL")?\PDO::PARAM_NULL:\PDO::PARAM_STR,
                                );
                            $row_affected += $conn->executeUpdate($sql,$param,$tipo_param);
                            set_time_limit(10);
                            $time_taken = microtime(true) - $start_insert;
                            if($time_taken >= 600){
                                $conn->rollback();
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'TIEMPO EXCEDIDO ('.round($time_taken,1).' SEG) EN INSERT. EL TIEMPO MAX ES DE 600 SEG. LO QUE DEBERIA ALCANZAR PARA PROCESAR APROX 45 MIL FILAS. SI SU ARCHIVO TIENE MAS, POR FAVOR SAQUE LAS SUFICIENTES FILAS.'
                                ));
                            }
                        }

                        $conn->commit();
                    } catch(Exception $e) {
                        $conn->rollback();
                        return new JsonResponse(array(
                            'status' => false, 
                            'mensaje' => 'ERROR EN EL INSERT DE DATOS. NO SE INGRESO NADA'
                        ));
                    }

                    break;






                case 'salacliente'://DATOS DE SALACLIENTE

                    //FORMATO ES:
                    //FOLIO_CADEM;CODIGOSALA;RESPONSABLE;ID_EMPLEADO;ID_SALA

                    //SE CARGA EN LA BD, USANDO TRANSACCIONES
                    
                    $conn->beginTransaction();
                    

                    //OBTENEMOS EL ULTIMO ID INGRESADO
                    $sql = "SELECT TOP(1) i.ID as id FROM SALACLIENTE i
                            ORDER BY i.ID DESC";
                    $query = $em->getConnection()->executeQuery($sql)->fetchAll();
                    $id = (isset($query[0]))?intval($query[0]['id']):0;

                    try{
                        $start_insert = microtime(true);
                        foreach ($m as $key => $fila) {
                            $id++;
                            $sql = "INSERT INTO SALACLIENTE
                                   ([ID]
                                   ,[MEDICION_ID]
                                   ,[CLIENTE_ID]
                                   ,[EMPLEADO_ID]
                                   ,[SALA_ID]
                                   ,[CODIGOSALA]
                                   ,[ACTIVO])
                             VALUES
                                   (?
                                   ,?
                                   ,?
                                   ,?
                                   ,?
                                   ,?
                                   ,1)";
                            $param = array(
                                $id,
                                $id_medicion,
                                $id_cliente,
                                $fila[3],
                                $fila[4],
                                $fila[1],
                                );
                            $tipo_param = array(
                                \PDO::PARAM_INT,
                                \PDO::PARAM_INT,
                                \PDO::PARAM_INT,
                                \PDO::PARAM_INT,
                                \PDO::PARAM_INT,
                                ($fila[1] === "NULL")?\PDO::PARAM_NULL:\PDO::PARAM_STR,
                                );
                            $row_affected += $conn->executeUpdate($sql,$param,$tipo_param);
                            set_time_limit(10);
                            $time_taken = microtime(true) - $start_insert;
                            if($time_taken >= 600){
                                $conn->rollback();
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'TIEMPO EXCEDIDO ('.round($time_taken,1).' SEG) EN INSERT. EL TIEMPO MAX ES DE 600 SEG. LO QUE DEBERIA ALCANZAR PARA PROCESAR APROX 45 MIL FILAS. SI SU ARCHIVO TIENE MAS, POR FAVOR SAQUE LAS SUFICIENTES FILAS.'
                                ));
                            }
                        }

                        $conn->commit();
                    } catch(Exception $e) {
                        $conn->rollback();
                        return new JsonResponse(array(
                            'status' => false, 
                            'mensaje' => 'ERROR EN EL INSERT DE DATOS. NO SE INGRESO NADA'
                        ));
                    }

                    break;




                case 'planoquiebre'://DATOS DE PLANOGRAMA QUIEBRE

                    //FORMATO ES:
                    // FOLIO;EAN;FECHA;QUIEBRE;CANTIDAD;ID_SALACLIENTE;ID_ITEMCLIENTE

                    //SE CARGA EN LA BD, USANDO TRANSACCIONES
                    
                    $conn->beginTransaction();
                    

                    //OBTENEMOS EL ULTIMO ID INGRESADO
                    $sql = "SELECT TOP(1) i.ID as id FROM PLANOGRAMAQ i
                            ORDER BY i.ID DESC";
                    $query = $em->getConnection()->executeQuery($sql)->fetchAll();
                    $id = (isset($query[0]))?intval($query[0]['id']):0;

                    //OBTENEMOS EL ULTIMO ID INGRESADO
                    $sql = "SELECT TOP(1) i.ID as id FROM QUIEBRE i
                            ORDER BY i.ID DESC";
                    $query = $em->getConnection()->executeQuery($sql)->fetchAll();
                    $id_q = (isset($query[0]))?intval($query[0]['id']):0;

                    try{
                        $start_insert = microtime(true);
                        foreach ($m as $key => $fila) {
                            $id++;
                            $sql = "INSERT INTO PLANOGRAMAQ
                                   ([ID]
                                   ,[SALACLIENTE_ID]
                                   ,[MEDICION_ID]
                                   ,[ITEMCLIENTE_ID]
                                   ,[ACTIVO])
                             VALUES
                                   (?
                                   ,?
                                   ,?
                                   ,?
                                   ,1 )";
                            $param = array(
                                $id,
                                $fila[5],
                                $id_medicion,
                                $fila[6],
                                );
                            $tipo_param = array(
                                \PDO::PARAM_INT,
                                \PDO::PARAM_INT,
                                \PDO::PARAM_INT,
                                \PDO::PARAM_INT,
                                );
                            $row_affected += $conn->executeUpdate($sql,$param,$tipo_param);


                            //INSERTAMOS QUIEBRE
                            $id_q++;

                            $sql = "INSERT INTO QUIEBRE
                                   ([ID]
                                   ,[PLANOGRAMAQ_ID]
                                   ,[HAYQUIEBRE]
                                   ,[CANTIDAD]
                                   ,[FECHAHORACAPTURA]
                                   ,[ACTIVO])
                             VALUES
                                   (?
                                   ,?
                                   ,?
                                   ,?
                                   ,?
                                   ,1 )";
                            $param = array(
                                $id_q,
                                $id,
                                $fila[3],
                                $fila[4],
                                $fila[2],
                                );
                            $tipo_param = array(
                                \PDO::PARAM_INT,
                                \PDO::PARAM_INT,
                                \PDO::PARAM_BOOL,
                                ($fila[4] === "NULL")?\PDO::PARAM_NULL:\PDO::PARAM_INT,
                                ($fila[2] === "NULL")?\PDO::PARAM_NULL:\PDO::PARAM_STR,
                                );

                            $row_affected += $conn->executeUpdate($sql,$param,$tipo_param);


                            set_time_limit(10);
                            $time_taken = microtime(true) - $start_insert;
                            if($time_taken >= 600){
                                $conn->rollback();
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'TIEMPO EXCEDIDO ('.round($time_taken,1).' SEG) EN INSERT. EL TIEMPO MAX ES DE 600 SEG. LO QUE DEBERIA ALCANZAR PARA PROCESAR APROX 45 MIL FILAS. SI SU ARCHIVO TIENE MAS, POR FAVOR SAQUE LAS SUFICIENTES FILAS.'
                                ));
                            }
                        }

                        $conn->commit();
                    } catch(Exception $e) {
                        $conn->rollback();
                        return new JsonResponse(array(
                            'status' => false, 
                            'mensaje' => 'ERROR EN EL INSERT DE DATOS. NO SE INGRESO NADA'
                        ));
                    }

                    break;







                case 'planoprecio'://DATOS DE PLANOGRAMA PRECIO

                    //FORMATO ES:
                    // FOLIO;EAN;PRECIO;POLITICAPRECIO;FECHAHORACAPTURA;ID_SALACLIENTE;ID_ITEMCLIENTE

                    //SE CARGA EN LA BD, USANDO TRANSACCIONES
                    
                    $conn->beginTransaction();
                    

                    //OBTENEMOS EL ULTIMO ID INGRESADO
                    $sql = "SELECT TOP(1) i.ID as id FROM PLANOGRAMAP i
                            ORDER BY i.ID DESC";
                    $query = $em->getConnection()->executeQuery($sql)->fetchAll();
                    $id = (isset($query[0]))?intval($query[0]['id']):0;

                    //OBTENEMOS EL ULTIMO ID INGRESADO
                    $sql = "SELECT TOP(1) i.ID as id FROM PRECIO i
                            ORDER BY i.ID DESC";
                    $query = $em->getConnection()->executeQuery($sql)->fetchAll();
                    $id_p = (isset($query[0]))?intval($query[0]['id']):0;

                    try{
                        $start_insert = microtime(true);
                        foreach ($m as $key => $fila) {
                            $id++;
                            $sql = "INSERT INTO PLANOGRAMAP
                                   ([ID]
                                   ,[SALACLIENTE_ID]
                                   ,[MEDICION_ID]
                                   ,[ITEMCLIENTE_ID]
                                   ,[POLITICAPRECIO]
                                   ,[ACTIVO])
                             VALUES
                                   (?
                                   ,?
                                   ,?
                                   ,?
                                   ,?
                                   ,1 )";
                            $param = array(
                                $id,
                                $fila[5],
                                $id_medicion,
                                $fila[6],
                                $fila[3],
                                );
                            $tipo_param = array(
                                \PDO::PARAM_INT,
                                \PDO::PARAM_INT,
                                \PDO::PARAM_INT,
                                \PDO::PARAM_INT,
                                ($fila[3] === "NULL")?\PDO::PARAM_NULL:\PDO::PARAM_INT,
                                );
                            $row_affected += $conn->executeUpdate($sql,$param,$tipo_param);


                            //INSERTAMOS PRECIO
                            $id_p++;

                            $sql = "INSERT INTO PRECIO
                                   ([ID]
                                   ,[PLANOGRAMAP_ID]
                                   ,[PRECIO]
                                   ,[FECHAHORACAPTURA]
                                   ,[ACTIVO])
                             VALUES
                                   (?
                                   ,?
                                   ,?
                                   ,1 )";
                            $param = array(
                                $id_p,
                                $id,
                                $fila[2],
                                $fila[4],
                                );
                            $tipo_param = array(
                                \PDO::PARAM_INT,
                                \PDO::PARAM_INT,
                                \PDO::PARAM_INT,
                                ($fila[4] === "NULL")?\PDO::PARAM_NULL:\PDO::PARAM_STR,
                                );

                            $row_affected += $conn->executeUpdate($sql,$param,$tipo_param);


                            set_time_limit(10);
                            $time_taken = microtime(true) - $start_insert;
                            if($time_taken >= 600){
                                $conn->rollback();
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'TIEMPO EXCEDIDO ('.round($time_taken,1).' SEG) EN INSERT. EL TIEMPO MAX ES DE 600 SEG. LO QUE DEBERIA ALCANZAR PARA PROCESAR APROX 45 MIL FILAS. SI SU ARCHIVO TIENE MAS, POR FAVOR SAQUE LAS SUFICIENTES FILAS.'
                                ));
                            }
                        }

                        $conn->commit();
                    } catch(Exception $e) {
                        $conn->rollback();
                        return new JsonResponse(array(
                            'status' => false, 
                            'mensaje' => 'ERROR EN EL INSERT DE DATOS. NO SE INGRESO NADA'
                        ));
                    }

                    break;









            }
            
            $time_taken = microtime(true) - $start;

            return new JsonResponse(array(
                'status' => true,
                'row_affected' => $row_affected,
                'time_taken' => $time_taken*1000
            ));
        }else{
            return new JsonResponse(array(
                'status' => false, 
                'mensaje' => 'EL ARCHIVO NO SE PUEDE LEER O NO TIENE EXTENSION CSV'
                ));
        }
    }

    private function cmp($a, $b)
    {
        if ($a['nombre'] === $b['nombre']) {
            return 0;
        }
        return ($a['nombre'] < $b['nombre']) ? -1 : 1;
    }
}
