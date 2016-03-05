<?php 
namespace Ks\AdminLteThemeBundle\DependencyInjection;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\Config\FileLocator;

class KsAdminLteThemeExtension extends ConfigurableExtension implements PrependExtensionInterface
{
	protected function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
		/*
        $loader = new YamlFileLoader(
			$container,
			new FileLocator(__DIR__.'/../Resources/config')
		);
		
		$loader->load('services.yml');
		*/
    }
	
	public function getNamespace()
    {
        return 'http://ks.localhost/schema/dic/adminlte';
    }
	
	public function prepend(ContainerBuilder $container)
    {
        // get all bundles
		$bundles = $container->getParameter('kernel.bundles');
		
		// Twig
		if (isset($bundles['TwigBundle'])) {
			$config = array(
				'form_themes' => array('bootstrap_3_layout.html.twig'),
				'globals' => array(
					'ac'=>'@ks.core.ac'
				)
			);
			$container->prependExtensionConfig('twig', $config);
		}
		
    }
}