<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Jetstream\Http\Controllers\CurrentTeamController as JetstreamCurrentTeamController;

class CustomCurrentTeamController extends JetstreamCurrentTeamController
{
    /**
     * Update the authenticated user's current team.
     */
    public function update(Request $request)
    {
        // Call the parent method to update the team
        parent::update($request);

        // Redirect back to the previous page instead of dashboard
        return redirect()->back();
    }
}
