<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Website;
use Illuminate\Http\Request;

trait AuthorizesWebsiteAccess
{
    protected function authorizeWebsite(Request $request, Website $website): void
    {
        abort_unless($website->user_id === $request->user()->id, 403);
    }
}
