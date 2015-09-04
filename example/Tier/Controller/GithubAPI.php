<?php


namespace Tier\Controller;

use GithubService\GithubArtaxService\GithubService;
use Tier\Data\ErrorInfo;

class GithubAPI {

    function display(GithubService $githubService)
    {
        try {
            $repoCommitsCommand = $githubService->listRepoCommits(null, 'danack', 'imagick-demos');
            $repoCommitsCommand->setPerPage(10);
            $commits = $repoCommitsCommand->execute();

            return getTemplateCallable('pages/commits', ['GithubService\Model\Commits' => $commits]);   
        }
        catch (\Exception $e) {
            $errorInfo = new ErrorInfo(
                "Error getting commits",
                $e->getMessage()
            );

            return getTemplateCallable('pages/error', ['Tier\Data\ErrorInfo' => $errorInfo]);
        }
    }
}

