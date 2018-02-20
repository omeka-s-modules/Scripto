<?php
namespace Scripto\Controller\Admin;

use Zend\View\Model\ViewModel;
use Zend\Mvc\Controller\AbstractActionController;

class IndexController extends AbstractActionController
{
    public function indexAction()
    {
        //~ $this->api()->update('scripto_media', 84870, ['o-module-scripto:text' => 'foobar bazbat']);
        exit('Index:index');
    }
}
