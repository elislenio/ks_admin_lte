<?php
namespace Ks\AdminLteThemeBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Ks\CoreBundle\Entity\AccessControl;
use Ks\CoreBundle\Entity\Menu;
use Ks\CoreBundle\Entity\MenuItem;
use Ks\CoreBundle\Entity\Role;
use Ks\CoreBundle\Entity\AccessControlList;
use Ks\CoreBundle\Entity\User;
use Ks\CoreBundle\Entity\UserRole;
use Ks\CoreBundle\Entity\Parameter;

class LoadData extends AbstractFixture implements OrderedFixtureInterface, ContainerAwareInterface
{
	/**
     * @var ContainerInterface
     */
    private $container;
	private $em;
	
	
	public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

	
    public function getOrder()
    {
        return 1;
    }
	
	
	private function loadFunctions()
	{
		$ac_model = $this->container->get('ks.core.ac_model');
		
		// PARAMETERS
		$parameters = new AccessControl();
		$parameters
			->setId('PARAMETERS')
			->setDescription('Administración de Parametros');
		$ac_model->insert($parameters);
		
		// ACCESS_CONTROL
		$access_control = new AccessControl();
		$access_control
			->setId('ACCESS_CONTROL')
			->setDescription('Administración de Funciones');
		$ac_model->insert($access_control);
		
		// MENUS
		$menus = new AccessControl();
		$menus
			->setId('MENUS')
			->setDescription('Administración de los menus de la aplicación');
		$ac_model->insert($menus);
		
		// REP_ACL
		$rep_acl = new AccessControl();
		$rep_acl
			->setId('REP_ACL')
			->setDescription('Reporte de permisos de acceso');
		$ac_model->insert($rep_acl);
		
		// REP_AUDIT
		$rep_audit = new AccessControl();
		$rep_audit
			->setId('REP_AUDIT')
			->setDescription('Reporte de auditoria');
		$ac_model->insert($rep_audit);
		
		// REP_USERS_ROLES
		$rep_users_roles = new AccessControl();
		$rep_users_roles
			->setId('REP_USERS_ROLES')
			->setDescription('Reporte de usuarios y roles');
		$ac_model->insert($rep_users_roles);
		
		// ROLES
		$roles = new AccessControl();
		$roles
			->setId('ROLES')
			->setDescription('Administración de Roles');
		$ac_model->insert($roles);
		
		// USERS
		$users = new AccessControl();
		$users
			->setId('USERS')
			->setDescription('Administración de Usuarios');
		$this->container->get('ks.core.ac_model')->insert($users);
	}
	
	
	private function loadMenu()
	{
		$menu_model = $this->container->get('ks.core.menu_model');
		
		// MAIN Menu
		$main = new Menu();
		$main
			->setId('MAIN')
			->setName('Menu principal');
		$menu_model->insert($main);
		
		$root = $this->em->getRepository('KsCoreBundle:MenuItem')->getRootItem($main->getId());
		
		// Security
		$security = new MenuItem();
		$security
			->setMenuId($main->getId())
			->setParentId($root->getId())
			->setLabel('Seguridad')
			->setRoute(null)
			->setItemOrder(1000)
			->setIcon('fa fa-lock fa-fw')
			->setIsBranch(true)
			->setVisible(true)
			->setAcId(null)
			->setMask(null)
			;
		$menu_model->insertItem($main, $security);
		
			// Usuarios
			$usuarios = new MenuItem();
			$usuarios
				->setMenuId($main->getId())
				->setParentId($security->getId())
				->setLabel('Usuarios')
				->setRoute('users')
				->setItemOrder(1)
				->setIcon('fa fa-user fa-fw')
				->setIsBranch(false)
				->setVisible(true)
				->setAcId('USERS')
				->setMask(MaskBuilder::MASK_VIEW)
				;
			$menu_model->insertItem($main, $usuarios);
		
			// Roles
			$roles = new MenuItem();
			$roles
				->setMenuId($main->getId())
				->setParentId($security->getId())
				->setLabel('Roles')
				->setRoute('roles')
				->setItemOrder(2)
				->setIcon('fa fa-group fa-fw')
				->setIsBranch(false)
				->setVisible(true)
				->setAcId('ROLES')
				->setMask(MaskBuilder::MASK_VIEW)
				;
			$menu_model->insertItem($main, $roles);
		
			// Reportes
			$reportes = new MenuItem();
			$reportes
				->setMenuId($main->getId())
				->setParentId($security->getId())
				->setLabel('Reportes')
				->setRoute(null)
				->setItemOrder(3)
				->setIcon('fa fa-database fa-fw')
				->setIsBranch(true)
				->setVisible(true)
				->setAcId(null)
				->setMask(null)
				;
			$menu_model->insertItem($main, $reportes);
		
				// Permisos
				$permisos = new MenuItem();
				$permisos
					->setMenuId($main->getId())
					->setParentId($reportes->getId())
					->setLabel('Permisos')
					->setRoute('acls')
					->setItemOrder(1)
					->setIcon('fa fa-key fa-fw')
					->setIsBranch(false)
					->setVisible(true)
					->setAcId('REP_ACL')
					->setMask(MaskBuilder::MASK_VIEW)
					;
				$menu_model->insertItem($main, $permisos);
		
				// Usuarios y Roles
				$user_roles = new MenuItem();
				$user_roles
					->setMenuId($main->getId())
					->setParentId($reportes->getId())
					->setLabel('Usuarios y Roles')
					->setRoute('rep_users_roles')
					->setItemOrder(2)
					->setIcon('fa fa-group fa-fw')
					->setIsBranch(false)
					->setVisible(true)
					->setAcId('REP_USERS_ROLES')
					->setMask(MaskBuilder::MASK_VIEW)
					;
				$menu_model->insertItem($main, $user_roles);
		
				// Reporte de Auditoría
				$rep_audit = new MenuItem();
				$rep_audit
					->setMenuId($main->getId())
					->setParentId($reportes->getId())
					->setLabel('Reporte de Auditoría')
					->setRoute('rep_audit')
					->setItemOrder(3)
					->setIcon('fa fa-eye fa-fw')
					->setIsBranch(false)
					->setVisible(true)
					->setAcId('REP_AUDIT')
					->setMask(MaskBuilder::MASK_VIEW)
					;
				$menu_model->insertItem($main, $rep_audit);
		
			// Configuración
			$config = new MenuItem();
			$config
				->setMenuId($main->getId())
				->setParentId($root->getId())
				->setLabel('Configuración')
				->setRoute(null)
				->setItemOrder(1001)
				->setIcon('fa fa-gear fa-fw')
				->setIsBranch(true)
				->setVisible(true)
				->setAcId(null)
				->setMask(null)
				;
			$menu_model->insertItem($main, $config);
		
				// Parametros
				$parametros = new MenuItem();
				$parametros
					->setMenuId($main->getId())
					->setParentId($config->getId())
					->setLabel('Parametros')
					->setRoute('parameters')
					->setItemOrder(1)
					->setIcon('fa fa-wrench fa-fw')
					->setIsBranch(false)
					->setVisible(true)
					->setAcId('PARAMETERS')
					->setMask(MaskBuilder::MASK_VIEW)
					;
				$menu_model->insertItem($main, $parametros);
		
				// Menus
				$menus = new MenuItem();
				$menus
					->setMenuId($main->getId())
					->setParentId($config->getId())
					->setLabel('Menus')
					->setRoute('menus')
					->setItemOrder(2)
					->setIcon('fa fa-list fa-fw')
					->setIsBranch(false)
					->setVisible(true)
					->setAcId('MENUS')
					->setMask(MaskBuilder::MASK_VIEW)
					;
				$menu_model->insertItem($main, $menus);
		
				// Funciones
				$funciones = new MenuItem();
				$funciones
					->setMenuId($main->getId())
					->setParentId($config->getId())
					->setLabel('Funciones')
					->setRoute('acs')
					->setItemOrder(3)
					->setIcon('fa fa-circle fa-fw')
					->setIsBranch(false)
					->setVisible(true)
					->setAcId('ACCESS_CONTROL')
					->setMask(MaskBuilder::MASK_VIEW)
					;
				$menu_model->insertItem($main, $funciones);
		
		// References
		$this->addReference('menu-main', $main);
		$this->addReference('menu-main-root', $root);
	}
	
	
	private function loadRoles()
	{
		$role_model = $this->container->get('ks.core.role_model');
		
		$admin = new Role();
		$admin
			->setId('ROLE_ADMIN')
			->setDescription('Administrador');
		$role_model->insert($admin);
		
		$this->addReference('role-admin', $admin);
		
		// **************************
		// ACLs
		// **************************
		
		// ACCESS_CONTROL
		$acl_admin_ac = new AccessControlList();
		$acl_admin_ac
			->setRoleId($admin->getId())
			->setAcId('ACCESS_CONTROL')
			->setMaskView(true)
			->setMaskCreate(true)
			->setMaskEdit(true)
			->setMaskDelete(true);
		$role_model->insertAcl($admin, $acl_admin_ac);
		
		// MENUS
		$acl_admin_menus = new AccessControlList();
		$acl_admin_menus
			->setRoleId($admin->getId())
			->setAcId('MENUS')
			->setMaskView(true)
			->setMaskCreate(true)
			->setMaskEdit(true)
			->setMaskDelete(true);
		$role_model->insertAcl($admin, $acl_admin_menus);
		
		// ROLES
		$acl_admin_roles = new AccessControlList();
		$acl_admin_roles
			->setRoleId($admin->getId())
			->setAcId('ROLES')
			->setMaskView(true)
			->setMaskCreate(true)
			->setMaskEdit(true)
			->setMaskDelete(true);
		$role_model->insertAcl($admin, $acl_admin_roles);
		
		// USERS
		$acl_admin_users = new AccessControlList();
		$acl_admin_users
			->setRoleId($admin->getId())
			->setAcId('USERS')
			->setMaskView(true)
			->setMaskCreate(true)
			->setMaskEdit(true)
			->setMaskDelete(true);
		$role_model->insertAcl($admin, $acl_admin_users);
		
		// REP_AUDIT
		$acl_admin_repaudit = new AccessControlList();
		$acl_admin_repaudit
			->setRoleId($admin->getId())
			->setAcId('REP_AUDIT')
			->setMaskView(true)
			->setMaskCreate(false)
			->setMaskEdit(false)
			->setMaskDelete(false);
		$role_model->insertAcl($admin, $acl_admin_repaudit);
		
		// REP_ACL
		$acl_admin_repacl = new AccessControlList();
		$acl_admin_repacl
			->setRoleId($admin->getId())
			->setAcId('REP_ACL')
			->setMaskView(true)
			->setMaskCreate(false)
			->setMaskEdit(false)
			->setMaskDelete(false);
		$role_model->insertAcl($admin, $acl_admin_repacl);
		
		// REP_USERS_ROLES
		$acl_admin_repur = new AccessControlList();
		$acl_admin_repur
			->setRoleId($admin->getId())
			->setAcId('REP_USERS_ROLES')
			->setMaskView(true)
			->setMaskCreate(false)
			->setMaskEdit(false)
			->setMaskDelete(false);
		$role_model->insertAcl($admin, $acl_admin_repur);
		
		// PARAMETERS
		$acl_admin_params = new AccessControlList();
		$acl_admin_params
			->setRoleId($admin->getId())
			->setAcId('PARAMETERS')
			->setMaskView(true)
			->setMaskCreate(true)
			->setMaskEdit(true)
			->setMaskDelete(true);
		$role_model->insertAcl($admin, $acl_admin_params);
	}
	
	
	private function loadUsers()
	{
		$user_model = $this->container->get('ks.core.user_model');
		
		$admin = new User();
		$admin
			->setUsername('admin')
			->setEmail('admin@localhost.com');
		
		if ($this->container->get('ks.core.ac')->localPasswordEnabled())
		{
			$admin
				->setGeneratedPassword('Ch@ng3_m3')
				->setPasswordExpired(true);
		}
		
		$user_model->insert($admin);
		
		$admin_role_1 = new UserRole();
		$admin_role_1
			->setRoleId('ROLE_ADMIN')
			->setUserId($admin->getId());
		$user_model->insertRole($admin_role_1);
	}
	
	
	private function loadParameters()
	{
		
	}
	
	
    public function load(ObjectManager $manager)
    {
		$this->em = $this->container->get('doctrine')->getManager();
		
		$this->em->getConnection()->beginTransaction();
		
		try {
			
			// Functions
			$this->loadFunctions();
			// Menu
			$this->loadMenu();
			// Roles
			$this->loadRoles();
			// Users
			$this->loadUsers();
			// Parameters
			$this->loadParameters();
			
			$this->em->getConnection()->commit();
			
		} catch (Exception $e) {
			$this->em->getConnection()->rollBack();
			throw $e;
		}
    }
}