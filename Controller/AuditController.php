<?php
namespace Ks\AdminLteThemeBundle\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Ks\CoreBundle\Controller\BaseController;
use Ks\CoreBundle\Classes\DbAbs;
use Ks\CoreBundle\Classes\Ajax;

use Symfony\Component\Form\Extension\Core\Type as FormType;

class AuditController extends BaseController
{
	protected function getGrants()
    {
		parent::getAcGrants('REP_AUDIT');
		// this report is read only
		$this->grants['MASK_CREATE'] = false;
		$this->grants['MASK_EDIT'] = false;
		$this->grants['MASK_DELETE'] = false;
    }
	
	private function getCrud1Conf()
	{
		$engine = $this->getDbEngine();
		
		$conf = array();
		$conf['name'] = 'rep_audit';
		$conf['grants'] = $this->grants;
		$conf['urls'] = array(
			'list'		=> 'rep_audit_list',
			'export'	=> 'rep_audit_export'
		);
		$conf['dt'] = 'KsAdminLteThemeBundle:fragments:crud1_dt_rep_audit.html.twig';
		$conf['csv_filename'] = 'rep_audit_' . date('mdHis') . '.csv';
		$conf['csv_columns'] = array();
		$conf['csv_columns']['logged_at'] = array('field' => 'logged_at', 'title' => 'Fecha');
		$conf['csv_columns']['id'] = array('field' => 'id', 'title' => 'Log Id');
		$conf['csv_columns']['username'] = array('field' => 'username', 'title' => 'Usuario');
		$conf['csv_columns']['object_class'] = array('field' => 'object_class', 'title' => 'Entidad');
		$conf['csv_columns']['action'] = array('field' => 'action', 'title' => 'Acción');
		$conf['csv_columns']['object_id'] = array('field' => 'object_id', 'title' => 'Identificador');
		$conf['csv_columns']['data'] = array('field' => 'data', 'title' => 'Detalle');
		$conf['sql'] = array();
		$conf['sql']['select'] = array('a.id', 'a.username', 'a.object_class', 'a.action', 'a.object_id', 'a.data', 'a.logged_at', DbAbs::longDatetime($engine, 'a.logged_at') . " char_logged_at");
		$conf['sql']['from'] = array('ext_log_entries', 'a');
		$conf['filters'] = array();
		$conf['filters']['logged_at'] = array('filter'=>'logged_at', 'label'=>'Rango de fechas', 'field'=>'a.logged_at', 'type'=>'datetime', 'condition'=>'bt', 'input_type'=>'date_range', 'extra'=>'readonly', 'value_callback'=>'Ks\AdminLteThemeBundle\Classes\DateRangePicker::parseValue');
		$conf['filters']['username'] = array('filter'=>'username', 'label'=>'Usuario', 'field'=>'a.username', 'type'=>'text', 'condition'=>'eq');
		$conf['filters']['entity'] = array('filter'=>'entity', 'label'=>'Entidad', 'field'=>'a.object_class', 'type'=>'text', 'condition'=>'contains');
		$conf['filters']['action'] = array('filter'=>'action', 'label'=>'Acción', 'field'=>'a.action', 'type'=>'text', 'condition'=>'eq');
		return $conf;
	}
	
	/**
     * @Route("/rep_audit", name="rep_audit")
     */
    public function indexAction(Request $request)
    {
		// Page header
		$hdr = array('title' => 'Pista de auditoría', 'small' => '');
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('description'=>'Pista de auditoría');
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_VIEW')) 
			return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		// Config
		$crud = $this->getCrud1Conf();
		
		/*
		// PRUEBA
		$label_attrs = array('class' => 'col-md-3');
		$attrs = array('class' => 'input-sm');
		
		$search_form = $this->get('form.factory')->createNamedBuilder(
			$crud['name'] . '_sform', 
			'form', null, 
			array('attr' => array(
				'id' 		=> $crud['name'] . '_sform',
				'class' 	=> 'form-horizontal'
			))
		)
			->setMethod('GET')
            ->add('logged_at', FormType\TextType::class, array('label' => 'Rango de fechas', 'required' => false, 'label_attr' => $label_attrs, 'attr' => $attrs))
			->add('username', FormType\TextType::class, array('label' => 'Usuario', 'required' => false, 'label_attr' => $label_attrs, 'attr' => $attrs))
			->add('entity', FormType\TextType::class, array('label' => 'Entidad', 'required' => false, 'label_attr' => $label_attrs, 'attr' => $attrs))
			->add('action', FormType\TextType::class, array('label' => 'Acción', 'required' => false, 'label_attr' => $label_attrs, 'attr' => $attrs))
			->getForm();
		*/
		
		return $this->render('KsAdminLteThemeBundle::rep_audit_list.html.twig', array(
            'hdr' 	=> $hdr,
			'bc' 	=> $bc,
			'crud' 	=> $crud
			//,'search_form'	=> $search_form->createView()
        ));
    }
	
	public function translateList($records)
    {
		for ($i=0; $i<count($records); $i++)
		{
			$data = $records[$i]['data'];
			if ($data)
			{
				$records[$i]['data'] = json_encode(unserialize($data));
			}
		}
		
		return $records;
	}
	
	public function translateCSV($key, $field, $value)
    {
		switch ($key)
		{
			case 'data':
				return json_encode(unserialize($value));
				break;
		}
		
		return $value;
	}
	
	/**
     * @Route("/rep_audit/list", name="rep_audit_list")
     */
    public function listAction(Request $request)
    {
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_VIEW')) return $this->get('ks.core.crud1')->getDeniedResponse();
		
		$conn = $this->get('doctrine.dbal.default_connection');
		return $this->get('ks.core.crud1')->getList($conn, $request->request, $this->getCrud1Conf(), array($this, 'translateList'));
    }
	
	/**
     * @Route("/rep_audit/export", name="rep_audit_export")
     */
    public function exportAction(Request $request)
    {
		// Page header
		$hdr = array('title' => 'Pista de auditoría', 'small' => '');
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('route' => $this->get('router')->generate('rep_audit'), 'description'=>'Pista de auditoría');
		
		// Access Control
		$this->getGrants();
		if (! $this->granted('MASK_VIEW')) 
			return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		$conn = $this->get('doctrine.dbal.default_connection');
		return $this->get('ks.core.crud1')->exportCsv(
			$conn,
			$request->query, 
			$this->getCrud1Conf(), 
			array($this, 'translateCSV')
		);
    }
}