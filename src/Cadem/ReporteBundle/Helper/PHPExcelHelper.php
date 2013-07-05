<?php
namespace Cadem\ReporteBundle\Helper;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\SecurityContext;
use Cadem\ReporteBundle\Helper\MedicionHelper;
use Symfony\Component\HttpFoundation\Session\Session;

class PHPExcelHelper {

    protected $em;
	protected $security;
	protected $user;
	protected $medicion;
	protected $session;
	private $salasmedidas = null;
	private $totalsalas = null;
	private $objPHPExcel;

    public function __construct(EntityManager $entityManager, SecurityContext $security, MedicionHelper $medicion, Session $session) {
        $this->em = $entityManager;
		$this->security = $security;
		$this->medicion = $medicion;
		$this->session = $session;
		if($security->getToken() != null) $this->user = $security->getToken()->getUser();
		else $this->user = null;
		$this->objPHPExcel = new \PHPExcel();
    }

    public function getExcelDetalle(){
		$em = $this->em;
		$user = $this->user;
		$id_user = $user->getId();
		$id_cliente = $user->getClienteId();
		$id_ultima_medicion = $this->medicion->getIdUltimaMedicion();
		$nombre_medicion = $this->medicion->getNombreMedicion($id_ultima_medicion);
		$session = $this->session;

		//DATOS
		$sql = "SELECT (case when q.hayquiebre = 1 then 1 else 0 END) as quiebre,
		ic.CODIGOITEM1 as COD_PRODUCTO,
		i.NOMBRE as NOM_PRODUCTO,
		ni.NOMBRE as SEGMENTO,
		sc.CODIGOSALA as COD_SALA,
		s.CALLE as CALLE_SALA,
		s.NUMEROCALLE as NUMCALLE_SALA,
		cad.NOMBRE as CAD_SALA,
		com.NOMBRE as COM_SALA
		FROM QUIEBRE q
		INNER JOIN PLANOGRAMAQ p on p.ID = q.PLANOGRAMAQ_ID and p.MEDICION_ID = {$id_ultima_medicion}
		INNER JOIN SALACLIENTE sc on sc.ID = p.SALACLIENTE_ID and sc.CLIENTE_ID = {$id_cliente} AND sc.MEDICION_ID = {$id_ultima_medicion}
		INNER JOIN SALA s on s.ID = sc.SALA_ID
		INNER JOIN ITEMCLIENTE ic on ic.ID = p.ITEMCLIENTE_ID AND ic.MEDICION_ID = {$id_ultima_medicion}
		INNER JOIN NIVELITEM ni on ni.ID = ic.NIVELITEM_ID
		INNER JOIN COMUNA com on s.COMUNA_ID=com.ID
		INNER JOIN CADENA cad on s.CADENA_ID=cad.ID	
		INNER JOIN ITEM i on i.ID = ic.ITEM_ID	
		ORDER BY SEGMENTO,NOM_PRODUCTO,CAD_SALA,COM_SALA,CALLE_SALA";
		
		$sha1 = sha1($sql);

		if(!$session->has($sha1)){
			$detalle_quiebre = $em->getConnection()->executeQuery($sql)->fetchAll();
			$session->set($sha1,$detalle_quiebre);
		}
		else $detalle_quiebre = $session->get($sha1);




    	$objPHPExcel = $this->objPHPExcel;
    	$objPHPExcel->getProperties()->setCreator("Cadem")
							 // ->setLastModifiedBy("Maarten Balliauw")
							 ->setTitle("Reporte Detalle Cadem")
							 ->setSubject("Reporte Detalle")
							 ->setDescription("");
							 // ->setKeywords("office 2007 openxml php")
							 // ->setCategory("Test result file");

		//EXTRAE DATOS
		foreach ($detalle_quiebre as $d) {
			$header[$d['COD_SALA']] = $d['CAD_SALA'].' '.$d['COM_SALA'].' '.$d['CALLE_SALA'];
			$producto[$d['COD_PRODUCTO']] = array($d['NOM_PRODUCTO'], $d['SEGMENTO']);
			$quiebre[$d['COD_SALA']][$d['COD_PRODUCTO']] = intval($d['quiebre']);
		}

		//ORDENAR LAS SALAS SI HAY DATOS, SI NO RETORNAR ERROR
		if(isset($header) && is_array($header)) asort($header);
		else return 'NO HAY DATOS o FALLO EL PROCESO';

		//LLENAR PRODUCTO/SEGMENTO
		$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', 'PRODUCTO');
		$objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(50);
		$objPHPExcel->setActiveSheetIndex(0)->setCellValue('B1', 'SEGMENTO');
		$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);

		$col = 'A';
		$fil = 2;
		foreach ($producto as $kp => $vp) {
			$objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.$fil, $vp[0]);
			$objPHPExcel->setActiveSheetIndex(0)->setCellValue('B'.$fil, $vp[1]);
			$fil++;
		}

		//LLENAR QUIEBRES/SALAS
		$col = 'C';
		$fil = 2;
		foreach ($header as $kh => $vh) {
			$objPHPExcel->setActiveSheetIndex(0)->setCellValue($col.'1', $vh);
			foreach ($producto as $kp => $vp) {
				//QUIEBRES
				if(array_key_exists($kh,$quiebre)){
					if(array_key_exists($kp,$quiebre[$kh])) $objPHPExcel->setActiveSheetIndex(0)->setCellValue($col.$fil, $quiebre[$kh][$kp]);
				}
				
				$fil++;
			}
			$col++;
			$fil = 2;
		}

		$objPHPExcel->getActiveSheet()->getStyle('C1:'.$col.'1')->getAlignment()->setWrapText(true);
		$objPHPExcel->getActiveSheet()->getStyle('C1:'.$col.'1')->getFont()->setSize(8);
		$objPHPExcel->getActiveSheet()->getStyle('A1:'.$col.'1')->getFont()->setBold(true);



		
		

		// Rename worksheet
		$objPHPExcel->getActiveSheet()->setTitle('Detalle quiebre');


		// Set active sheet index to the first sheet, so Excel opens this as the first sheet
		$objPHPExcel->setActiveSheetIndex(0);

		// Redirect output to a client’s web browser (Excel2007)
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment;filename="Detalle_quiebre_'.$nombre_medicion.'.xlsx"');
		header('Cache-Control: max-age=0');
		$objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save('php://output');
		exit;

    }
}