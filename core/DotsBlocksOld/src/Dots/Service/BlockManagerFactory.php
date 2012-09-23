<?php
/**
 * This file is part of DotsCMS
 *
 * (c) 2012 ZendExperts <team@zendexperts.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Dots\Service;

use Zend\ServiceManager\FactoryInterface,
    Zend\ServiceManager\ServiceLocatorInterface,
    Dots\Block\BlockManager;

/**
 * Dots Block Manager service factory
 * @package Dots
 * @author Cosmin Harangus <cosmin@zendexperts.com>
 */
class BlockManagerFactory implements FactoryInterface
{
    /**
     * Create and return a BlockManager instance
     *
     * @param  ServiceLocatorInterface $serviceLocator
     * @return BlockManager
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config = $serviceLocator->get('Configuration');
        $config = isset($config['dots']) && (is_array($config['dots']) || $config['dots'] instanceof ArrayAccess)
            ? $config['dots']
            : array();

        $manager = new BlockManager();
        foreach($config['blocks'] as $blockClass){
            try{
                $block = $serviceLocator->get($blockClass);
            }catch(\RuntimeException $exception){
                $block = $serviceLocator->get('Di')->get($blockClass);
            }
            $manager->addContentHandler($block);
        }
        $manager->setServiceLocator($serviceLocator);
        return $manager;
    }
}