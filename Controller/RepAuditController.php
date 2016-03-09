<?php
namespace Ks\AdminLteThemeBundle\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Ks\CoreBundle\Controller\BaseController;
use Ks\CoreBundle\Classes\DbAbs;
use Ks\CoreBundle\Classes\Ajax;

class RepAuditController extends BaseController
{
	protected function getGrants()
    {
		parent::getAcGrants('REP_AUDIT');
		// this report is read only
		$this->grants['MASK_CREATE'] = false;
		$this->grants['MASK_EDIT'] = false;
		$this->grants['MASK_DELETE'] = false;
    }
	
	private function getCrudConf()
	{
		$conf = array();
		$conf['name'] = 'rep_audit';
		$conf['grants'] = $this->grants;
		$conf['urls'] = array(
			'list'		=> 'rep_audit_list',
			'export'	=> 'rep_audit_export'
		);
		$conf['filters'] = array();
		$conf['filters']['logged_at'] = array('filter'=>'logged_at', 'label'=>'Rango de fechas', 'field'=>'a.logged_at', 'type'=>'datetime', 'condition'=>'bt', 'input_type'=>'date_range', 'extra'=>'readonly', 'value_callback'=>'Ks\AdminLteThemeBundle\Classes\DateRangePicker::parseValue');
		$conf['filters']['username'] = array('filter'=>'username', 'label'=>'Usuario', 'field'=>'a.username', 'type'=>'text', 'condition'=>'eq');
		$conf['filters']['entity'] = array('filter'=>'entity', 'label'=>'Entidad', 'field'=>'a.object_class', 'type'=>'text', 'condition'=>'contains');
		$conf['filters']['action'] = array('filter'=>'action', 'label'=>'Acción', 'field'=>'a.action', 'type'=>'text', 'condition'=>'eq');
		return $conf;
	}
	
	private function getQuery()
	{
		$conn = $this->get('doctrine.dbal.default_connection');
		$qb = $conn
			->createQueryBuilder()
			->select('a.id', 'a.username', 'a.object_class', 'a.version', 'a.action', 'a.object_id', 'a.data', 'a.logged_at', 
				DbAbs::longDatetime($conn, 'a.logged_at') . " char_logged_at")
			->from('ext_log_entries', 'a');
		return $qb;
	}
	
	/**
     * @Route("/rep_audit", name="rep_audit")
     */
    public function indexAction(Request $request)
    {
		// Page header
		$hdr = array('title' => 'Pista de auditoría', 'small' => '');
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('description'=>'Pista de auditoría');
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_VIEW')) 
			return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		// Config
		$crud = $this->getCrudConf();
		
		return $this->render('KsAdminLteThemeBundle::rep_audit_list.html.twig', array(
            'hdr' 	=> $hdr,
			'bc' 	=> $bc,
			'crud' 	=> $crud
        ));
    }
	
	public function translateList($records)
    {
		for ($i=0; $i<count($records); $i++)
		{
			$data = $records[$i]['data'];
			$object_class = pathinfo($records[$i]['object_class']);
			
			if ($data)
				$records[$i]['data'] = json_encode(unserialize($data));
			
			$records[$i]['object_class'] = $object_class['filename'];
		}
		
		return $records;
	}
	
	public function translateCSV($key, $field, $value)
    {
		switch ($key)
		{
			case 'data':
				return json_encode(unserialize($value));
				break;
			case 'object_class':
				$object_class = pathinfo($value);
				return $object_class['filename'];
				break;
		}
		
		return $value;
	}
	
	/**
     * @Route("/rep_audit/list", name="rep_audit_list")
     */
    public function listAction(Request $request)
    {
		$dt_report = $this->get('ks.core.dt_report');
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_VIEW')) return $dt_report->getDeniedResponse();
		
		$conf = $this->getCrudConf();
		return $dt_report->getList($this->getQuery(), $request->request, $conf['filters'], array($this, 'translateList'));
    }
	
	/**
     * @Route("/rep_audit/export", name="rep_audit_export")
     */
    public function exportAction(Request $request)
    {
		// Page header
		$hdr = array('title' => 'Pista de auditoría', 'small' => '');
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('route' => $this->get('router')->generate('rep_audit'), 'description'=>'Pista de auditoría');
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_VIEW')) 
			return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		$conf = $this->getCrudConf();
		$csv_filename = 'rep_audit_' . date('mdHis') . '.csv';
		$csv_columns = array();
		$csv_columns['id'] = array('field' => 'id', 'title' => 'Log Id');
		$csv_columns['logged_at'] = array('field' => 'logged_at', 'title' => 'Fecha');
		$csv_columns['username'] = array('field' => 'username', 'title' => 'Usuario');
		$csv_columns['object_class'] = array('field' => 'object_class', 'title' => 'Entidad');
		$csv_columns['version'] = array('field' => 'version', 'title' => 'Versión');
		$csv_columns['action'] = array('field' => 'action', 'title' => 'Acción');
		$csv_columns['object_id'] = array('field' => 'object_id', 'title' => 'Identificador');
		$csv_columns['data'] = array('field' => 'data', 'title' => 'Detalle');
		
		return $this->get('ks.core.dt_report')->exportCsv(
			$this->getQuery(), 
			$request->query, 
			$conf['filters'], 
			$csv_filename, 
			$csv_columns, 
			array($this, 'translateCSV')
		);
    }
}