<?php
namespace Ks\AdminLteThemeBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Ks\CoreBundle\Controller\BaseController;

class SecurityController extends BaseController
{
    /**
     * @Route("/login", name="login")
     */
    public function loginAction(Request $request)
    {
		if ($this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY'))
			return $this->redirectToRoute('homepage');
		
		// Check if it is an Ajax Request
		// Eg: On Datatables reload, when the session times out
		if ($request->isXmlHttpRequest()) 
		{
			$response = array();
			$response['server_msg'] = 'Ajax request denied';
			$response['redirectTo'] = $this->generateUrl('login');
			return new Response(
				json_encode($response),
				200,
				array('Content-Type' => 'application/json')
			);
        }
		
		$authenticationUtils = $this->get('security.authentication_utils');
		$error = $authenticationUtils->getLastAuthenticationError();
		$username = $authenticationUtils->getLastUsername();
		$this->get('session')->remove('ks_must_change_pwd');
		$this->get('session')->remove('ks_authenticated_username');
		
		if ($error)
		{
			if ($error->getMessageKey() == 'Credentials have expired.')
			{
				$this->get('session')->set('ks_must_change_pwd', true);
				$this->get('session')->set('ks_authenticated_username', $username);
				return $this->redirectToRoute('pwdchange');
			}
			else
			{
				$this->get('session')->getFlashBag()->add('error', $this->get('translator')->trans($error->getMessageKey(), array(), 'security'));
			}
		}
		
		return $this->render('KsAdminLteThemeBundle:security:login.html.twig',
			array(
				'username'	=> $username,
				'error'   	=> $error
			)
		);
    }
	
	/**
     * @Route("/login_check", name="login_check")
     */
    public function loginCheckAction()
    {
        // this controller will not be executed,
        // as the route is handled by the Security system
    }
	
	/**
     * @Route("/pwdchange", name="pwdchange")
     */
    public function pwdChangeAction(Request $request)
    {
		$username = $this->get('session')->get('ks_authenticated_username');
		
		// Access is controlled with session variable: ks_must_change_pwd
		
		if (! ($this->get('session')->get('ks_must_change_pwd') && $username))
			return $this->redirectToRoute('login');
		
		$form = $this->get('ks.core.user_model')->getFormPwdChange();
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			
			try{
				
				$data = $form->getData();
				$user = $this->get('ks.core.user_model')->getByUsername($username);
				$this->get('ks.core.user_model')->setPasswordSelf($user, $data['password']);
				$this->get('session')->getFlashBag()->add('success', 'La contraseña se cambió con éxito.');
				return $this->redirectToRoute('login');
				
			} catch (\Exception $e) {
				$message = $this->handleException($e);
				$this->get('session')->getFlashBag()->add('error', $message);
			}
		}
		
		return $this->render('KsAdminLteThemeBundle:security:change_password.html.twig',
			array(
				'form' 		=> $form->createView(),
				'username' 	=> $username
			)
		);
    }
	
	/**
     * @Route("/pwdselfchange", name="pwdselfchange")
     */
    public function pwdSelfChangeAction(Request $request)
    {
		// User
		$user = $this->get('security.token_storage')->getToken()->getUser();
		
		// Page header
		$hdr = array('title' => 'Cambio de contraseña', 'small' => 'Usuario: ' . $user->getUsername());
		
		// Breadcrumb
		$bc = array();
		$bc[] = array('description'=>'Cambio de contraseña');
		
		// Access Control
		$granted = $this->get('ks.core.ac')->localPasswordEnabled();
		if (! $granted ) 
			return $this->render('KsAdminLteThemeBundle::denied.html.twig', array('hdr' => $hdr, 'bc' => $bc));
		
		// Form
		$form = $this->get('ks.core.user_model')->getFormPwdSelfChange();
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			
			try{
				
				$data = $form->getData();
				$this->get('ks.core.user_model')->setPasswordSelf($user, $data['new_password']);
				$this->get('session')->getFlashBag()->add('success', 'La contraseña se cambió con éxito.');
				return $this->redirectToRoute('homepage');
				
			} catch (\Exception $e) {
				$message = $this->handleException($e);
				$this->get('session')->getFlashBag()->add('error', $message);
			}
		}
		
		return $this->render('KsAdminLteThemeBundle:security:self_change_password.html.twig', array(
			'hdr' 	=> $hdr,
			'bc' 	=> $bc,
			'form' 	=> $form->createView()
		));
    }
}
