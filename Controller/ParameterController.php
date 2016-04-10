<?php
namespace Ks\AdminLteThemeBundle\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Ks\CoreBundle\Controller\BaseController;
use Ks\CoreBundle\Classes\DbAbs;
use Ks\CoreBundle\Classes\Ajax;
use Ks\CoreBundle\Entity\Parameter;

class ParameterController extends BaseController
{
	protected function getGrants()
    {
		parent::getAcGrants('PARAMETERS');
    }
	
	private function getCrudConf()
	{
		$conf = array();
		$conf['name'] = 'parameter';
		$conf['grants'] = $this->grants;
		$conf['urls'] = array(
			'list'		=> 'param_list',
			'create'	=> 'param_create',
			'edit'		=> 'param_edit',
			'delete'	=> 'param_delete',
			'export'	=> 'param_export'
		);
		$conf['filters']['id'] = array('filter'=>'id', 'label'=>'Id', 'field'=>'a.id', 'type'=>'text', 'condition'=>'contains');
		$conf['filters']['value'] = array('filter'=>'value', 'label'=>'Valor', 'field'=>'a.value', 'type'=>'text', 'condition'=>'contains');
		$conf['filters']['description'] = array('filter'=>'description', 'label'=>'Descripción', 'field'=>'a.description', 'type'=>'text', 'condition'=>'contains');
		return $conf;
	}
	
	private function getQuery()
	{
		$conn = $this->get('doctrine.dbal.default_connection');
		$qb = $conn
			->createQueryBuilder()
			->select('id', 'description', 'value', 'created', 'updated', 
				DbAbs::longDatetime($conn, 'created') . " char_created", 
				DbAbs::longDatetime($conn, 'updated') . " char_updated")
			->from('ks_parameter', 'a');
		return $qb;
	}
	
	/**
     * @Route("/parameters", name="parameters")
     */
    public function indexAction(Request $request)
    {
		// Page header
		$hdr = array('title' => 'Parametros', 'small' => '');
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('description'=>'Parametros');
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_VIEW')) return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		// Config
		$crud = $this->getCrudConf();
		
		return $this->render('KsAdminLteThemeBundle::param_list.html.twig', array(
            'hdr' 	=> $hdr,
			'bc' 	=> $bc,
			'crud' 	=> $crud
        ));
    }
	
	/**
     * @Route("/parameters/list", name="param_list")
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
     * @Route("/parameters/export", name="param_export")
     */
    public function exportAction(Request $request)
    {
		// Page header
		$hdr = array('title' => 'Parametros', 'small' => '');
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('route' => $this->get('router')->generate('parameters'), 'description'=>'Parametros');
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_VIEW')) return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		$conf = $this->getCrudConf();
		$csv_filename = 'parametros_' . date('mdHis') . '.csv';
		$csv_columns = array();
		$csv_columns['id'] = array('field' => 'id', 'title' => 'Id');
		$csv_columns['value'] = array('field' => 'value', 'title' => 'Valor');
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
     * @Route("/parameters/create", name="param_create")
     */
    public function createAction(Request $request)
    {
		// Page header
		$hdr = array('title' => 'Nuevo Parametro', 'small' => '');
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('route' => $this->get('router')->generate('parameters'), 'description'=>'Parametros');
		$bc[] = array('description'=>'Crear');
		
		// Access Control
		$this->getGrants();
		
		if (! $this->granted('MASK_CREATE')) return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		// Form
		$ac = new Parameter();
		$form = $this->get('ks.core.parameter_model')->getFormCreate($ac);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			
			try{
				
				$this->get('ks.core.parameter_model')->insert($ac);
				return $this->redirectToRoute('parameters');
				
			} catch (\Exception $e) {
				$message = $this->handleException($e);
				$this->get('session')->getFlashBag()->add('error', $message);
			}
		}
		
		return $this->render('KsAdminLteThemeBundle::param_create.html.twig', array(
            'hdr' 	=> $hdr,
			'bc' 	=> $bc,
			'form' 	=> $form->createView()
        ));
	}
	
	/**
     * @Route("/parameters/edit/{id}", name="param_edit", defaults={"id" = 0})
     */
    public function editAction(Request $request, $id)
    {
		// Parameter
		$param = $this->get('ks.core.parameter_model')->get($id);
		
        // Page header
		$hdr = array('title' => 'Editar Parametro', 'small' => 'Id: ' . $param->getId());
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('route' => $this->get('router')->generate('parameters'), 'description'=>'Parametros');
		$bc[] = array('description'=>'Editar');
		
        // Access Control
		$this->getGrants();
		if (! $this->granted('MASK_EDIT')) return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		// Form
		$form = $this->get('ks.core.parameter_model')->getFormEdit($param);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			
			try{
				
				$this->get('ks.core.parameter_model')->update($param);
				return $this->redirectToRoute('parameters');
				
			} catch (\Exception $e) {
				$message = $this->handleException($e);
				$this->get('session')->getFlashBag()->add('error', $message);
			}
		}
	
		return $this->render('KsAdminLteThemeBundle::param_edit.html.twig', array(
            'hdr' 	=> $hdr,
			'bc' 	=> $bc,
			'form' => $form->createView()
        ));
	}
	
	/**
     * @Route("/parameters/delete", name="param_delete")
     */
    public function deleteAction(Request $request)
    {
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_DELETE')) return Ajax::responseDenied();
		
		try {
			
			foreach ($request->request->get('ids') as $id)
			{
				$param = $this->get('ks.core.parameter_model')->get($id);
				$this->get('ks.core.parameter_model')->delete($param);
			}
			
		} catch (\Exception $e) {
			$message = $this->handleException($e);
			return Ajax::responseResult($message);
		}
		
		return Ajax::responseOk();
	}
}