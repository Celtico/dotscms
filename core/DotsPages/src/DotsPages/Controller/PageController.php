<?php
namespace DotsPages\Controller;

use Zend\Mvc\Controller\ActionController,
    Zend\View\Model\ViewModel;

class PageController extends ActionController
{
    public function viewAction()
    {
        $routeMatch = $this->getEvent()->getParam('route-match');
        $page = $routeMatch->getParam('page');
        $metaModel = $this->getLocator()->get('DotsPages\Db\Model\PageMeta');
        $pageMeta = $metaModel->getByPageId($page->id);

        $viewModel = new ViewModel();
        $viewModel->setTemplate($page->template);

        $view = $this->getLocator()->get('view');

        if ($pageMeta){
            $view->plugin('headTitle')->append($pageMeta->title);
            $view->plugin('headMeta')->appendName('keywords', $pageMeta->keywords);
            $view->plugin('headMeta')->appendName('description', $pageMeta->description);
            if (null != $pageMeta->author)
                $view->plugin('headMeta')->appendName('author', $pageMeta->author);
            if (null != $pageMeta->robots)
                $view->plugin('headMeta')->appendName('robots', $pageMeta->robots);
            if (null != $pageMeta->copyright)
                $view->plugin('headMeta')->appendName('copyright', $pageMeta->copyright);
            $view->plugin('headMeta')->appendName('charset', $pageMeta->charset);
        }

        return $viewModel;
    }
}