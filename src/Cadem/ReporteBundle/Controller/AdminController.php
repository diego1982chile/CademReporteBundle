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

    public function fileuploadAction(Request $request)
    {
        $uf = $request->files->get('file1');
    	$tipo_carga = $request->request->get('tipo_carga');
    	if (null === $uf) return new JsonResponse(array('status' => false));//ERROR

    	$uf = $uf->move($this->uploadDIR,$uf->getClientOriginalName().'__'.date("d_m_Y_H_i_s").'.'.$uf->getClientOriginalExtension());
    	return new JsonResponse(array('status' => true, 'name' =>  $uf->getFilename(), 'tipo_carga' => $tipo_carga));
    }

    public function filevalidAction(Request $request)
    {
    	$em = $this->getDoctrine()->getManager();
        $data = $request->query->all();
        $tipo_carga = $data['tipo_carga'];
    	$name = $data['name'];
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
                    //FORMATO ES: TIPOCODIGO_ID;FABRICANTE;MARCA;NOMBRE;CODIGO
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
                        if(strlen($fila[4]) !== 13){//ERROR
                            return new JsonResponse(array(
                                'status' => false,
                                'mensaje' => 'EL SKU '.$fila[4].' CERCA DE LA LINEA '.$k.', NO TIENE 13 DIGITOS'
                            ));
                        }
                        $tipo_codigo[] = $fila[0];
                        if($fila[1] !== '') $fabricante[] = $fila[1];
                        if($fila[2] !== '') $marca[] = $fila[2];
                        if(in_array($fila[4], $cod_encontrados)){//SE BUSCAN Y DESCARTA LOS SKU ENCONTRADOS Y SE REGISTRA
                            unset($m[$k]);
                            $item_descartados++;
                        }
                    }

                    //SE VALIDA QUE EXISTA TIPOCODIGO_ID
                    if(count($tipo_codigo)){
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
                    if(count($marca)){
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
                    


                    // $fabricante = array();
                    // $marca = array();
                    foreach ($m as $value) $folio[] = $value[4];
                    //FORMATO ES: COMUNA;CANAL;CADENA;FORMATO;FOLIO;CALLE;NUMERO
                    //SI LA PRIMERA FILA TIENE LOS ENCABEZADOS SE BORRA
                    if($m[0][0] === 'COMUNA' || $m[0][4] === 'FOLIO') unset($m[0]);
                    

                    //SE VALIDA QUE EL FOLIO NO ESTE EN LA BD
                    $sql = "SELECT s.FOLIOCADEM as folio FROM SALA s
                            WHERE s.FOLIOCADEM IN ( ? )
                            ORDER BY s.FOLIOCADEM";
                    $param = array($folio);
                    $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
                    $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();
                    $folio_encontrados = array();
                    foreach ($query as $v) $folio_encontrados[] = $v['codigo'];

                    foreach ($m as $k => $fila) {
                        $tipo_codigo[] = $fila[0];
                        if($fila[1] !== '') $fabricante[] = $fila[1];
                        if($fila[2] !== '') $marca[] = $fila[2];
                        if(in_array($fila[4], $cod_encontrados)){//SE BUSCAN Y DESCARTA LOS SKU ENCONTRADOS Y SE REGISTRA
                            unset($m[$k]);
                            $item_descartados++;
                        }
                    }

                    //SE VALIDA QUE EXISTA TIPOCODIGO_ID
                    if(count($tipo_codigo)){
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
                    if(count($marca)){
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
                    $file = new \SplFileObject($this->uploadDIR.$name.'_proc.csv', 'w');

                    foreach ($m as $fields) {
                        $id_tipo_codigo = (isset($tipo_codigo_[$fields[0]]))?$tipo_codigo_[$fields[0]]:"NULL";
                        $id_fabricante = (isset($fabricante_[$fields[1]]))?$fabricante_[$fields[1]]:"NULL";
                        $id_marca = (isset($marca_[$fields[2]]))?$marca_[$fields[2]]:"NULL";
                        $fila = array_merge($fields, array($id_tipo_codigo, $id_fabricante, $id_marca));
                        $file->fputcsv($fila,";");
                    }




                    break;
            }
                    
                

            


            return new JsonResponse(array(
                'status' => true,
                'name' => $file->getFilename(),
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
