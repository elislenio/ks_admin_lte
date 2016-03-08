<?php
namespace Ks\AdminLteThemeBundle\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Form\Extension\Core\Type as FormType;
use Ks\CoreBundle\Controller\BaseController;
use Ks\CoreBundle\Entity\AccessControlList;
use Ks\CoreBundle\Classes\DbAbs;
use Ks\CoreBundle\Classes\Ajax;

class AclController extends BaseController
{
	protected function getGrants()
    {
		parent::getAcGrants('PERMISSIONS');
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
			'create'	=> 'acls_create',
			'edit'		=> 'acls_edit',
			'delete'	=> 'acls_delete',
			'export'	=> 'acls_export'
		);
		$conf['dt'] = 'KsAdminLteThemeBundle:fragments:crud1_dt_acls.html.twig';
		$conf['csv_filename'] = 'permisos_' . date('mdHis') . '.csv';
		$conf['csv_columns'] = array();
		$conf['csv_columns']['id'] = array('field' => 'id', 'title' => 'Id');
		$conf['csv_columns']['role'] = array('field' => 'role', 'title' => 'Rol');
		$conf['csv_columns']['ac'] = array('field' => 'ac', 'title' => 'Función');
		$conf['csv_columns']['read'] = array('field' => 'mask', 'title' => 'Lectura');
		$conf['csv_columns']['create'] = array('field' => 'mask', 'title' => 'Alta');
		$conf['csv_columns']['update'] = array('field' => 'mask', 'title' => 'Modificación');
		$conf['csv_columns']['delete'] = array('field' => 'mask', 'title' => 'Baja');
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
		
		return $this->render('KsAdminLteThemeBundle::acl_list.html.twig', array(
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
		return $this->get('ks.core.dt_report')->exportCsv(
			$this->getQuery(), 
			$request->query, 
			$conf['filters'], 
			$conf['csv_filename'], 
			$conf['csv_columns'],
			array($this, 'translateCSV')
		);
    }
	
	private function getRoleList()
	{
		$em = $this->getDoctrine()->getManager();
		$roles = $em->getRepository('KsCoreBundle:Role')->findAll();
		
		$options = array();
		$options['Seleccione un valor'] = '';
		
		foreach ($roles as $r)
			$options[$r->getDescription()] = $r->getId();
			
		return $options;
	}
	
	private function getControlList()
	{
		$em = $this->getDoctrine()->getManager();
		$acs = $em->getRepository('KsCoreBundle:AccessControl')->findAll();
		
		$options = array();
		$options['Seleccione un valor'] = '';
		
		foreach ($acs as $r)
			$options[$r->getDescription()] = $r->getId();
			
		return $options;
	}
	
	/**
     * @Route("/acls/create", name="acls_create")
     */
    public function createAction(Request $request)
    {
		// Page header
		$hdr = array('title' => 'Nuevo Permiso', 'small' => '');
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('route' => $this->get('router')->generate('acls'), 'description'=>'Permisos');
		$bc[] = array('description'=>'Crear');
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_CREATE')) return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		// Form
		$acl = new AccessControlList();
		$acl->setMaskView(true);
		
		$form = $this->createFormBuilder($acl, array('validation_groups' => array('create')))
            ->add('role_id', FormType\ChoiceType::class, array('label' => 'Rol', 'choices' => $this->getRoleList(), 'choices_as_values' => true))
			->add('ac_id', FormType\ChoiceType::class, array('label' => 'Control', 'choices' => $this->getControlList(), 'choices_as_values' => true))
			->add('mask_view', FormType\CheckboxType::class, array('label' => 'Lectura'))
			->add('mask_create', FormType\CheckboxType::class, array('label' => 'Alta'))
			->add('mask_edit', FormType\CheckboxType::class, array('label' => 'Modificación'))
			->add('mask_delete', FormType\CheckboxType::class, array('label' => 'Baja'))
			->add('save', FormType\SubmitType::class, array('label' => 'Guardar'))
            ->getForm();
		
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			
			try{
				
				$em = $this->getDoctrine()->getManager();
				$role = $em->getRepository('KsCoreBundle:Role')->find($acl->getRoleId());
				$ac = $em->getRepository('KsCoreBundle:AccessControl')->find($acl->getAcId());
				$acl->buildMask();
				$acl->setRole($role);
				$acl->setAc($ac);
				$em->persist($acl);
				$em->flush();
				
				return $this->redirectToRoute('acls');
				
			} catch (\Exception $e) {
				$message = $this->handleException($e);
				$this->get('session')->getFlashBag()->add('error', $message);
			}
		}
		
		return $this->render('KsAdminLteThemeBundle::acl_create.html.twig', array(
            'hdr' 	=> $hdr,
			'bc' 	=> $bc,
			'form' 	=> $form->createView()
        ));
	}
	
	/**
     * @Route("/acls/edit/{id}", name="acls_edit", defaults={"id" = 0})
     */
    public function editAction(Request $request, $id)
    {
		// ACL
		$em = $this->getDoctrine()->getManager();
		$acl = $em->getRepository('KsCoreBundle:AccessControlList')->find($id);
		
		// Page header
		$hdr = array('title' => 'Editar Permiso', 'small' => 'Id: ' . $acl->getId());
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('route' => $this->get('router')->generate('acls'), 'description'=>'Permisos');
		$bc[] = array('description'=>'Editar');
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_EDIT')) return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		// Form
		$acl->parseMask();
		
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
				
				$acl->buildMask();
				$em->persist($acl);
				$em->flush();
				
				return $this->redirectToRoute('acls');
				
			} catch (\Exception $e) {
				$message = $this->handleException($e);
				$this->get('session')->getFlashBag()->add('error', $message);
			}
		}
	
		return $this->render('KsAdminLteThemeBundle::acl_edit.html.twig', array(
            'hdr' 	=> $hdr,
			'bc' 	=> $bc,
			'form' 	=> $form->createView(),
			'acl' 	=> $acl
        ));
	}
	
	/**
     * @Route("/acls/delete", name="acls_delete")
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
				$acl = $em->getRepository('KsCoreBundle:AccessControlList')->find($id);
				$em->remove($acl);
				$em->flush();
			}
			
		} catch (\Exception $e) {
			$message = $this->handleException($e);
			return Ajax::responseResult($message);
		}
		
		return Ajax::responseOk();
	}
}