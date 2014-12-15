<?php

/**
 * This file is part of the {@link http://ontowiki.net OntoWiki} project.
 *
 * @copyright Copyright (c) 2014, {@link http://aksw.org AKSW}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */
class IssnFinderJob extends Erfurt_Worker_Job_Abstract
{

    public function run($load)
    {
        if (empty($load)) {
            $this->logSuccess('started (without workload)');
        } else {
            $this->logSuccess('IssnFinderJob started: ' . print_r($load, true));
        }
    }
}