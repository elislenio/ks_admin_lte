<?php
namespace Ks\AdminLteThemeBundle\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Ks\CoreBundle\Controller\BaseController;
use Ks\CoreBundle\Entity\User;
use Ks\CoreBundle\Entity\UserRole;
use Ks\CoreBundle\Classes\DbAbs;
use Ks\CoreBundle\Classes\Ajax;

class UsersController extends BaseController
{
	protected function getGrants()
    {
		parent::getAcGrants('USERS');
    }
	
	private function getCrudConf()
	{
		$conf = array();
		$conf['name'] = 'users';
		$conf['grants'] = $this->grants;
		$conf['urls'] = array(
			'list'		=> 'users_list',
			'create'	=> 'users_create',
			'edit'		=> 'users_edit',
			'delete'	=> 'users_delete',
			'export'	=> 'users_export',
			'roles'		=> 'user_roles',
			'pwdreset'	=> 'users_pwd_reset'
		);
		$conf['filters'] = array();
		$conf['filters']['username'] = array('filter'=>'username', 'label'=>'Usuario', 'field'=>'a.username', 'type'=>'text', 'condition'=>'contains');
		$conf['filters']['email'] = array('filter'=>'email', 'label'=>'Email', 'field'=>'a.email', 'type'=>'text');
		$conf['filters']['id'] = array('filter'=>'id', 'label'=>'Id', 'field'=>'a.id', 'type'=>'number');
		
		if ( $this->get('ks.core.ac')->localPasswordEnabled() ) 
			$conf['filters']['pwd_exp'] = array('filter'=>'pwd_exp', 'label'=>'Contraseña expirada', 'field'=>'a.password_expired', 'type'=>'number', 'condition'=>'eq', 'input_type'=>'bool1');
		
		return $conf;
	}
	
	private function getQuery()
	{
		$conn = $this->get('doctrine.dbal.default_connection');
		$qb = $conn
			->createQueryBuilder()
			->select('a.id', 'a.email', 'a.username', 'a.first_name', 'a.last_name', 
				'a.enabled', 'a.password_expired', 
				'a.locked', 'a.created', 'a.updated', 'a.last_login', 
				DbAbs::longDatetime($conn, 'a.created') . " char_created", 
				DbAbs::longDatetime($conn, 'a.updated') . " char_updated",
				DbAbs::longDatetime($conn, 'a.last_login') . " char_last_login"
				)
			->from('ks_user', 'a');
		return $qb;
	}
	
	/**
     * @Route("/users", name="users")
     */
    public function indexAction(Request $request)
    {
		// Page header
		$hdr = array('title' => 'Usuarios', 'small' => '');
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('description'=>'Usuarios');
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_VIEW')) 
			return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		// Config
		$crud = $this->getCrudConf();
		
		return $this->render('KsAdminLteThemeBundle::user_list.html.twig', array(
            'hdr' 	=> $hdr,
			'bc' 	=> $bc,
			'crud' 	=> $crud
        ));
    }
	
	/**
     * @Route("/users/list", name="users_list")
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
     * @Route("/users/export", name="users_export")
     */
    public function exportAction(Request $request)
    {
		// Page header
		$hdr = array('title' => 'Usuarios', 'small' => '');
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('route' => $this->get('router')->generate('users'), 'description'=>'Usuarios');
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_VIEW')) 
			return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		$conf = $this->getCrudConf();
		$csv_filename = 'usuarios_' . date('mdHis') . '.csv';
		$csv_columns = array();
		$csv_columns['id'] = array('field' => 'id', 'title' => 'Id');
		$csv_columns['username'] = array('field' => 'username', 'title' => 'Usuario');
		$csv_columns['email'] = array('field' => 'email', 'title' => 'Email');
		$csv_columns['first_name'] = array('field' => 'first_name', 'title' => 'Nombre');
		$csv_columns['last_name'] = array('field' => 'last_name', 'title' => 'Apellido');
		$csv_columns['enabled'] = array('field' => 'enabled', 'title' => 'Habilitado');
		$csv_columns['locked'] = array('field' => 'locked', 'title' => 'Bloqueado');
		$csv_columns['char_created'] = array('field' => 'char_created', 'title' => 'Fecha de creación');
		$csv_columns['char_updated'] = array('field' => 'char_updated', 'title' => 'Fecha de actualización');
		
		if ( $this->get('ks.core.ac')->localPasswordEnabled() ) 
			$csv_columns['password_expired'] = array('field' => 'password_expired', 'title' => 'Contraseña expirada');
		
		return $this->get('ks.core.dt_report')->exportCsv(
			$this->getQuery(), 
			$request->query, 
			$conf['filters'], 
			$csv_filename,
			$csv_columns
		);
    }
	
	/**
     * @Route("/users/create", name="users_create")
     */
    public function createAction(Request $request)
    {
		// Page header
		$hdr = array('title' => 'Nuevo Usuario', 'small' => '');
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('route' => $this->get('router')->generate('users'), 'description'=>'Usuarios');
		$bc[] = array('description'=>'Nuevo');
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_CREATE')) return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		// Form
		$user = new User();
		$form = $this->get('ks.core.user_model')->getFormCreate($user);
		$form->handleRequest($request);
		
		if ($form->isSubmitted() && $form->isValid()) {
			
			try{
				
				$this->get('ks.core.user_model')->insert($user);
				return $this->redirectToRoute('users');
				
			} catch (\Exception $e) {
				$message = $this->handleException($e);
				$this->get('session')->getFlashBag()->add('error', $message);
			}
		}
		
		return $this->render('KsAdminLteThemeBundle::user_create.html.twig', array(
            'hdr' 	=> $hdr,
			'bc' 	=> $bc,
			'form' 	=> $form->createView()
        ));
	}
	
	/**
     * @Route("/users/edit/{id}", name="users_edit", defaults={"id" = 0})
     */
    public function editAction(Request $request, $id)
    {
		// User
		$em = $this->getDoctrine()->getManager();
		$user = $em->getRepository('KsCoreBundle:User')->find($id);
		
		// Page header
		$hdr = array('title' => 'Editar Usuario', 'small' => 'Id: ' . $user->getId());
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('route' => $this->get('router')->generate('users'), 'description'=>'Usuarios');
		$bc[] = array('description'=>'Editar');
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_EDIT')) 
			return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		// Form
		$form = $this->get('ks.core.user_model')->getFormEdit($user);
		$form->handleRequest($request);
		
		if ($form->isSubmitted() && $form->isValid()) {
			
			try{
				
				$this->get('ks.core.user_model')->update($user);
				return $this->redirectToRoute('users');
				
			} catch (\Exception $e) {
				$message = $this->handleException($e);
				$this->get('session')->getFlashBag()->add('error', $message);
			}
		}
		
		return $this->render('KsAdminLteThemeBundle::user_edit.html.twig', array(
            'hdr' 	=> $hdr,
			'bc' 	=> $bc,
			'form' 	=> $form->createView()
        ));
	}
	
	/**
     * @Route("/users/delete", name="users_delete")
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
				$user = $em->getRepository('KsCoreBundle:User')->find($id);
				$this->get('ks.core.user_model')->delete($user);
			}
			
		} catch (\Exception $e) {
			$message = $this->handleException($e);
			return Ajax::responseResult($message);
		}
		
		return Ajax::responseOk();
	}
	
	
	/************************************************
     * 					PASSWORD
     ************************************************
	 */
	 
	/**
     * @Route("/users/pwdgen", name="users_pwd_gen")
     */
    public function pwdGenerateAction(Request $request)
    {
		// Access Control
		$this->getGrants();
		if (! ( $this->get('ks.core.ac')->localPasswordEnabled() && $this->granted('MASK_EDIT') ) )
			return Ajax::responseDenied();
		
		// Get a random password
		$pwd = $this->get('ks.core.ac')->genPassword();
		
		$response = array();
		$response['pwd'] = $pwd;
		return Ajax::responseOk($response);
    }
	
	/**
     * @Route("/users/pwdreset/{id}", name="users_pwd_reset", defaults={"id" = 0})
     */
    public function pwdResetAction(Request $request, $id)
    {
		// User
		$em = $this->getDoctrine()->getManager();
		$user = $em->getRepository('KsCoreBundle:User')->find($id);
		
		// Page header
		$hdr = array('title' => 'Restablecer contraseña', 'small' => $user->getUsername());
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('route' => $this->get('router')->generate('users'), 'description'=>'Usuarios');
		$bc[] = array('description'=>'Restablecer contraseña');
		
		// Access Control
		$this->getGrants();
		if (! ( $this->get('ks.core.ac')->localPasswordEnabled() && $this->granted('MASK_EDIT') ) ) 
			return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		// Form
		$form = $this->get('ks.core.user_model')->getFormPwdReset($user);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			
			try{
				
				$this->get('ks.core.user_model')->resetPwd($user);
				return $this->redirectToRoute('users');
				
			} catch (\Exception $e) {
				$message = $this->handleException($e);
				$this->get('session')->getFlashBag()->add('error', $message);
			}
		}
	
        return $this->render('KsAdminLteThemeBundle::user_pwd_reset.html.twig', array(
            'hdr' 	=> $hdr,
			'bc' 	=> $bc,
			'form' 	=> $form->createView()
        ));
    }
	
	
	/************************************************
     * 						ROLES
     ************************************************
	 */
	
	private function getRolesCrudConf()
	{
		$conf = array();
		$conf['name'] = 'user_roles';
		$conf['grants'] = $this->grants;
		$conf['urls'] = array(
			'list'		=> 'user_roles_list',
			'create'	=> 'user_roles_create',
			'delete'	=> 'user_roles_delete',
			'export'	=> 'user_roles_export'
		);
		$conf['filters'] = array();
		$conf['filters']['user'] = array('filter'=>'user', 'label'=>'Usuario', 'field'=>'a.user_id', 'type'=>'number', 'condition'=>'eq', 'hidden' => true);
		$conf['filters']['role'] = array('filter'=>'role', 'label'=>'Rol', 'field'=>'c.description', 'type'=>'text', 'condition'=>'contains');
		return $conf;
	}
	
	private function getRolesQuery()
	{
		$conn = $this->get('doctrine.dbal.default_connection');
		$qb = $conn
			->createQueryBuilder()
			->select('a.id', 'a.user_id', 'a.role_id', 'b.username as user', 'c.description as role', 'assigned', 
				DbAbs::longDatetime($conn, 'a.assigned') . " char_assigned")
			->from('ks_user_role', 'a')
			->innerJoin('a', 'ks_user', 'b', 'a.user_id = b.id')
			->innerJoin('a', 'ks_role', 'c', 'a.role_id = c.id');
		
		return $qb;
	}
	
	/**
     * @Route("/users/roles/view/{id}", name="user_roles", defaults={"id" = 0})
     */
    public function rolesAction(Request $request, $id)
    {
		// User
		$em = $this->getDoctrine()->getManager();
		$user = $em->getRepository('KsCoreBundle:User')->find($id);
		
		// Page header
		$hdr = array('title' => 'Roles de ' . $user->getUsername(), 'small' => '');
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('route' => $this->get('router')->generate('users'), 'description'=>'Usuarios');
		$bc[] = array('description'=> $user->getUsername());
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_VIEW')) return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		// Conf
		$crud = $this->getRolesCrudConf();
		
		// Sets the value for the hidden field
		$crud['filters']['user']['value'] = $id;
		$crud['url_param'] = array();
		$crud['url_param']['create'] = $id;
		
		return $this->render('KsAdminLteThemeBundle::user_roles_list.html.twig', array(
            'hdr' 	=> $hdr,
			'bc' 	=> $bc,
			'crud' 	=> $crud
        ));
    }
	
	/**
     * @Route("/users/roles/list", name="user_roles_list")
     */
    public function roleListAction(Request $request)
    {
		$dt_report = $this->get('ks.core.dt_report');
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_VIEW')) return $dt_report->getDeniedResponse();
		
		$conf = $this->getRolesCrudConf();
		return $dt_report->getList($this->getRolesQuery(), $request->request, $conf['filters']);
    }
	
	/**
     * @Route("/users/roles/export", name="user_roles_export")
     */
    public function rolesExportAction(Request $request)
    {
		// User
		$user_id = $this->get('ks.core.dt_report')->getExtraSearchValue($request, 'f_user');
				
		$em = $this->getDoctrine()->getManager();
		$user = $em->getRepository('KsCoreBundle:User')->find($user_id);
		
		// Page header
		$hdr = array('title' => 'Roles de ' . $user->getUsername(), 'small' => '');
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('route' => $this->get('router')->generate('users'), 'description'=>'Usuarios');
		$bc[] = array('route' => $this->get('router')->generate('user_roles', array('id' => $user_id)), 'description' => $user->getUsername());
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_VIEW')) return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		$conf = $this->getRolesCrudConf();
		$csv_filename = 'usuario_roles_' . date('mdHis') . '.csv';
		$csv_columns = array();
		$csv_columns['id'] = array('field' => 'id', 'title' => 'Id');
		$csv_columns['user'] = array('field' => 'user', 'title' => 'Usuario');
		$csv_columns['role'] = array('field' => 'role', 'title' => 'Rol');
		$csv_columns['char_assigned'] = array('field' => 'char_assigned', 'title' => 'Fecha de asignación');
		
		return $this->get('ks.core.dt_report')->exportCsv(
			$this->getRolesQuery(), 
			$request->query, 
			$conf['filters'], 
			$csv_filename, 
			$csv_columns
		);
    }
	
	/**
     * @Route("/users/roles/assign/{id}", name="user_roles_create", defaults={"id" = 0})
     */
    public function roleAssignAction(Request $request, $id)
    {
		// User
		$em = $this->getDoctrine()->getManager();
		$user = $em->getRepository('KsCoreBundle:User')->find($id);
		
		// Page header
		$hdr = array('title' => 'Usuario: ' . $user->getUsername(), 'small' => 'Agregar rol');
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('route' => $this->get('router')->generate('users'), 'description'=>'Usuarios');
		$bc[] = array('route' => $this->get('router')->generate('user_roles', array('id' => $id)), 'description' => 'Roles de ' . $user->getUsername());
		$bc[] = array('description'=>'Agregar rol');
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_CREATE')) 
			return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		// Form
		$user_role = new UserRole();
		$user_role->setUserId($user->getId());
		$form = $this->get('ks.core.user_model')->getFormRoleAssign($user_role);
		$form->handleRequest($request);
		
		if ($form->isSubmitted() && $form->isValid()) {
			
			try{
				
				$this->get('ks.core.user_model')->insertRole($user_role);
				return $this->redirectToRoute('user_roles', array('id' => $id));
				
			} catch (\Exception $e) {
				$message = $this->handleException($e);
				$this->get('session')->getFlashBag()->add('error', $message);
			}
		}
		
		return $this->render('KsAdminLteThemeBundle::user_roles_create.html.twig', array(
            'hdr' 	=> $hdr,
			'bc' 	=> $bc, 
			'form' 	=> $form->createView()
        ));
	}
	
	/**
     * @Route("/users/roles/delete", name="user_roles_delete")
     */
    public function roleDeleteAction(Request $request)
    {
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_DELETE')) return Ajax::responseDenied();
		
		$em = $this->getDoctrine()->getManager();
		
		try {
			
			foreach ($request->request->get('ids') as $id)
			{
				$ur = $em->getRepository('KsCoreBundle:UserRole')->find($id);
				$this->get('ks.core.user_model')->deleteRole($ur);
			}
			
		} catch (\Exception $e) {
			$message = $this->handleException($e);
			return Ajax::responseResult($message);
		}
		
		return Ajax::responseOk();
	}
}