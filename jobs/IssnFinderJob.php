<?php

/**
 * This file is part of the {@link http://amsl.technology amls} project.
 *
 * @copyright Copyright (c) 2014, {@link http://amsl.technology amsl}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */

class IssnFinderJob
{

    private $curl;
    private $modelIri;
    private $store;
    private $_erfurt;
    private $logger;

    function __construct()
    {
        $this->logger = OntoWiki::getInstance()->getCustomLogger("jobcenter-issnfinderjob");
        $owApp       = OntoWiki::getInstance();
        $this->store = $owApp->erfurt->getStore();
        $this->_erfurt = Erfurt_App::getInstance();
    }

    /**
     * Basically mimics the LinkedDataGatherer functionality. Not used atm.
     */
    public function run($data)
    {
        $this->logger->debug('preparing to start IssnFinderJob: ' . print_r($data, true));
        $this->logger->debug('ModelIri: ' . $data['modelIri']);
        $this->curl = curl_init();
        if (empty($data['modelIri'])) {
            $this->logger->debug('started (without workload)');
        } else {
            $this->modelIri = $data['modelIri'];
            $this->logger->debug('IssnFinderJob started with Model IRI ' . $data->modelIri);
            $arrayOfIssn = $this->getIssn();
            $this->logger->debug("nr of found issn: " . count($arrayOfIssn));
            $looseIssn = $this->extractLooseIssn($arrayOfIssn);
            $this->logger->debug('nr of proceeded issn: ' . count($looseIssn) . ' of ' . count($arrayOfIssn));
        }
        curl_close($this->curl);
        $objectCache            = $this->_erfurt->getCache();
        $objectCache->clean();
    }

    /*
     * Retrieve all ISSN that are contract items, have an
     */
    public function getIssn()
    {
        $sparql = '
        prefix amsl: <http://vocab.ub.uni-leipzig.de/amsl/>
        prefix umbel: <http://umbel.org/umbel#>
        prefix rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#>
        prefix foaf: <http://xmlns.com/foaf/0.1/>
        SELECT DISTINCT ?issn
        WHERE {
            ?s a amsl:ContractItem .
            ?s ?p ?issn .
            FILTER(regex(?p, "issn"))
            FILTER(regex(?issn, "urn:(issn|ISSN)"))
        }';

        //query selected model
        $this->logger->debug('querying ' . $sparql);

        //disable AC to be able to access graphs that would require login (was needed for the usage as gearman job)
//        $result = $this->store->sparqlQuery($sparql, array(Erfurt_Store::USE_AC => false));
        $result = $this->store->sparqlQuery($sparql);
//        $this->logSuccess('result:  ' . print_r($result, true));
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
            $result = $this->store->sparqlQuery($sparql);

            // if ask query returns nothing, its result is false (OntoWiki specific)
            if (empty($result)) {
                $this->logger->debug("issn: " . $issn);
                $answer = $this->askIssnResolver(substr($issn, -9));
                if (strpos($answer, '404 / ISSN not found') === false) {
                    $this->_import($answer);
                    $return[] = $issn;
                } else {
                    $this->logger->debug('ISSN-Resolver: ISSN not found');
                }
            } else {
                $this->logger->debug('result not empty, resource already imported');
            }
        }

        return $return;

    }

    private function askIssnResolver($issn)
    {
        $url = "http://data.ub.uni-leipzig.de/zdb/issn/$issn";
        $this->logger->debug('asking issn resolver for: ' . $issn);

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
//        $dataWithBase = '@base <urn:ISSN:> . ';
//        $dataWithBase .= $data;

        try {
            // starting versioning action
            $versioning->startAction($actionSpec);

            $locator = Erfurt_Syntax_RdfParser::LOCATOR_DATASTRING;

            // parse the response from the issn resolver
            $filetype = 'n3';

            $this->logger->debug('trying to import into modelIRI ' . $this->modelIri . ": " . $data);
            $this->_erfurt->getStore()->importRdf($this->modelIri, $data, $filetype, $locator);
            $this->logger->debug('finished importing');
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