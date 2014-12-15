<?php

/**
 * This file is part of the {@link http://ontowiki.net OntoWiki} project.
 *
 * @copyright Copyright (c) 2014, {@link http://aksw.org AKSW}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */
class LinkfinderController extends OntoWiki_Controller_Component
{
    public function viewAction()
    {
        $_owApp = OntoWiki::getInstance();
        $logger = $_owApp->getCustomLogger("linkfinder");
        $_owApp->callJob('IssnFinderJob', array('This is DATA!!!'));


        $translate = $this->_owApp->translate;
        $this->view->placeholder('main.window.title')->set($translate->_('Linkfinder'));
        $this->addModuleContext('main.window.linkfinder');
        $_owApp->getNavigation()->disableNavigation();

    }

}