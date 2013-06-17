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
        $sql = "SELECT c.ID as idc, m.ID as idm, c.NOMBREFANTASIA as nombre, m.NOMBRE as medicion FROM CLIENTE c
                INNER JOIN ESTUDIO e on e.CLIENTE_ID = c.ID
                INNER JOIN MEDICION m on m.ESTUDIO_ID = e.ID
                ORDER BY c.NOMBREFANTASIA, m.NOMBRE";
        $query = $em->getConnection()->executeQuery($sql)->fetchAll();
        $choices_medicion = array();
        foreach($query as $r)
        {
            $choices_medicion[$r['idc'].'-'.$r['idm']] = strtoupper($r['nombre'].'-'.$r['medicion']);
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

    public function fileuploadAction(Request $request)
    {
        $uf = $request->files->get('file1');
    	$tipo_carga = $request->request->get('tipo_carga');
    	if (null === $uf || !isset($tipo_carga)) return new JsonResponse(array('status' => false));//ERROR
        $datos_itemcliente = array();
        if($tipo_carga === 'itemcliente'){
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
    	$em = $this->getDoctrine()->getManager();
        $data = $request->query->all();
        $tipo_carga = $data['tipo_carga'];
    	$name = $data['name'];
        if($tipo_carga === 'itemcliente'){
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
                if(isset($row[4])){
                    $m[] = $row;
                }
			}


            switch ($tipo_carga) {
                case 'item'://DATOS DE ITEM


                    $fabricante = array();
                    $marca = array();
                    foreach ($m as $value) $cod_item[] = $value[4];
                    //FORMATO ES: TIPOCODIGO;FABRICANTE;MARCA;NOMBRE;CODIGO
                    //SI LA PRIMERA FILA TIENE LOS ENCABEZADOS SE BORRA
                    if($m[0][0] === 'TIPOCODIGO' || $m[0][4] === 'CODIGO') unset($m[0]);
                    

                    //SE VERIFICA QUE TODOS LOS SKU TENGAN 13 DIG, ADEMAS SE VALIDA QUE EL SKU NO ESTE EN LA BD
                    $sql = "SELECT i.codigo as codigo FROM ITEM i
                            WHERE i.codigo IN ( ? )
                            ORDER BY i.codigo";
                    $param = array($cod_item);
                    $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
                    $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();
                    $cod_encontrados = array();
                    foreach ($query as $v) $cod_encontrados[] = $v['codigo'];

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
                    }

                    if(count($m) === 0){//NO SE INGRESAN DATOS
                        return new JsonResponse(array(
                            'status' => false,
                            'mensaje' => 'VERIFIQUE QUE EL CSV TIENE DATOS Y QUE LOS SKU NO EXISTEN EN LA BD'
                        ));
                    }

                    //SE VALIDA QUE EXISTA TIPOCODIGO
                    if(count($tipo_codigo) > 0){
                        $tipo_codigo = array_unique($tipo_codigo);
                        sort($tipo_codigo);

                        $sql = "SELECT tc.NOMBRE as nombre, tc.ID as id FROM TIPOCODIGO tc
                                WHERE tc.NOMBRE IN ( ? )";
                        $param = array($tipo_codigo);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

                        usort($query, array($this,"cmp"));

                        foreach ($tipo_codigo as $k => $v) {
                            if($v !== $query[$k]['nombre']){
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'EL TIPO CODIGO "'.$v.'" NO EXISTE EN LA BD.'
                                ));
                            }
                            $tipo_codigo_[$v] = $query[$k]['id'];
                        }
                    }
                    

                    //SE VALIDA QUE EXISTA FABRICANTE
                    if(count($fabricante) > 0){
                         $fabricante = array_unique($fabricante);
                        sort($fabricante);

                        $sql = "SELECT f.NOMBRE as nombre, f.ID as id FROM FABRICANTE f
                                WHERE f.NOMBRE IN ( ? )";
                        $param = array($fabricante);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

                        usort($query, array($this,"cmp"));

                        foreach ($fabricante as $k => $v) {
                            if($v !== $query[$k]['nombre']){
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'EL FABRICANTE "'.$v.'" NO EXISTE EN LA BD.'
                                ));
                            }
                            $fabricante_[$v] = $query[$k]['id'];
                        }
                    }
                       

                    //SE VALIDA QUE EXITA MARCA
                    if(count($marca) > 0){
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
                            if($v !== $query[$k]['nombre']){
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'LA MARCA "'.$v.'" NO EXISTE EN LA BD.'
                                ));
                            }
                            $marca_[$v] = $query[$k]['id'];
                        }
                    }


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
                    if(count($comuna) > 0){
                        $comuna = array_unique($comuna);
                        sort($comuna);

                        $sql = "SELECT c.NOMBRE as nombre, c.ID as id FROM COMUNA c
                                WHERE c.NOMBRE IN ( ? )";
                        $param = array($comuna);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

                        usort($query, array($this,"cmp"));

                        foreach ($comuna as $k => $v) {
                            if($v !== $query[$k]['nombre']){
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'LA COMUNA "'.$v.'" NO EXISTE EN LA BD.'
                                ));
                            }
                            $comuna_[$v] = $query[$k]['id'];
                        }
                    }
                    

                    //SE VALIDA QUE EXISTA CANAL
                    if(count($canal) > 0){
                         $canal = array_unique($canal);
                        sort($canal);

                        $sql = "SELECT c.NOMBRE as nombre, c.ID as id FROM CANAL c
                                WHERE c.NOMBRE IN ( ? )";
                        $param = array($canal);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

                        usort($query, array($this,"cmp"));

                        foreach ($canal as $k => $v) {
                            if($v !== $query[$k]['nombre']){
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'EL CANAL "'.$v.'" NO EXISTE EN LA BD.'
                                ));
                            }
                            $canal_[$v] = $query[$k]['id'];
                        }
                    }
                       

                    //SE VALIDA QUE EXISTA CADENA
                    if(count($cadena) > 0){
                        $cadena = array_unique($cadena);
                        sort($cadena);

                        $sql = "SELECT c.NOMBRE as nombre, c.ID as id FROM CADENA c
                                WHERE c.NOMBRE IN ( ? )";
                        $param = array($cadena);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

                        usort($query, array($this,"cmp"));

                        foreach ($cadena as $k => $v) {
                            if($v !== $query[$k]['nombre']){
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'LA CADENA "'.$v.'" NO EXISTE EN LA BD.'
                                ));
                            }
                            $cadena_[$v] = $query[$k]['id'];
                        }
                    }


                    //SE VALIDA QUE EXISTA FORMATO
                    if(count($formato) > 0){
                        $formato = array_unique($formato);
                        sort($formato);

                        $sql = "SELECT f.NOMBRE as nombre, f.ID as id FROM FORMATO f
                                WHERE f.NOMBRE IN ( ? )";
                        $param = array($formato);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

                        usort($query, array($this,"cmp"));

                        foreach ($formato as $k => $v) {
                            if($v !== $query[$k]['nombre']){
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
                    // 10->ITEM_PADRE;
                    // 11->TIPOCODIGO_ID;
                    // 12->CODIGOITEM1;
                    // 13->CODIGOITEM2

                    
                    //SI LA PRIMERA FILA TIENE LOS ENCABEZADOS SE BORRA
                    if($m[0][0] === 'NOMBRE NIVELITEM_ID1' || $m[0][1] === 'NIVELITEM_ID1' || $m[0][12] === 'CODIGOITEM1') unset($m[0]);
                    

                    //SE VALIDA QUE EL ITEM NO ESTE EN LA BD
                    foreach ($m as $value) if(strlen($value[12]) !== 0) $item[] = $value[12];

                    $sql = "SELECT ic.CODIGOITEM1 as codigo FROM ITEMCLIENTE ic
                            WHERE ic.CODIGOITEM1 IN ( ? ) and ic.CLIENTE_ID = ? and ic.MEDICION_ID = ?
                            ORDER BY ic.CODIGOITEM1";
                    $param = array($item, $id_cliente, $id_medicion);
                    $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY, \PDO::PARAM_INT, \PDO::PARAM_INT);
                    $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();
                    $item_encontrados = array();
                    foreach ($query as $v) $item_encontrados[] = $v['codigo'];

                    foreach ($m as $k => $fila) {
                        if(count($fila) !== 14){//SIEMPRE DEBEN HABER 14 COLUMNAS
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'NO HAY 14 COLUMNAS CERCA DE LA LINEA '.$k
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
                        if(strlen($fila[12]) === 0){//EL CODIGOITEM1 NO PUEDE SER VACIO
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'EL "CODIGOITEM1" NO PUEDE ESTAR VACIO, CERCA DE LA LINEA '.$k
                            ));
                        }
                        if(in_array($fila[12], $item_encontrados)){//SE BUSCAN Y DESCARTA LOS CODIGOITEM1 ENCONTRADOS Y SE REGISTRA
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
                            if($fila[10] !== '') $item_padre[] = $fila[10];
                            if($fila[11] !== '') $tipo_codigo[] = $fila[11];
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
                                WHERE cni.NOMBRE + '-' + ni.NOMBRE IN ( ? )";
                        $param = array($nni_ni1);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

                        usort($query, array($this,"cmp"));

                        foreach ($nni_ni1 as $k => $v) {
                            if($v !== $query[$k]['nombre']){
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'LA CLASE "'.$v.'" NO EXISTE EN LA BD.'
                                ));
                            }
                            $nni_ni1_[$v] = $query[$k]['id'];
                        }
                    }

                    return new Response(print_r($nni_ni1_));


                    //SE VALIDA QUE EXISTA "NOMBRE NIVELITEM_ID2" y "NIVELITEM_ID2"
                    if(isset($nni2) && count($nni2) > 0 && isset($ni2) && count($ni2) > 0){
                        foreach($ni2 as $k => $v) $nni_ni2[$k] = $nni2[$k].'-'.$v;
                        $nni_ni2 = array_unique($nni_ni2);
                        sort($nni_ni2);

                        $sql = "SELECT cni.NOMBRE + '-' + ni.NOMBRE as nombre, ni.ID as id FROM NIVELITEM ni
                                INNER JOIN CLASNIVELITEM cni on cni.ID = ni.CLASNIVELITEM_ID
                                WHERE cni.NOMBRE + '-' + ni.NOMBRE IN ( ? )";
                        $param = array($nni_ni2);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

                        usort($query, array($this,"cmp"));

                        foreach ($nni_ni2 as $k => $v) {
                            if($v !== $query[$k]['nombre']){
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'LA CLASE "'.$v.'" NO EXISTE EN LA BD.'
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
                                WHERE cni.NOMBRE + '-' + ni.NOMBRE IN ( ? )";
                        $param = array($nni_ni3);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

                        usort($query, array($this,"cmp"));

                        foreach ($nni_ni3 as $k => $v) {
                            if($v !== $query[$k]['nombre']){
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'LA CLASE "'.$v.'" NO EXISTE EN LA BD.'
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
                                WHERE cni.NOMBRE + '-' + ni.NOMBRE IN ( ? )";
                        $param = array($nni_ni4);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

                        usort($query, array($this,"cmp"));

                        foreach ($nni_ni4 as $k => $v) {
                            if($v !== $query[$k]['nombre']){
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'LA CLASE "'.$v.'" NO EXISTE EN LA BD.'
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
                                WHERE cni.NOMBRE + '-' + ni.NOMBRE IN ( ? )";
                        $param = array($nni_ni5);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

                        usort($query, array($this,"cmp"));

                        foreach ($nni_ni5 as $k => $v) {
                            if($v !== $query[$k]['nombre']){
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'LA CLASE "'.$v.'" NO EXISTE EN LA BD.'
                                ));
                            }
                            $nni_ni5_[$v] = $query[$k]['id'];
                        }
                    }
                       

                    //SE VALIDA QUE EXISTA EL ITEM
                    if(count($item) > 0){
                        $item = array_unique($item);
                        sort($item);

                        $sql = "SELECT i.NOMBRE as nombre, i.ID as id FROM ITEM i
                                WHERE i.NOMBRE IN ( ? )";
                        $param = array($item);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

                        usort($query, array($this,"cmp"));

                        foreach ($item as $k => $v) {
                            if($v !== $query[$k]['nombre']){
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'EL ITEM "'.$v.'" NO EXISTE EN LA BD.'
                                ));
                            }
                            $item_[$v] = $query[$k]['id'];
                        }
                    }

                    //SE VALIDA QUE EXISTA EL ITEMPADRE
                    if(count($item_padre) > 0){
                        $item_padre = array_unique($item_padre);
                        sort($item_padre);

                        $sql = "SELECT i.NOMBRE as nombre, i.ID as id FROM ITEM i
                                WHERE i.NOMBRE IN ( ? )";
                        $param = array($item_padre);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

                        usort($query, array($this,"cmp"));

                        foreach ($item_padre as $k => $v) {
                            if($v !== $query[$k]['nombre']){
                                return new JsonResponse(array(
                                    'status' => false,
                                    'mensaje' => 'EL ITEM PADRE "'.$v.'" NO EXISTE EN LA BD.'
                                ));
                            }
                            $item_padre_[$v] = $query[$k]['id'];
                        }
                    }


                    //SE VALIDA QUE EXISTA EL TIPO DE CODIGO
                    if(count($tipo_codigo) > 0){
                        $tipo_codigo = array_unique($tipo_codigo);
                        sort($tipo_codigo);

                        $sql = "SELECT tc.NOMBRE as nombre, tc.ID as id FROM TIPOCODIGO tc
                                WHERE tc.NOMBRE IN ( ? )";
                        $param = array($tipo_codigo);
                        $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
                        $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

                        usort($query, array($this,"cmp"));

                        foreach ($tipo_codigo as $k => $v) {
                            if($v !== $query[$k]['nombre']){
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
                    // 10->ITEM_PADRE;
                    // 11->TIPOCODIGO_ID;
                    // 12->CODIGOITEM1;
                    // 13->CODIGOITEM2

                    // 14->ID_NIVELITEM1
                    // 15->ID_NIVELITEM2
                    // 16->ID_NIVELITEM3
                    // 17->ID_NIVELITEM4
                    // 18->ID_NIVELITEM5
                    // 19->ID_ITEM
                    // 20->ID_ITEM2
                    // 21->ID_TIPOCODIGO

                    //ARCHIVO A ESCRIBIR CON LOS IDs FINALES
                    $fp = fopen($this->uploadDIR.$name.'_proc.csv', 'w');

                    foreach ($m as $fields) {
                        $id_ni1 = (isset($nni_ni1_[$fields[0].'-'.$fields[1]]))?$nni_ni1_[$fields[0].'-'.$fields[1]]:"NULL";
                        $id_ni2 = (isset($nni_ni2_[$fields[2].'-'.$fields[3]]))?$nni_ni2_[$fields[2].'-'.$fields[3]]:"NULL";
                        $id_ni3 = (isset($nni_ni3_[$fields[4].'-'.$fields[5]]))?$nni_ni3_[$fields[4].'-'.$fields[5]]:"NULL";
                        $id_ni4 = (isset($nni_ni4_[$fields[6].'-'.$fields[7]]))?$nni_ni4_[$fields[6].'-'.$fields[7]]:"NULL";
                        $id_ni5 = (isset($nni_ni5_[$fields[8].'-'.$fields[9]]))?$nni_ni5_[$fields[8].'-'.$fields[9]]:"NULL";
                        $id_item = (isset($item_[$fields[12]]))?$item_[$fields[12]]:"NULL";
                        $id_item_padre = (isset($item_padre_[$fields[10]]))?$item_padre_[$fields[10]]:"NULL";
                        $id_tipo_codigo = (isset($tipo_codigo_[$fields[11]]))?$tipo_codigo_[$fields[11]]:"NULL";

                        $fila = array_merge($fields, array($id_ni1, $id_ni2, $id_ni3, $id_ni4, $id_ni5, $id_item, $id_item_padre, $id_tipo_codigo));
                        fputcsv($fp, $fila,";");
                    }
                    fclose($fp);

                    break;
            }
                    
                

            


            return new JsonResponse(array(
                'status' => true,
                'name' => $name.'_proc.csv',
                'tipo_carga' => $tipo_carga
            ));

    	}else{
    		return new JsonResponse(array(
    			'status' => false, 
    			'mensaje' => 'EL ARCHIVO NO SE PUEDE LEER O NO TIENE EXTENSION CSV'
    			));
    	}
    	
    }

    public function fileprocessAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $data = $request->query->all();
        $name = $data['name'];
        $tipo_carga = $data['tipo_carga'];
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
                    $id = intval($query[0]['id']);

                    try{

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
                    $id = intval($query[0]['id']);

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

            }
                    

            return new JsonResponse(array(
                'status' => true,
                'row_affected' => $row_affected
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
