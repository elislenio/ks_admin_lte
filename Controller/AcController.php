<?php
namespace Ks\AdminLteThemeBundle\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type as FormType;
use Doctrine\DBAL\Types\Type;
use Ks\CoreBundle\Controller\BaseController;
use Ks\CoreBundle\Entity\AccessControl;
use Ks\CoreBundle\Classes\DbAbs;
use Ks\CoreBundle\Classes\Ajax;

class AcController extends BaseController
{
	protected function getGrants()
    {
		parent::getAcGrants('ACCESS_CONTROL');
    }
	
	private function getCrud1Conf()
	{
		$engine = $this->getDbEngine();
		
		$conf = array();
		$conf['name'] = 'acs';
		$conf['grants'] = $this->grants;
		$conf['urls'] = array(
			'list'		=> 'acs_list',
			'create'	=> 'acs_create',
			'edit'		=> 'acs_edit',
			'delete'	=> 'acs_delete',
			'export'	=> 'acs_export'
		);
		$conf['dt'] = 'KsAdminLteThemeBundle:fragments:crud1_dt_acs.html.twig';
		$conf['csv_filename'] = 'funciones_' . date('mdHis') . '.csv';
		$conf['csv_columns'] = array();
		$conf['csv_columns']['id'] = array('field' => 'id', 'title' => 'Id');
		$conf['csv_columns']['description'] = array('field' => 'description', 'title' => 'Descripción');
		$conf['csv_columns']['char_created'] = array('field' => 'char_created', 'title' => 'Fecha de creación');
		$conf['csv_columns']['char_updated'] = array('field' => 'char_updated', 'title' => 'Fecha de actualización');
		$conf['sql'] = array();
		$conf['sql']['select'] = array('id', 'description', 'created', 'updated', DbAbs::longDatetime($engine, 'created') . " char_created", DbAbs::longDatetime($engine, 'updated') . " char_updated");
		$conf['sql']['from'] = array('ks_ac', 'a');
		$conf['filters']['description'] = array('filter'=>'description', 'label'=>'Descripción', 'field'=>'a.description', 'type'=>'text', 'condition'=>'contains');
		$conf['filters']['id'] = array('filter'=>'id', 'label'=>'Id', 'field'=>'a.id', 'type'=>'text');
		return $conf;
	}
	
	/**
     * @Route("/acs", name="acs")
     */
    public function indexAction(Request $request)
    {
		// Page header
		$hdr = array('title' => 'Funciones', 'small' => '');
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('description'=>'Funciones');
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_VIEW')) return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		// Config
		$crud = $this->getCrud1Conf();
		
		return $this->render('KsAdminLteThemeBundle::ac_list.html.twig', array(
            'hdr' 	=> $hdr,
			'bc' 	=> $bc,
			'crud' 	=> $crud
        ));
    }
	
	/**
     * @Route("/acs/list", name="acs_list")
     */
    public function listAction(Request $request)
    {
		$this->getGrants();
		if (! $this->granted('MASK_VIEW')) return $this->get('ks.core.crud1')->getDeniedResponse();
		
		$conn = $this->get('doctrine.dbal.default_connection');
		return $this->get('ks.core.crud1')->getList($conn, $request->request, $this->getCrud1Conf(), array($this, 'translateList'));
    }
	
	/**
     * @Route("/acs/export", name="acs_export")
     */
    public function exportAction(Request $request)
    {
		// Page header
		$hdr = array('title' => 'Funciones', 'small' => '');
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('route' => $this->get('router')->generate('users'), 'description'=>'Funciones');
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_VIEW')) return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		$conn = $this->get('doctrine.dbal.default_connection');
		return $this->get('ks.core.crud1')->exportCsv($conn, $request->query, $this->getCrud1Conf(), array($this, 'translateCSV'));
    }
	
	/**
     * @Route("/acs/create", name="acs_create")
     */
    public function createAction(Request $request)
    {
		// Page header
		$hdr = array('title' => 'Nueva Función', 'small' => '');
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('route' => $this->get('router')->generate('acs'), 'description'=>'Funciones');
		$bc[] = array('description'=>'Crear');
		
		// Access Control
		$this->getGrants();
		
		if (! $this->granted('MASK_CREATE')) return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		// Form
		$ac = new AccessControl();
		
        $form = $this->createFormBuilder($ac, array('validation_groups' => array('create')))
            ->add('id')
			->add('description', FormType\TextType::class, array('label' => 'Descripción'))
			->add('save', FormType\SubmitType::class, array('label' => 'Guardar'))
            ->getForm();
		
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			
			try{
				
				$ac->normalizeId();
				$em = $this->getDoctrine()->getManager();
				$em->persist($ac);
				$em->flush();
				
				return $this->redirectToRoute('acs');
				
			} catch (\Exception $e) {
				$message = $this->handleException($e);
				$this->get('session')->getFlashBag()->add('error', $message);
			}
		}
		
		return $this->render('KsAdminLteThemeBundle::ac_create.html.twig', array(
            'hdr' 	=> $hdr,
			'bc' 	=> $bc,
			'form' 	=> $form->createView()
        ));
	}
	
	/**
     * @Route("/acs/edit/{id}", name="acs_edit", defaults={"id" = 0})
     */
    public function editAction(Request $request, $id)
    {
		// Funcion
		$em = $this->getDoctrine()->getManager();
		$ac = $em->getRepository('KsCoreBundle:AccessControl')->find($id);
		
        // Page header
		$hdr = array('title' => 'Editar Función', 'small' => 'Id: ' . $ac->getId());
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('route' => $this->get('router')->generate('acs'), 'description'=>'Funciones');
		$bc[] = array('description'=>'Editar');
		
        // Access Control
		$this->getGrants();
		if (! $this->granted('MASK_EDIT')) return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		// Form
		$form = $this->createFormBuilder($ac, array('validation_groups' => array('update')))
            ->add('description', FormType\TextType::class, array('label' => 'Descripción'))
			->add('save', FormType\SubmitType::class, array('label' => 'Guardar'))
            ->getForm();
		
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			
			try{
				
				$em->persist($ac);
				$em->flush();
				
				return $this->redirectToRoute('acs');
				
			} catch (\Exception $e) {
				$message = $this->handleException($e);
				$this->get('session')->getFlashBag()->add('error', $message);
			}
		}
	
		return $this->render('KsAdminLteThemeBundle::ac_edit.html.twig', array(
            'hdr' 	=> $hdr,
			'bc' 	=> $bc,
			'form' => $form->createView()
        ));
	}
	
	/**
     * @Route("/acs/delete", name="acs_delete")
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
				$ac = $em->getRepository('KsCoreBundle:AccessControl')->find($id);
				$em->remove($ac);
				$em->flush();
			}
			
		} catch (\Exception $e) {
			$message = $this->handleException($e);
			return Ajax::responseResult($message);
		}
		
		return Ajax::responseOk();
	}
}