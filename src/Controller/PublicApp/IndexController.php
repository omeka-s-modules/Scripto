<?php
namespace Scripto\Controller\PublicApp;

use Zend\View\Model\ViewModel;
use Zend\Mvc\Controller\AbstractActionController;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
    }

    public function loginAction()
    {
        return $this->scripto()->login('scripto');
    }

    public function logoutAction()
    {
        return $this->scripto()->logout('scripto');
    }
}
