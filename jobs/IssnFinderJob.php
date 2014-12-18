<?php

/**
 * This file is part of the {@link http://amsl.technology amls} project.
 *
 * @copyright Copyright (c) 2014, {@link http://amsl.technology amsl}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

class IssnFinderJob extends Erfurt_Worker_Job_Abstract
{

    private $curl;
    private $modelIri;
    private $model;
    private $_erfurt;

    public function run($data)
    {
        $this->logSuccess('preparing to start IssnFinderJob: ' . print_r($data, true));
        $this->curl = curl_init();
        $this->_erfurt = Erfurt_App::getInstance();
        if (empty($data->modelIri)) {
            $this->logSuccess('started (without workload)');
        } else {
            $this->modelIri = $data->modelIri;
            $this->logSuccess('IssnFinderJob started with Model IRI ' . $data->modelIri);
            $this->model = new Erfurt_Owl_Model($data->modelIri);
            $arrayOfIssn = $this->getIssn();
            $this->logSuccess("nr of found issn: " . count($arrayOfIssn));
            $looseIssn = $this->extractLooseIssn($arrayOfIssn);
            $this->logSuccess('nr of proceeded issn: ' . count($looseIssn) . ' of ' . count($arrayOfIssn));
        }
        curl_close($this->curl);
        $objectCache            = $this->_erfurt->getCache();
        $objectCache->clean();
    }

    /*
     * Retrieve all ISSN that are contract items, have an
     */
    private function getIssn()
    {
        $sparql = '
            prefix bibrm: <http://vocab.ub.uni-leipzig.de/bibrm/>
            prefix umbel: <http://umbel.org/umbel#>
            SELECT ?issn WHERE {
                ?s a bibrm:ContractItem .
                ?s ?p ?issn .
                FILTER(regex(?p, "issn"))
                FILTER(regex(?issn, "urn:(issn|ISSN)"))
                #FILTER (NOT EXISTS {
                #    ?issn umbel:isLike ?zdb .
                #})
            }';

        //query selected model
        $result = $this->model->sparqlQuery($sparql);
        return $result;
    }

    private function extractLooseIssn($results)
    {
        // array to store the loose issn
        $return = array();

        foreach ($results as $arrayOfIssn) {
            $issn = $arrayOfIssn['issn'];
            $sparql = "
            prefix umbel: <http://umbel.org/umbel#>
            SELECT ?zdb WHERE {
                <$issn> umbel:isLike ?zdb .
            }";
            $result = $this->model->sparqlQuery($sparql);

            // if ask query returns nothing, its result is false (OntoWiki specific)
            if (empty($result)) {
                $this->logSuccess("issn: " . $issn);
                $answer = $this->askIssnResolver(substr($issn, -9));
                if (strpos($answer, '404 / ISSN not found') === false) {
                    $this->_import($answer);
                    $return[] = $issn;
                } else {
                    $this->logSuccess('ISSN-Resolver: ISSN not found');
                }
            } else {
                $this->logSuccess('result not empty, resource already imported');
            }
        }

        return $return;

    }

    private function askIssnResolver($issn)
    {
        $url = "http://data.ub.uni-leipzig.de/zdb/issn/$issn";
        $this->logSuccess('asking issn resolver for: ' . $issn);

        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($this->curl, CURLOPT_HEADER, 0);
        curl_setopt($this->curl, CURLOPT_FRESH_CONNECT, true);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->curl, CURLOPT_TIMEOUT, 30);
        $answer = curl_exec($this->curl);
        return $answer;
    }


    private function _import($data)
    {
        // action spec for versioning
        $versioning = $this->_erfurt->getVersioning();
        $actionSpec                = array();
        $actionSpec['type']        = 11;
        $actionSpec['modeluri']    = $this->modelIri;
        $actionSpec['resourceuri'] = $this->modelIri;

        // since the importer has problems with urn:issn:xxxx-xxxx uris,
        // we have to define the base as urn:issn:
        $dataWithBase = '@base <urn:ISSN:> . ';
        $dataWithBase .= $data;

        try {
            // starting versioning action
            $versioning->startAction($actionSpec);

            $locator = Erfurt_Syntax_RdfParser::LOCATOR_DATASTRING;
            $filetype = 'ttl';
            $this->logSuccess('trying to import into modelIRI ' . $this->modelIri);
            $this->_erfurt->getStore()->importRdf($this->modelIri, $data, $filetype, $locator);
            $this->logSuccess('finished importing');
            // stopping versioning action
            $versioning->endAction();

        } catch (Erfurt_Exception $e) {
            // re-throw
            throw new OntoWiki_Controller_Exception(
                'Could not import given model: ' . $e->getMessage(),
                0,
                $e
            );
        }
    }

}