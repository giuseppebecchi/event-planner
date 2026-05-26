<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\View\View;

class PublicProjectWebsiteController extends Controller
{
    public function __invoke(Project $project): View
    {
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
