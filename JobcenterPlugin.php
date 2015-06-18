<?php

/**
 * This file is part of the {@link http://ontowiki.net OntoWiki} project.
 *
 * @copyright Copyright (c) 2014, {@link http://aksw.org AKSW}
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 */
class JobcenterPlugin extends OntoWiki_Plugin
{
    public function onAnnounceWorker($event)
    {
        $event->registry->registerJob(
            "IssnFinderJob",                                    //  job key name
            "extensions/jobcenter/jobs/IssnFinderJob.php",       //  job class file
            "IssnFinderJob"                        //  job class name
        );
    }
}