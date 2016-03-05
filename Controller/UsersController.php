<?php
namespace Ks\AdminLteThemeBundle\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type as FormType;
use Doctrine\DBAL\Types\Type;
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
	
	private function getCrud1Conf()
	{
		$engine = $this->getDbEngine();
		
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
		$conf['dt'] = 'KsAdminLteThemeBundle:fragments:crud1_dt_users.html.twig';
		$conf['csv_filename'] = 'users_' . date('mdHis') . '.csv';
		$conf['csv_columns'] = array();
		$conf['csv_columns']['id'] = array('field' => 'id', 'title' => 'Id');
		$conf['csv_columns']['username'] = array('field' => 'username', 'title' => 'Usuario');
		$conf['csv_columns']['email'] = array('field' => 'email', 'title' => 'Email');
		$conf['csv_columns']['first_name'] = array('field' => 'first_name', 'title' => 'Nombre');
		$conf['csv_columns']['last_name'] = array('field' => 'last_name', 'title' => 'Apellido');
		$conf['csv_columns']['enabled'] = array('field' => 'enabled', 'title' => 'Habilitado');
		$conf['csv_columns']['locked'] = array('field' => 'locked', 'title' => 'Bloqueado');
		$conf['csv_columns']['char_created'] = array('field' => 'char_created', 'title' => 'Fecha de creación');
		$conf['csv_columns']['char_updated'] = array('field' => 'char_updated', 'title' => 'Fecha de actualización');
		$conf['sql'] = array();
		$conf['sql']['select'] = array('a.id', 'a.email', 'a.username', 'a.first_name', 'a.last_name', 'a.enabled', 'a.password_expired', 'a.locked', 'a.created', 'a.updated', DbAbs::longDatetime($engine, 'a.created') . " char_created", DbAbs::longDatetime($engine, 'a.updated') . " char_updated");
		$conf['sql']['from'] = array('ks_user', 'a');
		$conf['filters'] = array();
		$conf['filters']['username'] = array('filter'=>'username', 'label'=>'Usuario', 'field'=>'a.username', 'type'=>'text', 'condition'=>'contains');
		$conf['filters']['email'] = array('filter'=>'email', 'label'=>'Email', 'field'=>'a.email', 'type'=>'text');
		$conf['filters']['id'] = array('filter'=>'id', 'label'=>'Id', 'field'=>'a.id', 'type'=>'number');
		
		if ( $this->get('ks.core.ac')->localPasswordEnabled() ) 
		{
			$conf['csv_columns']['password_expired'] = array('field' => 'password_expired', 'title' => 'Contraseña expirada');
			$conf['filters']['pwd_exp'] = array('filter'=>'pwd_exp', 'label'=>'Contraseña expirada', 'field'=>'a.password_expired', 'type'=>'number', 'condition'=>'eq', 'input_type'=>'bool1');
		}
		
		return $conf;
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
		$crud = $this->getCrud1Conf();
		
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
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_VIEW')) return $this->get('ks.core.crud1')->getDeniedResponse();
		
		$conn = $this->get('doctrine.dbal.default_connection');
		return $this->get('ks.core.crud1')->getList($conn, $request->request, $this->getCrud1Conf());
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
		
		$conn = $this->get('doctrine.dbal.default_connection');
		return $this->get('ks.core.crud1')->exportCsv($conn, $request->query, $this->getCrud1Conf());
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
		$form = $this->get('ks.core.user')->getFormCreate();
		$form->handleRequest($request);
		
		if ($form->isSubmitted() && $form->isValid()) {
			
			try{
				
				$this->get('ks.core.user')->insert();
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
		$form = $this->get('ks.core.user')->getFormEdit($user);
		$form->handleRequest($request);
		
		if ($form->isSubmitted() && $form->isValid()) {
			
			try{
				
				$this->get('ks.core.user')->update();
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
				$this->get('ks.core.user')->delete($user);
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
		
		// Get a secure random number, then hash and substr
		$random = random_bytes(10);
		$pwd = mb_substr(rtrim(strtr(base64_encode($random), '+/', '-_'), '='),0,8);
		
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
		$form = $this->get('ks.core.user')->getFormPwdReset($user);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			
			try{
				
				$this->get('ks.core.user')->resetPwd();
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
	
	private function getRolesCrud1Conf()
	{
		$engine = $this->getDbEngine();
		
		$conf = array();
		$conf['name'] = 'user_roles';
		$conf['grants'] = $this->grants;
		$conf['urls'] = array(
			'list'		=> 'user_roles_list',
			'create'	=> 'user_roles_create',
			'delete'	=> 'user_roles_delete',
			'export'	=> 'user_roles_export'
		);
		$conf['dt'] = 'KsAdminLteThemeBundle:fragments:crud1_dt_user_roles.html.twig';
		$conf['csv_filename'] = 'user_roles_' . date('mdHis') . '.csv';
		$conf['csv_columns'] = array();
		$conf['csv_columns']['id'] = array('field' => 'id', 'title' => 'Id');
		$conf['csv_columns']['user'] = array('field' => 'user', 'title' => 'Usuario');
		$conf['csv_columns']['role'] = array('field' => 'role', 'title' => 'Rol');
		$conf['csv_columns']['char_assigned'] = array('field' => 'char_assigned', 'title' => 'Fecha de asignación');
		$conf['sql'] = array();
		$conf['sql']['select'] = array('a.id', 'a.user_id', 'a.role_id', 'b.username as user', 'c.description as role', 'assigned', DbAbs::longDatetime($engine, 'a.assigned') . " char_assigned");
		$conf['sql']['from'] = array('ks_user_role', 'a');
		$conf['sql']['innerJoin'] = array();
		$conf['sql']['innerJoin'][] = array('a', 'ks_user', 'b', 'a.user_id = b.id');
		$conf['sql']['innerJoin'][] = array('a', 'ks_role', 'c', 'a.role_id = c.id');
		$conf['filters'] = array();
		$conf['filters']['user'] = array('filter'=>'user', 'label'=>'Usuario', 'field'=>'a.user_id', 'type'=>'number', 'condition'=>'eq', 'hidden' => true);
		$conf['filters']['role'] = array('filter'=>'role', 'label'=>'Rol', 'field'=>'c.description', 'type'=>'text', 'condition'=>'contains');
		return $conf;
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
		$crud = $this->getRolesCrud1Conf();
		
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
     * @Route("/users/roles/list/{id}", name="user_roles_list", defaults={"id" = 0})
     */
    public function roleListAction(Request $request)
    {
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_VIEW')) return $this->get('ks.core.crud1')->getDeniedResponse();
		
		$conn = $this->get('doctrine.dbal.default_connection');
		return $this->get('ks.core.crud1')->getList($conn, $request->request, $this->getRolesCrud1Conf());
    }
	
	/**
     * @Route("/users/roles/export", name="user_roles_export")
     */
    public function rolesExportAction(Request $request)
    {
		// User
		$user_id = $this->get('ks.core.crud1')->getExtraSearchValue($request, 'f_user');
				
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
		
		$conn = $this->get('doctrine.dbal.default_connection');
		return $this->get('ks.core.crud1')->exportCsv(
			$conn,
			$request->query, 
			$this->getRolesCrud1Conf()
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
		$form = $this->get('ks.core.user_role')->getFormCreate($user);
		$form->handleRequest($request);
		
		if ($form->isSubmitted() && $form->isValid()) {
			
			try{
				
				$this->get('ks.core.user_role')->insert();
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
				$this->get('ks.core.user_role')->delete($ur);
			}
			
		} catch (\Exception $e) {
			$message = $this->handleException($e);
			return Ajax::responseResult($message);
		}
		
		return Ajax::responseOk();
	}
}