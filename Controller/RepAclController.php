<?php
namespace Ks\AdminLteThemeBundle\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Ks\CoreBundle\Controller\BaseController;
use Ks\CoreBundle\Classes\DbAbs;
use Ks\CoreBundle\Classes\Ajax;

class RepAclController extends BaseController
{
	protected function getGrants()
    {
		parent::getAcGrants('REP_ACL');
		// this report is read only
		$this->grants['MASK_CREATE'] = false;
		$this->grants['MASK_EDIT'] = false;
		$this->grants['MASK_DELETE'] = false;
    }
	
	private function getCrudConf()
	{
		$conf = array();
		$conf['name'] = 'acls';
		$conf['grants'] = $this->grants;
		$conf['urls'] = array(
			'list'		=> 'acls_list',
			'export'	=> 'acls_export'
		);
		$conf['filters'] = array();
		$conf['filters']['role'] = array('filter'=>'role', 'label'=>'Rol', 'field'=>'b.description', 'type'=>'text', 'condition'=>'contains');
		$conf['filters']['ac'] = array('filter'=>'ac', 'label'=>'Función', 'field'=>'c.name', 'type'=>'text', 'condition'=>'contains');
		return $conf;
	}
	
	private function getQuery()
	{
		$conn = $this->get('doctrine.dbal.default_connection');
		$qb = $conn
			->createQueryBuilder()
			->select('a.id', 'b.description as role', 'c.description as ac', 'a.mask')
			->from('ks_acl', 'a')
			->innerJoin('a', 'ks_role', 'b', 'a.role_id = b.id')
			->innerJoin('a', 'ks_ac', 'c', 'a.ac_id = c.id');
		return $qb;
	}
	
	/**
     * @Route("/acls", name="acls")
     */
    public function indexAction(Request $request)
    {
		// Page header
		$hdr = array('title' => 'Permisos', 'small' => '');
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('description'=>'Permisos');
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_VIEW')) return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		// Config
		$crud = $this->getCrudConf();
		
		return $this->render('KsAdminLteThemeBundle::rep_acl_list.html.twig', array(
            'hdr' 	=> $hdr,
			'bc' 	=> $bc,
			'crud' 	=> $crud
        ));
    }
	
	/**
     * @Route("/acls/list", name="acls_list")
     */
    public function listAction(Request $request)
    {
		$dt_report = $this->get('ks.core.dt_report');
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_VIEW')) return $dt_report->getDeniedResponse();
		
		$conf = $this->getCrudConf();
		return $dt_report->getList($this->getQuery(), $request->request, $conf['filters']);
    }
	
	private function translateGranted($granted)
	{
		if ($granted) return 'Si';
		return 'No';
	}
	
	public function translateCSV($key, $field, $value)
    {
		switch ($key)
		{
			case 'read':
				$granted = MaskBuilder::MASK_VIEW & $value;
				return $this->translateGranted($granted);
				break;
			case 'create':
				$granted = MaskBuilder::MASK_CREATE & $value;
				return $this->translateGranted($granted);
				break;
			case 'update':
				$granted = MaskBuilder::MASK_EDIT & $value;
				return $this->translateGranted($granted);
				break;
			case 'delete':
				$granted = MaskBuilder::MASK_DELETE & $value;
				return $this->translateGranted($granted);
				break;
		}
		
		return $value;
	}
	
	/**
     * @Route("/acls/export", name="acls_export")
     */
    public function exportAction(Request $request)
    {
		// Page header
		$hdr = array('title' => 'Permisos', 'small' => '');
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('route' => $this->get('router')->generate('acls'), 'description'=>'Permisos');
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_VIEW')) return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		$conf = $this->getCrudConf();
		$csv_filename = 'permisos_' . date('mdHis') . '.csv';
		$csv_columns = array();
		$csv_columns['id'] = array('field' => 'id', 'title' => 'Id');
		$csv_columns['role'] = array('field' => 'role', 'title' => 'Rol');
		$csv_columns['ac'] = array('field' => 'ac', 'title' => 'Función');
		$csv_columns['read'] = array('field' => 'mask', 'title' => 'Lectura');
		$csv_columns['create'] = array('field' => 'mask', 'title' => 'Alta');
		$csv_columns['update'] = array('field' => 'mask', 'title' => 'Modificación');
		$csv_columns['delete'] = array('field' => 'mask', 'title' => 'Baja');
		
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