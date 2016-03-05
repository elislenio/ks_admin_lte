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
	
	private function getCrud1Conf()
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
		$conf['sql'] = array();
		$conf['sql']['select'] = array('a.user_id', 'a.role_id', 'b.username as user', 'c.description as role');
		$conf['sql']['from'] = array('ks_user_role', 'a');
		$conf['sql']['innerJoin'] = array();
		$conf['sql']['innerJoin'][] = array('a', 'ks_user', 'b', 'a.user_id = b.id');
		$conf['sql']['innerJoin'][] = array('a', 'ks_role', 'c', 'a.role_id = c.id');
		$conf['filters'] = array();
		$conf['filters']['user'] = array('filter'=>'user', 'label'=>'Usuario', 'field'=>'b.username', 'type'=>'text', 'condition'=>'contains');
		$conf['filters']['role'] = array('filter'=>'role', 'label'=>'Rol', 'field'=>'c.description', 'type'=>'text', 'condition'=>'contains');
		return $conf;
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
		$crud = $this->getCrud1Conf();
		
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
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_VIEW')) return $this->get('ks.core.crud1')->getDeniedResponse();
		
		$conn = $this->get('doctrine.dbal.default_connection');
		return $this->get('ks.core.crud1')->getList($conn, $request->request, $this->getCrud1Conf());
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
		
		$conn = $this->get('doctrine.dbal.default_connection');
		return $this->get('ks.core.crud1')->exportCsv(
			$conn,
			$request->query, 
			$this->getCrud1Conf()
		);
    }
}