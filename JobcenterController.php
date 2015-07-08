<?php

/**
 * This file is part of the {@link http://ontowiki.net OntoWiki} project.
 *
 * @copyright Copyright (c) 2014, {@link http://aksw.org AKSW}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

require_once realpath(dirname(__FILE__)) . '/jobs/IssnFinderJob.php';

class JobcenterController extends OntoWiki_Controller_Component
{
    public function linkfinderAction()
    {
//        $_owApp = OntoWiki::getInstance();
//        $modelIri = $this->_owApp->selectedModel->getModelIri();
//        $logger = $_owApp->getCustomLogger("jobcenter");
//        $logger->debug('$modelIri: ' . $modelIri);
//
//        $result = 'here goes the result';
//        $this->view->result = $result;
//
//
//        $issnFinder = new IssnFinderJob();
//        $listOfIssn = $issnFinder->getIssn();
//
//        $this->view->listOfIssn = $listOfIssn;
//        $translate = $this->_owApp->translate;
//        $this->view->placeholder('main.window.title')->set($translate->_('Jobcenter - Linkfinder'));
//        $this->addModuleContext('main.window.linkfinder');
//        $_owApp->getNavigation()->disableNavigation();

    }

    public function viewAction() {
        $_owApp = OntoWiki::getInstance();
        $translate = $this->_owApp->translate;
        $this->view->placeholder('main.window.title')->set($translate->_('Jobcenter'));
        $this->addModuleContext('main.window.linkfinder');
        $_owApp->getNavigation()->disableNavigation();
    }

}