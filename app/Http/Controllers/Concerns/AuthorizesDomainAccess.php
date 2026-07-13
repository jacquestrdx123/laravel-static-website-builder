<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Domain;
use Illuminate\Http\Request;

trait AuthorizesDomainAccess
{
    protected function authorizeDomain(Request $request, Domain $domain): void
    {
        abort_unless($domain->user_id === $request->user()->id, 403);
    }
}
