<?php
namespace Ks\AdminLteThemeBundle\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Form\Extension\Core\Type as FormType;
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
			'list'			=> 'roles_list',
			'create'		=> 'roles_create',
			'edit'			=> 'roles_edit',
			'delete'		=> 'roles_delete',
			'export'		=> 'roles_export',
			'permissions'	=> 'role_permissions'
		);
		$conf['dt'] = 'KsAdminLteThemeBundle:fragments:crud1_dt_roles.html.twig';
		$conf['csv_filename'] = 'roles_' . date('mdHis') . '.csv';
		$conf['csv_columns'] = array();
		$conf['csv_columns']['id'] = array('field' => 'id', 'title' => 'Id');
		$conf['csv_columns']['description'] = array('field' => 'description', 'title' => 'Descripción');
		$conf['csv_columns']['char_created'] = array('field' => 'char_created', 'title' => 'Fecha de creación');
		$conf['csv_columns']['char_updated'] = array('field' => 'char_updated', 'title' => 'Fecha de actualización');
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
		return $this->get('ks.core.dt_report')->exportCsv(
			$this->getQuery(), 
			$request->query, 
			$conf['filters'], 
			$conf['csv_filename'], 
			$conf['csv_columns']
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
		
		$role = new Role();
		
        $form = $this->createFormBuilder($role, array('validation_groups' => array('create')))
            ->add('id')
			->add('description', FormType\TextType::class, array('label' => 'Descripción'))
			->add('save', FormType\SubmitType::class, array('label' => 'Guardar'))
            ->getForm();
		
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			
			try{
				
				$this->get('ks.core.role')->insert($role);
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
        $form = $this->createFormBuilder($role, array('validation_groups' => array('update')))
            ->add('description', FormType\TextType::class, array('label' => 'Descripción'))
			->add('save', FormType\SubmitType::class, array('label' => 'Guardar'))
            ->getForm();
		
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			
			try{
				
				$this->get('ks.core.role')->update($role);
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
				$this->get('ks.core.role')->delete($role);
			}
			
		} catch (\Exception $e) {
			$message = $this->handleException($e);
			return Ajax::responseResult($message);
		}
		
		return Ajax::responseOk();
	}
	
	
	/************************************************
     * 					PERMISSIONS
     ************************************************
	 */
	 
	private function getPermCrudConf()
	{
		$conf = array();
		$conf['name'] = 'roles_p';
		$conf['grants'] = $this->grants;
		$conf['urls'] = array(
			'list'		=> 'role_p_list',
			'create'	=> 'role_p_create',
			'edit'		=> 'role_p_edit',
			'delete'	=> 'role_p_delete',
			'export'	=> 'role_p_export'
		);
		$conf['dt'] = 'KsAdminLteThemeBundle:fragments:crud1_dt_role_p.html.twig';
		$conf['csv_filename'] = 'role_permissions_' . date('mdHis') . '.csv';
		$conf['csv_columns'] = array();
		$conf['csv_columns']['id'] = array('field' => 'id', 'title' => 'Id');
		$conf['csv_columns']['role'] = array('field' => 'role', 'title' => 'Rol');
		$conf['csv_columns']['ac'] = array('field' => 'ac', 'title' => 'Función');
		$conf['csv_columns']['read'] = array('field' => 'mask', 'title' => 'Lectura');
		$conf['csv_columns']['create'] = array('field' => 'mask', 'title' => 'Alta');
		$conf['csv_columns']['update'] = array('field' => 'mask', 'title' => 'Modificación');
		$conf['csv_columns']['delete'] = array('field' => 'mask', 'title' => 'Baja');
		$conf['csv_columns']['char_created'] = array('field' => 'char_created', 'title' => 'Fecha de creación');
		$conf['csv_columns']['char_updated'] = array('field' => 'char_updated', 'title' => 'Fecha de actualización');
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
     * @Route("/roles/permissions/view/{id}", name="role_permissions", defaults={"id" = 0})
     */
    public function permissionsAction(Request $request, $id)
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
		
		return $this->render('KsAdminLteThemeBundle::role_permissions_list.html.twig', array(
            'hdr' 	=> $hdr,
			'bc' 	=> $bc,
			'crud' 	=> $crud
        ));
    }
	
	/**
     * @Route("/roles/permissions/list/{id}", name="role_p_list", defaults={"id" = 0})
     */
    public function permListAction(Request $request)
    {
		$dt_report = $this->get('ks.core.dt_report');
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_VIEW')) return $dt_report->getDeniedResponse();
		
		$conf = $this->getPermCrudConf();
		return $dt_report->getList($this->getPermQuery(), $request->request, $conf['filters']);
    }
	
	/**
     * @Route("/roles/permissions/export", name="role_p_export")
     */
    public function permExportAction(Request $request)
    {
		// Access Control
		$this->getGrants();
		
		if (! $this->granted('MASK_VIEW')) return $this->render('KsAdminLteThemeBundle::denied.html.twig');
		
		$conf = $this->getPermCrudConf();
		return $this->get('ks.core.dt_report')->exportCsv(
			$this->getPermQuery(), 
			$request->query, 
			$conf['filters'], 
			$conf['csv_filename'], 
			$conf['csv_columns'], 
			array($this, 'translateCSV')
		);
    }
	
	private function getAvailableControlList($id)
	{
		$conn = $this->get('database_connection');
		$qb = $conn->createQueryBuilder();
		$qb->select('a.id, a.description');
		$qb->from('ks_ac', 'a');
		$qb->andWhere('a.id not in (
			select b.ac_id
			from ks_acl b
			where b.role_id = ?
		)');
		$qb->setParameter(0, $id);
		
		$records = $qb->execute()->fetchAll();
		
		$options = array();
		$options['Seleccione un valor'] = '';
		
		foreach ($records as $r)
			$options[$r['description']] = $r['id'];
			
		return $options;
	}
	
	/**
     * @Route("/roles/permissions/create/{id}", name="role_p_create", defaults={"id" = 0})
     */
    public function permCreateAction(Request $request, $id)
    {
		//Role
		$em = $this->getDoctrine()->getManager();
		$role = $em->getRepository('KsCoreBundle:Role')->find($id);
		
		// Page header
		$hdr = array('title' => 'Rol: ' . $role->getDescription(), 'small' => 'Agregar permisos');
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('route' => $this->get('router')->generate('roles'), 'description'=>'Roles');
		$bc[] = array('route' => $this->get('router')->generate('role_permissions', array('id' => $id)), 'description' => 'Permisos para '.$role->getDescription());
		$bc[] = array('description'=>'Agregar permiso');
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_CREATE')) 
			return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		// Form
		$acl = new AccessControlList();
		$acl->setRoleId($id);
		$acl->setMaskView(true);
		
        $form = $this->createFormBuilder($acl, array('validation_groups' => array('create')))
            ->add('role_id', FormType\HiddenType::class)
			->add('ac_id', FormType\ChoiceType::class, array('label' => 'Función', 'choices' => $this->getAvailableControlList($id), 'choices_as_values' => true))
			->add('mask_view', FormType\CheckboxType::class, array('label' => 'Lectura'))
			->add('mask_create', FormType\CheckboxType::class, array('label' => 'Alta'))
			->add('mask_edit', FormType\CheckboxType::class, array('label' => 'Modificación'))
			->add('mask_delete', FormType\CheckboxType::class, array('label' => 'Baja'))
			->add('save', FormType\SubmitType::class, array('label' => 'Guardar'))
            ->getForm();
		
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			
			try{
				
				$this->get('ks.core.role')->insertPermission($role, $acl);
				return $this->redirectToRoute('role_permissions', array('id' => $id));
				
			} catch (\Exception $e) {
				$message = $this->handleException($e);
				$this->get('session')->getFlashBag()->add('error', $message);
			}
		}
		
		return $this->render('KsAdminLteThemeBundle::role_permissions_create.html.twig', array(
            'hdr' 	=> $hdr,
			'bc' 	=> $bc, 
			'form' => $form->createView()
        ));
	}
	
	/**
     * @Route("/roles/permissions/edit/{id}", name="role_p_edit", defaults={"id" = 0})
     */
    public function permEditAction(Request $request, $id)
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
		$bc[] = array('route' => $this->get('router')->generate('role_permissions', array('id' => $acl->getRoleId())), 'description' => 'Permisos para '.$acl->getRole()->getDescription());
		$bc[] = array('description'=>'Editar permisos');
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_EDIT')) return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		// Form
		$form = $this->createFormBuilder($acl, array('validation_groups' => array('update')))
			->add('mask_view', FormType\CheckboxType::class, array('label' => 'Lectura'))
			->add('mask_create', FormType\CheckboxType::class, array('label' => 'Alta'))
			->add('mask_edit', FormType\CheckboxType::class, array('label' => 'Modificación'))
			->add('mask_delete', FormType\CheckboxType::class, array('label' => 'Baja'))
			->add('save', FormType\SubmitType::class, array('label' => 'Guardar'))
            ->getForm();
		
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			
			try{
				
				$this->get('ks.core.role')->updatePermission($acl);
				return $this->redirectToRoute('role_permissions', array('id' => $acl->getRoleId()));
				
			} catch (\Exception $e) {
				$message = $this->handleException($e);
				$this->get('session')->getFlashBag()->add('error', $message);
			}
		}
		
		return $this->render('KsAdminLteThemeBundle::role_permissions_edit.html.twig', array(
            'hdr' 	=> $hdr,
			'bc' 	=> $bc, 
			'form' 	=> $form->createView(),
			'acl' 	=> $acl
        ));
	}
	
	/**
     * @Route("/roles/permissions/delete", name="role_p_delete")
     */
    public function permDeleteAction(Request $request)
    {
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_DELETE')) return Ajax::responseDenied();
		
		$em = $this->getDoctrine()->getManager();
		
		try {
			
			foreach ($request->request->get('ids') as $id)
			{
				$acl = $em->getRepository('KsCoreBundle:AccessControlList')->find($id);
				$this->get('ks.core.role')->deletePermission($acl);
			}
			
		} catch (\Exception $e) {
			$message = $this->handleException($e);
			return Ajax::responseResult($message);
		}
		
		return Ajax::responseOk();
	}
}