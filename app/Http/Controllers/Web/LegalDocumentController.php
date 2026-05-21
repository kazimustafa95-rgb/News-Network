<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\LegalDocument;
use Illuminate\Contracts\View\View;

class LegalDocumentController extends Controller
{
    public function terms(): View
    {
        return $this->renderPublishedDocument('terms-and-conditions');
    }

    public function privacy(): View
    {
        return $this->renderPublishedDocument('privacy-policy');
    }

    private function renderPublishedDocument(string $slug): View
    {
        $document = LegalDocument::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return view('legal.show', [
            'document' => $document,
        ]);
    }
}
