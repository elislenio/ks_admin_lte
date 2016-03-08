<?php
namespace Ks\AdminLteThemeBundle\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Ks\CoreBundle\Controller\BaseController;
use Ks\CoreBundle\Classes\DbAbs;
use Ks\CoreBundle\Classes\Ajax;

class RepUsersRolesController extends BaseController
{
	protected function getGrants()
    {
		parent::getAcGrants('REP_USERS_ROLES');
		// this report is read only
		$this->grants['MASK_CREATE'] = false;
		$this->grants['MASK_EDIT'] = false;
		$this->grants['MASK_DELETE'] = false;
    }
	
	private function getCrudConf()
	{
		$conf = array();
		$conf['name'] = 'rep_users_roles';
		$conf['grants'] = $this->grants;
		$conf['urls'] = array(
			'list'		=> 'rep_users_roles_list',
			'export'	=> 'rep_users_roles_export'
		);
		$conf['dt'] = 'KsAdminLteThemeBundle:fragments:crud1_dt_rep_users_roles.html.twig';
		$conf['csv_filename'] = 'rep_users_roles_' . date('mdHis') . '.csv';
		$conf['csv_columns'] = array();
		$conf['csv_columns']['user'] = array('field' => 'user', 'title' => 'Usuario');
		$conf['csv_columns']['role'] = array('field' => 'role', 'title' => 'Rol');
		$conf['filters'] = array();
		$conf['filters']['user'] = array('filter'=>'user', 'label'=>'Usuario', 'field'=>'b.username', 'type'=>'text', 'condition'=>'contains');
		$conf['filters']['role'] = array('filter'=>'role', 'label'=>'Rol', 'field'=>'c.description', 'type'=>'text', 'condition'=>'contains');
		return $conf;
	}
	
	private function getQuery()
	{
		$conn = $this->get('doctrine.dbal.default_connection');
		$qb = $conn
			->createQueryBuilder()
			->select('a.user_id', 'a.role_id', 'b.username as user', 'c.description as role')
			->from('ks_user_role', 'a')
			->innerJoin('a', 'ks_user', 'b', 'a.user_id = b.id')
			->innerJoin('a', 'ks_role', 'c', 'a.role_id = c.id');
		return $qb;
	}
	
	/**
     * @Route("/rep_users_roles", name="rep_users_roles")
     */
    public function indexAction(Request $request)
    {
		// Page header
		$hdr = array('title' => 'Usuarios y Roles', 'small' => '');
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('description'=>'Usuarios y Roles');
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_VIEW')) 
			return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		// Config
		$crud = $this->getCrudConf();
		
		return $this->render('KsAdminLteThemeBundle::rep_users_roles_list.html.twig', array(
            'hdr' 	=> $hdr,
			'bc' 	=> $bc,
			'crud' 	=> $crud
        ));
    }
	
	/**
     * @Route("/rep_users_roles/list", name="rep_users_roles_list")
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
	
	/**
     * @Route("/rep_users_roles/export", name="rep_users_roles_export")
     */
    public function exportAction(Request $request)
    {
		// Page header
		$hdr = array('title' => 'Usuarios y Roles', 'small' => '');
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('route' => $this->get('router')->generate('rep_users_roles'), 'description'=>'Usuarios y Roles');
		
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
			$conf['csv_columns']
		);
    }
}