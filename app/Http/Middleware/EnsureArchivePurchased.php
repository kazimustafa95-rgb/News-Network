<?php

namespace App\Http\Middleware;

use App\Services\Archive\ArchiveService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureArchivePurchased
{
    public function __construct(private readonly ArchiveService $archiveService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $countyId = (int) $request->route('county_id', $request->input('county_id'));
        $archiveDate = (string) $request->route('archive_date', $request->input('archive_date'));

        if (! $user || ! $this->archiveService->userHasArchiveAccess($user, $countyId, $archiveDate)) {
            abort(403, 'Archive access has not been purchased for this county and date.');
        }

        return $next($request);
    }
}
