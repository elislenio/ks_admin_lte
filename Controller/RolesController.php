<?php
namespace Ks\AdminLteThemeBundle\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Ks\CoreBundle\Controller\BaseController;
use Ks\CoreBundle\Entity\Role;
use Ks\CoreBundle\Entity\AccessControlList;
use Ks\CoreBundle\Classes\DbAbs;
use Ks\CoreBundle\Classes\Ajax;

class RolesController extends BaseController
{
	protected function getGrants()
    {
		parent::getAcGrants('ROLES');
    }
	
	private function getCrudConf()
	{
		$conf = array();
		$conf['name'] = 'roles';
		$conf['grants'] = $this->grants;
		$conf['urls'] = array(
			'list'		=> 'roles_list',
			'create'	=> 'roles_create',
			'edit'		=> 'roles_edit',
			'delete'	=> 'roles_delete',
			'export'	=> 'roles_export',
			'acl'		=> 'role_acl'
		);
		$conf['filters'] = array();
		$conf['filters']['id'] = array('filter'=>'id', 'label'=>'Id', 'field'=>'a.id', 'type'=>'text', 'condition'=>'contains');
		$conf['filters']['description'] = array('filter'=>'description', 'label'=>'Descripción', 'field'=>'a.description', 'type'=>'text', 'condition'=>'contains');
		return $conf;
	}
	
	private function getQuery()
	{
		$conn = $this->get('doctrine.dbal.default_connection');
		$qb = $conn
			->createQueryBuilder()
			->select('id', 'description', 'created', 'updated', 
				DbAbs::longDatetime($conn, 'a.created') . " char_created", 
				DbAbs::longDatetime($conn, 'a.updated') . " char_updated")
			->from('ks_role', 'a');
		return $qb;
	}
	
	/**
     * @Route("/roles", name="roles")
     */
    public function indexAction(Request $request)
    {
		// Page header
		$hdr = array('title' => 'Roles', 'small' => '');
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('description'=>'Roles');
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_VIEW')) return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		$crud = $this->getCrudConf();
		
		return $this->render('KsAdminLteThemeBundle::role_list.html.twig', array(
            'hdr' 	=> $hdr, 
			'bc' 	=> $bc,
			'crud' 	=> $crud
        ));
    }
	
	/**
     * @Route("/roles/list", name="roles_list")
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
     * @Route("/roles/export", name="roles_export")
     */
    public function exportAction(Request $request)
    {
		// Page header
		$hdr = array('title' => 'Roles', 'small' => '');
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('route' => $this->get('router')->generate('roles'), 'description'=>'Roles');
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_VIEW')) return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		$conf = $this->getCrudConf();
		$csv_filename = 'roles_' . date('mdHis') . '.csv';
		$csv_columns = array();
		$csv_columns['id'] = array('field' => 'id', 'title' => 'Id');
		$csv_columns['description'] = array('field' => 'description', 'title' => 'Descripción');
		$csv_columns['char_created'] = array('field' => 'char_created', 'title' => 'Fecha de creación');
		$csv_columns['char_updated'] = array('field' => 'char_updated', 'title' => 'Fecha de actualización');
		
		return $this->get('ks.core.dt_report')->exportCsv(
			$this->getQuery(),
			$request->query,
			$conf['filters'],
			$csv_filename,
			$csv_columns
		);
    }
	
	/**
     * @Route("/roles/create", name="roles_create")
     */
    public function createAction(Request $request)
    {
		// Page header
		$hdr = array('title' => 'Nuevo Rol', 'small' => '');
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('route' => $this->get('router')->generate('roles'), 'description'=>'Roles');
		$bc[] = array('description'=>'Crear');
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_CREATE')) return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		// Form
		$role = new Role();
		$form = $this->get('ks.core.role_model')->getFormCreate($role);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			
			try{
				
				$this->get('ks.core.role_model')->insert($role);
				return $this->redirectToRoute('roles');
				
			} catch (\Exception $e) {
				$message = $this->handleException($e);
				$this->get('session')->getFlashBag()->add('error', $message);
			}
		}
		
		return $this->render('KsAdminLteThemeBundle::role_create.html.twig', array(
            'hdr' 	=> $hdr,
			'bc' 	=> $bc,
			'form' => $form->createView()
        ));
	}
	
	/**
     * @Route("/roles/edit/{id}", name="roles_edit", defaults={"id" = 0})
     */
    public function editAction(Request $request, $id)
    {
		// Role
		$em = $this->getDoctrine()->getManager();
		$role = $em->getRepository('KsCoreBundle:Role')->find($id);
		
		// Page header
		$hdr = array('title' => 'Editar Rol', 'small' => 'Id: ' . $role->getId());
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('route' => $this->get('router')->generate('roles'), 'description'=>'Roles');
		$bc[] = array('description'=>'Editar');
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_EDIT')) 
			return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		// Form
		$form = $this->get('ks.core.role_model')->getFormEdit($role);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			
			try{
				
				$this->get('ks.core.role_model')->update($role);
				return $this->redirectToRoute('roles');
				
			} catch (\Exception $e) {
				$message = $this->handleException($e);
				$this->get('session')->getFlashBag()->add('error', $message);
			}
		}
	
        return $this->render('KsAdminLteThemeBundle::role_edit.html.twig', array(
            'hdr' 	=> $hdr,
			'bc' 	=> $bc,
			'form' => $form->createView()
        ));
	}
	
	/**
     * @Route("/roles/delete", name="roles_delete")
     */
    public function deleteAction(Request $request)
    {
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_DELETE')) return Ajax::responseDenied();
		
		$em = $this->getDoctrine()->getManager();
		
		try {
			
			foreach ($request->request->get('ids') as $id)
			{
				$role = $em->getRepository('KsCoreBundle:Role')->find($id);
				$this->get('ks.core.role_model')->delete($role);
			}
			
		} catch (\Exception $e) {
			$message = $this->handleException($e);
			return Ajax::responseResult($message);
		}
		
		return Ajax::responseOk();
	}
	
	
	/************************************************
     * 					ACL
     ************************************************
	 */
	 
	private function getPermCrudConf()
	{
		$conf = array();
		$conf['name'] = 'roles_p';
		$conf['grants'] = $this->grants;
		$conf['urls'] = array(
			'list'		=> 'role_acl_list',
			'create'	=> 'role_acl_create',
			'edit'		=> 'role_acl_edit',
			'delete'	=> 'role_acl_delete',
			'export'	=> 'role_acl_export'
		);
		$conf['filters'] = array();
		$conf['filters']['role'] = array('filter'=>'role', 'label'=>'Rol', 'field'=>'b.id', 'type'=>'text', 'condition'=>'eq', 'hidden' => true);
		$conf['filters']['ac'] = array('filter'=>'ac', 'label'=>'Función', 'field'=>'c.description', 'type'=>'text', 'condition'=>'contains');
		return $conf;
	}
	
	private function getPermQuery()
	{
		$conn = $this->get('doctrine.dbal.default_connection');
		$qb = $conn
			->createQueryBuilder()
			->select('a.id', 'b.description as role', 'c.description as ac', 'a.mask', 'a.created', 'a.updated', 
				DbAbs::longDatetime($conn, 'a.created') . " char_created", 
				DbAbs::longDatetime($conn, 'a.updated') . " char_updated")
			->from('ks_acl', 'a')
			->innerJoin('a', 'ks_role', 'b', 'a.role_id = b.id')
			->innerJoin('a', 'ks_ac', 'c', 'a.ac_id = c.id');
		
		return $qb;
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
     * @Route("/roles/acl/view/{id}", name="role_acl", defaults={"id" = 0})
     */
    public function aclAction(Request $request, $id)
    {
		// Role
		$em = $this->getDoctrine()->getManager();
		$role = $em->getRepository('KsCoreBundle:Role')->find($id);
		
		// Page header
		$hdr = array('title' => 'Permisos para ' . $role->getDescription(), 'small' => '');
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('route' => $this->get('router')->generate('roles'), 'description'=>'Roles');
		$bc[] = array('description'=>'Permisos para '.$role->getDescription());
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_VIEW')) return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		// Conf
		$crud = $this->getPermCrudConf();
		
		// Sets the value for the hidden field
		$crud['filters']['role']['value'] = $id;
		$crud['url_param'] = array();
		$crud['url_param']['create'] = $id;
		
		return $this->render('KsAdminLteThemeBundle::role_acl_list.html.twig', array(
            'hdr' 	=> $hdr,
			'bc' 	=> $bc,
			'crud' 	=> $crud
        ));
    }
	
	/**
     * @Route("/roles/acl/list", name="role_acl_list")
     */
    public function aclListAction(Request $request)
    {
		$dt_report = $this->get('ks.core.dt_report');
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_VIEW')) return $dt_report->getDeniedResponse();
		
		$conf = $this->getPermCrudConf();
		return $dt_report->getList($this->getPermQuery(), $request->request, $conf['filters']);
    }
	
	/**
     * @Route("/roles/acl/export", name="role_acl_export")
     */
    public function aclExportAction(Request $request)
    {
		// Access Control
		$this->getGrants();
		
		if (! $this->granted('MASK_VIEW')) return $this->render('KsAdminLteThemeBundle::denied.html.twig');
		
		$conf = $this->getPermCrudConf();
		$csv_filename = 'rol_permisos_' . date('mdHis') . '.csv';
		$csv_columns = array();
		$csv_columns['id'] = array('field' => 'id', 'title' => 'Id');
		$csv_columns['role'] = array('field' => 'role', 'title' => 'Rol');
		$csv_columns['ac'] = array('field' => 'ac', 'title' => 'Función');
		$csv_columns['read'] = array('field' => 'mask', 'title' => 'Lectura');
		$csv_columns['create'] = array('field' => 'mask', 'title' => 'Alta');
		$csv_columns['update'] = array('field' => 'mask', 'title' => 'Modificación');
		$csv_columns['delete'] = array('field' => 'mask', 'title' => 'Baja');
		$csv_columns['char_created'] = array('field' => 'char_created', 'title' => 'Fecha de creación');
		$csv_columns['char_updated'] = array('field' => 'char_updated', 'title' => 'Fecha de actualización');
		
		return $this->get('ks.core.dt_report')->exportCsv(
			$this->getPermQuery(),
			$request->query,
			$conf['filters'],
			$csv_filename,
			$csv_columns,
			array($this, 'translateCSV')
		);
    }
	
	/**
     * @Route("/roles/acl/create/{id}", name="role_acl_create", defaults={"id" = 0})
     */
    public function aclCreateAction(Request $request, $id)
    {
		//Role
		$em = $this->getDoctrine()->getManager();
		$role = $em->getRepository('KsCoreBundle:Role')->find($id);
		
		// Page header
		$hdr = array('title' => 'Rol: ' . $role->getDescription(), 'small' => 'Agregar permisos');
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('route' => $this->get('router')->generate('roles'), 'description'=>'Roles');
		$bc[] = array('route' => $this->get('router')->generate('role_acl', array('id' => $id)), 'description' => 'Permisos para '.$role->getDescription());
		$bc[] = array('description'=>'Agregar permiso');
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_CREATE')) 
			return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		// Form
		$acl = new AccessControlList();
		$acl->setRoleId($id);
		$acl->setMaskView(true);
		$form = $this->get('ks.core.role_model')->getFormAclCreate($acl);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			
			try{
				
				$this->get('ks.core.role_model')->insertAcl($role, $acl);
				return $this->redirectToRoute('role_acl', array('id' => $id));
				
			} catch (\Exception $e) {
				$message = $this->handleException($e);
				$this->get('session')->getFlashBag()->add('error', $message);
			}
		}
		
		return $this->render('KsAdminLteThemeBundle::role_acl_create.html.twig', array(
            'hdr' 	=> $hdr,
			'bc' 	=> $bc, 
			'form' => $form->createView()
        ));
	}
	
	/**
     * @Route("/roles/acl/edit/{id}", name="role_acl_edit", defaults={"id" = 0})
     */
    public function aclEditAction(Request $request, $id)
    {
		// Acl
		$em = $this->getDoctrine()->getManager();
		$acl = $em->getRepository('KsCoreBundle:AccessControlList')->find($id);
		$acl->parseMask();
		
		// Page header
		$hdr = array('title' => 'Rol: ' . $acl->getRole()->getDescription(), 'small' => 'Editar permisos');
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('route' => $this->get('router')->generate('roles'), 'description'=>'Roles');
		$bc[] = array('route' => $this->get('router')->generate('role_acl', array('id' => $acl->getRoleId())), 'description' => 'Permisos para '.$acl->getRole()->getDescription());
		$bc[] = array('description'=>'Editar permisos');
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_EDIT')) return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		// Form
		$form = $this->get('ks.core.role_model')->getFormAclEdit($acl);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			
			try{
				
				$this->get('ks.core.role_model')->updateAcl($acl);
				return $this->redirectToRoute('role_acl', array('id' => $acl->getRoleId()));
				
			} catch (\Exception $e) {
				$message = $this->handleException($e);
				$this->get('session')->getFlashBag()->add('error', $message);
			}
		}
		
		return $this->render('KsAdminLteThemeBundle::role_acl_edit.html.twig', array(
            'hdr' 	=> $hdr,
			'bc' 	=> $bc, 
			'form' 	=> $form->createView(),
			'acl' 	=> $acl
        ));
	}
	
	/**
     * @Route("/roles/acl/delete", name="role_acl_delete")
     */
    public function aclDeleteAction(Request $request)
    {
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_DELETE')) return Ajax::responseDenied();
		
		$em = $this->getDoctrine()->getManager();
		
		try {
			
			foreach ($request->request->get('ids') as $id)
			{
				$acl = $em->getRepository('KsCoreBundle:AccessControlList')->find($id);
				$this->get('ks.core.role_model')->deleteAcl($acl);
			}
			
		} catch (\Exception $e) {
			$message = $this->handleException($e);
			return Ajax::responseResult($message);
		}
		
		return Ajax::responseOk();
	}
}