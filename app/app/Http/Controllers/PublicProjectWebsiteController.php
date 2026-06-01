<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\View\View;

class PublicProjectWebsiteController extends Controller
{
    public function __invoke(string $projectAlias): View
    {
        $project = Project::query()->where('alias', $projectAlias)->first();

        if (! $project && ctype_digit($projectAlias)) {
            $project = Project::query()->find((int) $projectAlias);
        }

        abort_if(! $project, 404);

        $website = $project->websiteConfiguration();

        abort_if(! ($website['settings']['published'] ?? true), 404);

        return view('public.project-website', [
            'project' => $project,
            'website' => $website,
            'guest' => request('rsvp')
                ? $project->guests()->where('rsvp_token', request('rsvp'))->first()
                : null,
        ]);
    }
}
