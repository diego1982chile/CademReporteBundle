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

	public function indexAction()
    {
		

		
		//RESPONSE
		$response = $this->render('CademReporteBundle:Admin:index.html.twig',
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
    	if (null === $uf) return new JsonResponse(array('status' => false));//ERROR

    	$uf = $uf->move($this->uploadDIR,$uf->getClientOriginalName().'__'.date("d_m_Y_H_i_s").'.'.$uf->getClientOriginalExtension());
    	return new JsonResponse(array('status' => true, 'name' =>  $uf->getFilename()));
    }

    public function filevalidAction(Request $request)
    {
    	$em = $this->getDoctrine()->getManager();
        $data = $request->query->all();
    	$name = $data['name'];
    	$file = new File($this->uploadDIR.$name);
    	if($file->isReadable() && $file->getExtension() === 'csv'){
    		//LEER Y VALIDAR
    		$fileobj = $file->openFile('r');

    		while (!$fileobj->eof()) {
			    $row = $fileobj->fgetcsv(';');
                $row = array_map("utf8_encode", $row);//SE PASA DE ANSI A UTF-8
				$m[] = $row;
			}
            //FORMATO ES: TIPOCODIGO_ID;FABRICANTE;MARCA;NOMBRE;CODIGO
            //SI LA PRIMERA FILA TIENE LOS ENCABEZADOS SE BORRA
            if($m[0][0] === 'TIPOCODIGO_ID' || $m[0][4] === 'CODIGO') unset($m[0]);
            //SI LA ULTIMA FILA ES EXTRAÑA SE BORRA
            if(!isset($m[count($m)][4])) unset($m[count($m)]);

            //SE VERIFICA QUE TODOS LOS SKU TENGAN 13 DIG
            foreach ($m as $k => $fila) {
                if(strlen($fila[4]) !== 13){//ERROR
                    return new JsonResponse(array(
                        'status' => false,
                        'mensaje' => 'EL SKU '.$fila[4].' CERCA DE LA LINEA '.$k.', NO TIENE 13 DIGITOS'
                    ));
                }
                $tipo_codigo[] = $fila[0];
                if($fila[2] !== '') $marca[] = $fila[2];
            }
            //SE VALIDA QUE EXITA TIPOCODIGO_ID
            $tipo_codigo = array_unique($tipo_codigo);            

            $sql = "SELECT COUNT(*) as count FROM TIPOCODIGO tc
                    WHERE tc.NOMBRE IN ( ? )";
            $param = array($tipo_codigo);
            $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
            $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();
            $cant_tipo_codigo = intval($query[0]['count']);

            if(count($tipo_codigo) !== $cant_tipo_codigo){
                return new JsonResponse(array(
                    'status' => false,
                    'mensaje' => 'AL MENOS EXISTE UN TIPO DE CODIGO QUE NO EXISTE EN LA BD'
                ));
            }

            //SE VALIDA QUE EXITA MARCA
            $marca = array_unique($marca);
            sort($marca);

            $sql = "SELECT m.NOMBRE as nombre FROM MARCA m
                    WHERE m.NOMBRE IN ( ? )
                    ORDER BY m.NOMBRE";
            $param = array($marca);
            $tipo_param = array(\Doctrine\DBAL\Connection::PARAM_STR_ARRAY);
            $query = $em->getConnection()->executeQuery($sql,$param,$tipo_param)->fetchAll();

            //SE ARREGLA PARA ORDENARSE POR PHP YA QUE LAS Ñs CAUSAN DISTINTOS ORDENES
            foreach ($query as $v) $query_[] = $v['nombre'];
            $query = $query_;
            sort($query);

            foreach ($marca as $k => $v) {
                if($v !== $query[$k]){
                    return new JsonResponse(array(
                        'status' => false,
                        'mensaje' => 'LA MARCA "'.$v.'" NO EXISTE EN LA BD.'
                    ));
                }
            }


            return new JsonResponse(array(
                'status' => true,
                'name' => $name
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
        $file = new File($this->uploadDIR.$name);
        if($file->isReadable() && $file->getExtension() === 'csv'){
            //LEER Y PROCESAR
            $fileobj = $file->openFile('r');

            while (!$fileobj->eof()) {
                $row = $fileobj->fgetcsv(';');
                $row = array_map("utf8_encode", $row);//SE PASA DE ANSI A UTF-8
                $m[] = $row;
            }
            //FORMATO ES: TIPOCODIGO_ID;FABRICANTE;MARCA;NOMBRE;CODIGO
            //SI LA PRIMERA FILA TIENE LOS ENCABEZADOS SE BORRA
            if($m[0][0] === 'TIPOCODIGO_ID' || $m[0][4] === 'CODIGO') unset($m[0]);
            //SI LA ULTIMA FILA ES EXTRAÑA SE BORRA
            if(!isset($m[count($m)][4])) unset($m[count($m)]);

            //SE CARGA EN LA BD
            return new Response(print_r($m,true));
        }else{
            return new JsonResponse(array(
                'status' => false, 
                'mensaje' => 'EL ARCHIVO NO SE PUEDE LEER O NO TIENE EXTENSION CSV'
                ));
        }
    }
}
