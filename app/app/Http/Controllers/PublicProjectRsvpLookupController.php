<?php

namespace App\Http\Controllers;

use App\Models\Guest;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicProjectRsvpLookupController extends Controller
{
    public function show(string $projectAlias): View
    {
        return view('public.project-rsvp-lookup', [
            'project' => $this->findProject($projectAlias),
        ]);
    }

    public function submit(Request $request, string $projectAlias): RedirectResponse
    {
        $project = $this->findProject($projectAlias);

        $data = $request->validate([
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'required_without:phone'],
            'phone' => ['nullable', 'string', 'max:255', 'required_without:email'],
        ]);

        $guest = $this->findMatchingGuest(
            $project,
            (string) $data['last_name'],
            $data['email'] ?? null,
            $data['phone'] ?? null,
        );

        if (! $guest) {
            return back()
                ->withInput()
                ->with('lookup_error', 'We could not access your RSVP with these details. Please contact the couple.');
        }

        return redirect()->route('public.rsvp.show', ['token' => $guest->rsvp_token]);
    }

    protected function findProject(string $projectAlias): Project
    {
        $project = Project::query()->where('alias', $projectAlias)->first();

        if (! $project && ctype_digit($projectAlias)) {
            $project = Project::query()->find((int) $projectAlias);
        }

        abort_if(! $project, 404);

        $website = $project->websiteConfiguration();

        abort_if(! ($website['settings']['published'] ?? true), 404);

        return $project;
    }

    protected function findMatchingGuest(Project $project, string $lastName, ?string $email, ?string $phone): ?Guest
    {
        $lastName = $this->normalizeText($lastName);
        $email = $this->normalizeText($email);
        $phone = $this->normalizePhone($phone);

        return $project->guests()
            ->get()
            ->first(function (Guest $guest) use ($lastName, $email, $phone): bool {
                $hasMatchingContact = ($email !== '' && $this->normalizeText($guest->email) === $email)
                    || ($phone !== '' && $this->normalizePhone($guest->phone) === $phone);

                return $hasMatchingContact && $this->guestHasLastName($guest, $lastName);
            });
    }

    protected function guestHasLastName(Guest $guest, string $lastName): bool
    {
        $lastNames = collect([
            $guest->primary_last_name,
            $guest->partner_last_name,
        ])->merge(
            collect($guest->additional_guests ?? [])->pluck('last_name'),
        );

        return $lastNames
            ->map(fn (mixed $value): string => $this->normalizeText($value))
            ->contains($lastName);
    }

    protected function normalizeText(mixed $value): string
    {
        return str((string) $value)->trim()->lower()->value();
    }

    protected function normalizePhone(mixed $value): string
    {
        return preg_replace('/\D+/', '', (string) $value) ?? '';
    }
}
