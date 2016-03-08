<?php
namespace Ks\AdminLteThemeBundle\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Form\Extension\Core\Type as FormType;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Knp\Menu\Matcher\Matcher;
use Knp\Menu\Matcher\Voter\RegexVoter;
use Knp\Menu\Matcher\Voter\UriVoter;
use Knp\Menu\Renderer\TwigRenderer;
use Ks\CoreBundle\Controller\BaseController;
use Ks\CoreBundle\Entity\Menu;
use Ks\CoreBundle\Entity\MenuItem;
use Ks\CoreBundle\Classes\DbAbs;
use Ks\CoreBundle\Classes\Ajax;

class MenuController extends BaseController
{
	protected function getGrants()
    {
		parent::getAcGrants('MENUS');
    }
	
	private function getCrudConf()
	{
		$conf = array();
		$conf['name'] = 'menus';
		$conf['grants'] = $this->grants;
		$conf['urls'] = array(
			'list'		=> 'menus_list',
			'create'	=> 'menus_create',
			'edit'		=> 'menus_edit',
			'delete'	=> 'menus_delete',
			'export'	=> 'menus_export',
			'items'		=> 'menu_items'
		);
		$conf['dt'] = 'KsAdminLteThemeBundle:fragments:crud1_dt_menus.html.twig';
		$conf['csv_filename'] = 'menus_' . date('mdHis') . '.csv';
		$conf['csv_columns'] = array();
		$conf['csv_columns']['id'] = array('field' => 'id', 'title' => 'Id');
		$conf['csv_columns']['name'] = array('field' => 'name', 'title' => 'Nombre');
		$conf['csv_columns']['char_created'] = array('field' => 'char_created', 'title' => 'Fecha de creación');
		$conf['csv_columns']['char_updated'] = array('field' => 'char_updated', 'title' => 'Fecha de actualización');
		$conf['filters']['name'] = array('filter'=>'name', 'label'=>'Nombre', 'field'=>'a.name', 'type'=>'text', 'condition'=>'contains');
		$conf['filters']['id'] = array('filter'=>'id', 'label'=>'Id', 'field'=>'a.id', 'type'=>'text');
		return $conf;
	}
	
	private function getQuery()
	{
		$conn = $this->get('doctrine.dbal.default_connection');
		$qb = $conn
			->createQueryBuilder()
			->select('id', 'name', 'created', 'updated', 
				DbAbs::longDatetime($conn, 'created') . " char_created", 
				DbAbs::longDatetime($conn, 'updated') . " char_updated")
			->from('ks_menu', 'a');
		return $qb;
	}
	
	/**
     * @Route("/menus", name="menus")
     */
    public function indexAction(Request $request)
    {
		// Page header
		$hdr = array('title' => 'Menus', 'small' => '');
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('description'=>'Menus');
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_VIEW')) return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		// Config
		$crud = $this->getCrudConf();
		
		return $this->render('KsAdminLteThemeBundle::menu_list.html.twig', array(
            'hdr' 	=> $hdr,
			'bc' 	=> $bc,
			'crud' 	=> $crud
        ));
    }
	
	/**
     * @Route("/menus/list", name="menus_list")
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
     * @Route("/menus/export", name="menus_export")
     */
    public function exportAction(Request $request)
    {
		// Page header
		$hdr = array('title' => 'Menus', 'small' => '');
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('route' => $this->get('router')->generate('users'), 'description'=>'Menus');
		
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
     * @Route("/menus/create", name="menus_create")
     */
    public function createAction(Request $request)
    {
		// Page header
		$hdr = array('title' => 'Nuevo Menu', 'small' => '');
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('route' => $this->get('router')->generate('menus'), 'description'=>'Menus');
		$bc[] = array('description'=>'Crear');
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_CREATE')) return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		// Form
		$menu = new Menu();
		
        $form = $this->createFormBuilder($menu, array('validation_groups' => array('create')))
			->add('id', FormType\TextType::class, array('label' => 'Id'))
            ->add('name', FormType\TextType::class, array('label' => 'Descripción'))
			->add('save', FormType\SubmitType::class, array('label' => 'Guardar'))
            ->getForm();
		
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			
			try{
				
				$em = $this->getDoctrine()->getManager();
				
				// Creates the menu
				$em->persist($menu);
				
				// Creates the root item
				$root_item = new MenuItem();
				$root_item->setMenuId($menu->getId())
					->setLabel('Root')
					->setMenu($menu)
					->setItemOrder(1)
					->setIsBranch(true)
					->setVisible(false);
				
				$em->persist($root_item);
				$em->flush();
				
				return $this->redirectToRoute('menus');
				
			} catch (\Exception $e) {
				$message = $this->handleException($e);
				$this->get('session')->getFlashBag()->add('error', $message);
			}
		}
		
		return $this->render('KsAdminLteThemeBundle::menu_create.html.twig', array(
            'hdr' 	=> $hdr,
			'bc' 	=> $bc,
			'form' => $form->createView()
        ));
	}
	
	/**
     * @Route("/menus/edit/{id}", name="menus_edit", defaults={"id" = 0})
     */
    public function editAction(Request $request, $id)
    {
		// Menu
		$em = $this->getDoctrine()->getManager();
		$menu = $em->getRepository('KsCoreBundle:Menu')->find($id);
		
        // Page header
		$hdr = array('title' => 'Editar Menu', 'small' => 'Id: ' . $menu->getId());
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('route' => $this->get('router')->generate('menus'), 'description'=>'Menus');
		$bc[] = array('description'=>'Editar');
		
        // Access Control
		$this->getGrants();
		if (! $this->granted('MASK_EDIT')) return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		// Form
		$form = $this->createFormBuilder($menu, array('validation_groups' => array('update')))
            ->add('name', FormType\TextType::class, array('label' => 'Descripción'))
			->add('save', FormType\SubmitType::class, array('label' => 'Guardar'))
            ->getForm();
		
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			
			try{
				
				$em->persist($menu);
				$em->flush();
				
				return $this->redirectToRoute('menus');
				
			} catch (\Exception $e) {
				$message = $this->handleException($e);
				$this->get('session')->getFlashBag()->add('error', $message);
			}
		}
	
		return $this->render('KsAdminLteThemeBundle::menu_edit.html.twig', array(
            'hdr' 	=> $hdr,
			'bc' 	=> $bc,
			'form' => $form->createView()
        ));
	}
	
	/**
     * @Route("/menus/delete", name="menus_delete")
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
				$menu = $em->getRepository('KsCoreBundle:Menu')->find($id);
				$em->remove($menu);
				$em->flush();
			}
			
		} catch (\Exception $e) {
			$message = $this->handleException($e);
			return Ajax::responseResult($message);
		}
		
		return Ajax::responseOk();
	}
	
	/************************************************
     * 					ITEMS
     ************************************************
	 */
	
	private function getItemsCrud1Conf()
	{
		$conf = array();
		$conf['name'] = 'menu_items';
		$conf['grants'] = $this->grants;
		$conf['urls'] = array(
			'list'		=> 'menu_items_list',
			'create'	=> 'menu_items_create',
			'edit'		=> 'menu_items_edit',
			'delete'	=> 'menu_items_delete'
		);
		return $conf;
	}
	
	
	/*
	public function translateList($records)
    {
		for ($i=0; $i<count($records); $i++)
		{
			$route = $records[$i]['route'];
			if ($route)
			{
				try {
					$records[$i]['route'] = $this->get('router')->generate($route);
				} catch (RouteNotFoundException $e) {
					$records[$i]['route'] = $route;
				}
			}
		}
		
		return $records;
	}
	
	
	public function translateCSV($key, $field, $value)
    {
		if ($key == 'route')
			if ($value)
				return $this->get('router')->generate($value);
		
		return $value;
	}
	*/
	
	/**
     * @Route("/menus/items/view/{id}", name="menu_items", defaults={"id" = 0})
     */
    public function menuitemsAction(Request $request, $id)
    {
		// Menu
		$em = $this->getDoctrine()->getManager();
		$menu = $em->getRepository('KsCoreBundle:Menu')->find($id);
		
		// Knp Menu
		$conn = $this->get('doctrine.dbal.default_connection');
		$knpmenu = $this->get('ks.core.menubuilder')->loadMenu($conn, $menu->getId());
		
		// Page header
		$hdr = array('title' => $menu->getName(), 'small' => '');
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('route' => $this->get('router')->generate('menus'), 'description'=>'Menus');
		$bc[] = array('description'=> $menu->getName());
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_VIEW')) return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		// Conf
		$crud = $this->getItemsCrud1Conf();
		$crud['url_param'] = array();
		$crud['url_param']['create'] = $id;
		
		return $this->render('KsAdminLteThemeBundle::menu_items_list.html.twig', array(
            'hdr' 		=> $hdr,
			'bc' 		=> $bc,
			'knpmenu' 	=> $knpmenu,
			'crud' 		=> $crud
        ));
    }
	
	private function getBranchList($id)
	{
		$conn = $this->get('database_connection');
		$qb = $conn->createQueryBuilder();
		
		$qb->select('a.id, a.label')
			->from('ks_menu_item', 'a')
			->andWhere('a.menu_id = ?')
			->andWhere('a.is_branch = 1')
			->setParameter(0, $id);
		
		$records = $qb->execute()->fetchAll();
		
		$options = array();
		$options['Seleccione un valor'] = '';
		
		foreach ($records as $r)
			$options[$r['label']] = $r['id'];
			
		return $options;
	}
	
	private function getFunctionList()
	{
		$conn = $this->get('database_connection');
		$qb = $conn->createQueryBuilder();
		$qb->select('a.id, a.description')
			->from('ks_ac', 'a');
		
		$records = $qb->execute()->fetchAll();
		
		$options = array();
		$options['Seleccione un valor'] = '';
		
		foreach ($records as $r)
			$options[$r['description']] = $r['id'];
			
		return $options;
	}
	
	private function getMaskList()
	{
		$options = array();
		$options['Seleccione un valor'] = '';
		$options['Lectura'] = MaskBuilder::MASK_VIEW;
		$options['Alta'] = MaskBuilder::MASK_CREATE;
		$options['Modificación'] = MaskBuilder::MASK_EDIT;
		$options['Baja'] = MaskBuilder::MASK_DELETE;
			
		return $options;
	}
	
	/**
     * @Route("/menus/items/create/{id}", name="menu_items_create", defaults={"id" = 0})
     */
    public function itemCreateAction(Request $request, $id)
    {
		// Menu
		$em = $this->getDoctrine()->getManager();
		$menu = $em->getRepository('KsCoreBundle:Menu')->find($id);
		
		// Page header
		$hdr = array('title' => $menu->getName(), 'small' => 'Agregar elemento');
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('route' => $this->get('router')->generate('menus'), 'description'=>'Menus');
		$bc[] = array('route' => $this->get('router')->generate('menu_items', array('id' => $id)), 'description' => $menu->getName());
		$bc[] = array('description'=>'Agregar elemento');
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_CREATE')) return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		// Form
		$menu_item = new MenuItem();
		$menu_item->setMenuId($id);
		$menu_item->setItemOrder(1);
		$menu_item->setIsBranch(false);
		$menu_item->setVisible(true);
		
        $form = $this->createFormBuilder($menu_item, array('validation_groups' => array('create')))
            ->add('menu_id', FormType\HiddenType::class)
			->add('parent_id', FormType\ChoiceType::class, array('label' => 'Hubicación', 'choices' => $this->getBranchList($id), 'choices_as_values' => true))
			->add('label', FormType\TextType::class, array('label' => 'Texto'))
			->add('route', FormType\TextType::class, array('label' => 'Ruta'))
			->add('item_order', FormType\NumberType::class, array('label' => 'Orden'))
			->add('icon', FormType\TextType::class, array('label' => 'Icono'))
			->add('is_branch', FormType\CheckboxType::class, array('label' => 'Submenú'))
			->add('visible', FormType\CheckboxType::class, array('label' => 'Visible'))
			->add('ac_id', FormType\ChoiceType::class, array('label' => 'Función', 'choices' => $this->getFunctionList(), 'choices_as_values' => true))
			->add('mask', FormType\ChoiceType::class, array('label' => 'Permiso', 'choices' => $this->getMaskList(), 'choices_as_values' => true))
			->add('save', FormType\SubmitType::class, array('label' => 'Guardar'))
            ->getForm();
		
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			
			try{
				
				$menu_item->setMenu($menu);
				
				$parent = $em->getRepository('KsCoreBundle:MenuItem')->find($menu_item->getParentId());
				$menu_item->setParent($parent);
				
				if ($menu_item->getAcId())
				{
					$ac = $em->getRepository('KsCoreBundle:AccessControl')->find($menu_item->getAcId());
					$menu_item->setAc($ac);
				}
				
				$em->persist($menu_item);
				$em->flush();
				
				return $this->redirectToRoute('menu_items', array('id' => $id));
				
			} catch (\Exception $e) {
				$message = $this->handleException($e);
				$this->get('session')->getFlashBag()->add('error', $message);
			}
		}
		
		return $this->render('KsAdminLteThemeBundle::menu_items_create.html.twig', array(
            'hdr' 	=> $hdr,
			'bc' 	=> $bc, 
			'form' 	=> $form->createView()
        ));
	}
	
	/**
     * @Route("/menus/items/edit/{id}", name="menu_items_edit", defaults={"id" = 0})
     */
    public function itemEditAction(Request $request, $id)
    {
		// Menu
		$em = $this->getDoctrine()->getManager();
		$menu_item = $em->getRepository('KsCoreBundle:MenuItem')->find($id);
		
		$menu = $menu_item->getMenu();
		$menu_id = $menu_item->getMenuId();
		
		// Page header
		$hdr = array('title' => $menu->getName(), 'small' => 'Editar elemento');
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('route' => $this->get('router')->generate('menus'), 'description'=>'Menus');
		$bc[] = array('route' => $this->get('router')->generate('menu_items', array('id' => $menu_id)), 'description' => $menu->getName());
		$bc[] = array('description'=>'Editar elemento');
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_EDIT')) return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		// Form
		$form = $this->createFormBuilder($menu_item, array('validation_groups' => array('update')))
			->add('parent_id', FormType\ChoiceType::class, array('label' => 'Hubicación', 'choices' => $this->getBranchList($menu_id), 'choices_as_values' => true))
			->add('label', FormType\TextType::class, array('label' => 'Texto'))
			->add('route', FormType\TextType::class, array('label' => 'Ruta'))
			->add('item_order', FormType\NumberType::class, array('label' => 'Orden'))
			->add('icon', FormType\TextType::class, array('label' => 'Icono'))
			->add('is_branch', FormType\CheckboxType::class, array('label' => 'Submenú'))
			->add('visible', FormType\CheckboxType::class, array('label' => 'Visible'))
			->add('ac_id', FormType\ChoiceType::class, array('label' => 'Función', 'choices' => $this->getFunctionList(), 'choices_as_values' => true))
			->add('mask', FormType\ChoiceType::class, array('label' => 'Permiso', 'choices' => $this->getMaskList(), 'choices_as_values' => true))
			->add('save', FormType\SubmitType::class, array('label' => 'Guardar'))
            ->getForm();
		
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			
			try{
				
				if (!$menu_item->getAcId())	$menu_item->setAcId(null);
				if (!$menu_item->getMask())	$menu_item->setMask(null);
				
				$em->persist($menu_item);
				$em->flush();
				
				return $this->redirectToRoute('menu_items', array('id' => $menu_id));
				
			} catch (\Exception $e) {
				$message = $this->handleException($e);
				$this->get('session')->getFlashBag()->add('error', $message);
			}
		}
		
		return $this->render('KsAdminLteThemeBundle::menu_items_edit.html.twig', array(
            'hdr' 	=> $hdr,
			'bc' 	=> $bc, 
			'form'	=> $form->createView()
        ));
	}
	
	/**
     * @Route("/menus/items/delete", name="menu_items_delete")
     */
    public function itemDeleteAction(Request $request)
    {
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_DELETE')) return Ajax::responseDenied();
		
		$em = $this->getDoctrine()->getManager();
		
		try {
			
			foreach ($request->request->get('ids') as $id)
			{
				$menu_item = $em->getRepository('KsCoreBundle:MenuItem')->find($id);
				$em->remove($menu_item);
				$em->flush();
			}
			
		} catch (\Exception $e) {
			$message = $this->handleException($e);
			return Ajax::responseResult($message);
		}
		
		return Ajax::responseOk();
	}
	
	
	/************************************************
     * 					Sidebar Menu
     ************************************************
	 */
	public function sidebarMenuAction(Request $request)
    {
		$main_menu = $this->get('session')->get('main_menu');
		
		if (! $main_menu)
		{
			$conn = $this->get('doctrine.dbal.default_connection');
			$main_menu = $this->get('ks.core.menubuilder')->loadMenu($conn, 'MAIN', 'side-menu', $this->getUser()->getId());
			$this->get('session')->set('main_menu', $main_menu);
		}
		
		$matcher = new Matcher();
		$uri = $_SERVER['REQUEST_URI'];
		$matcher->addVoter(new UriVoter($uri));
		$pos1 = strpos($uri, '/');
		$pos2 = strpos($uri, '/', $pos1 + 1);
		if ($pos2)
		{
			$start_of_uri = mb_substr($uri, 0, $pos2);
			$regex = '/^' . preg_quote($start_of_uri, '/') . '/';
			$matcher->addVoter(new RegexVoter($regex));
		}
		$renderer = new TwigRenderer($this->get('twig'), 'KsAdminLteThemeBundle:Menu:sidebar_menu.html.twig', $matcher);
		$sidebar_menu = $renderer->render($main_menu, array('currentClass' => 'active', 'ancestorClass' => 'active', 'branch_class' => 'treeview'));
		
		return $this->render('KsAdminLteThemeBundle:fragments:sidebar.html.twig',
            array(
				'sidebar_menu'	=> $sidebar_menu
			)
        );
    }
}