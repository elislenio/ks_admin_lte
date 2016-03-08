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
		$conf['dt'] = 'KsAdminLteThemeBundle:fragments:crud1_dt_rep_audit.html.twig';
		$conf['csv_filename'] = 'rep_audit_' . date('mdHis') . '.csv';
		$conf['csv_columns'] = array();
		$conf['csv_columns']['logged_at'] = array('field' => 'logged_at', 'title' => 'Fecha');
		$conf['csv_columns']['id'] = array('field' => 'id', 'title' => 'Log Id');
		$conf['csv_columns']['username'] = array('field' => 'username', 'title' => 'Usuario');
		$conf['csv_columns']['object_class'] = array('field' => 'object_class', 'title' => 'Entidad');
		$conf['csv_columns']['action'] = array('field' => 'action', 'title' => 'Acción');
		$conf['csv_columns']['object_id'] = array('field' => 'object_id', 'title' => 'Identificador');
		$conf['csv_columns']['data'] = array('field' => 'data', 'title' => 'Detalle');
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
			->select('a.id', 'a.username', 'a.object_class', 'a.action', 'a.object_id', 'a.data', 'a.logged_at', 
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
			if ($data)
			{
				$records[$i]['data'] = json_encode(unserialize($data));
			}
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
		return $this->get('ks.core.dt_report')->exportCsv(
			$this->getQuery(), 
			$request->query, 
			$conf['filters'], 
			$conf['csv_filename'], 
			$conf['csv_columns'], 
			array($this, 'translateCSV')
		);
    }
}