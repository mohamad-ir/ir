<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Services\Learning\CertificateService;
use App\Services\Seo\SeoManager;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    /** Public verification page: anyone with the code can confirm a certificate is genuine. */
    public function show(string $code, Request $request, SeoManager $seo)
    {
        $certificate = Certificate::where('code', strtoupper($code))->with('course')->firstOrFail();
        $seo->title('Certificate '.$certificate->code)->noindex();

        return view('certificates.show', [
            'certificate' => $certificate,
            'canDownload' => $this->canDownload($certificate, $request),
        ]);
    }

    public function download(string $code, Request $request, CertificateService $certificates)
    {
        $certificate = Certificate::where('code', strtoupper($code))->firstOrFail();
        abort_unless($this->canDownload($certificate, $request) && $certificate->isValid(), 403);

        return response($certificates->pdf($certificate), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$certificates->filename($certificate).'"',
        ]);
    }

    protected function canDownload(Certificate $certificate, Request $request): bool
    {
        $user = $request->user();

        return $user && ($user->id === $certificate->user_id || $user->can('courses.view'));
    }
}
