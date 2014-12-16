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
//        $modelIri = $this->_owApp->selectedModel->getModelIri();
        $logger = $_owApp->getCustomLogger("linkfinder");
        $_owApp->callJob('IssnFinderJob', array('modelIri' => "http://ubl.amsl.technology/erm/"));

//        $model = new Erfurt_Owl_Model($modelIri);
//        $logger->debug('model:  ' . var_export($model));
//        $sparql = '
//            prefix bibrm: <http://vocab.ub.uni-leipzig.de/bibrm/>
//            prefix umbel: <http://umbel.org/umbel#>
//            SELECT ?issn WHERE {
//                ?s a bibrm:ContractItem .
//                ?s ?p ?issn .
//                FILTER(regex(?p, "issn"))
//                FILTER(regex(?issn, "urn:(issn|ISSN)"))
//                #FILTER( NOT EXISTS {?issn umbel:isLike ?zdb })
//            }';
//
//        //query selected model
//        $query = Erfurt_Sparql_SimpleQuery::initWithString($sparql);
//        $result = $model->sparqlQuery($sparql);


        $translate = $this->_owApp->translate;
        $this->view->placeholder('main.window.title')->set($translate->_('Linkfinder'));
        $this->addModuleContext('main.window.linkfinder');
        $_owApp->getNavigation()->disableNavigation();

    }

}