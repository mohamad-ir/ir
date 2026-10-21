<?php

namespace App\Services\Learning;

use App\Mail\CertificateIssued;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Media;
use App\Models\User;
use App\Support\Settings;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Issues certificates of completion and renders them as PDF (Dompdf — pure PHP, works on shared hosting). */
class CertificateService
{
    /** Unambiguous characters for human-readable codes (no 0/O, 1/I/L). */
    protected const ALPHABET = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';

    public function issue(User $user, Course $course, bool $notify = true): ?Certificate
    {
        if (! $course->certificate_enabled || ! Settings::get('certificates.enabled', true)) {
            return null;
        }

        $existing = Certificate::where('user_id', $user->id)->where('course_id', $course->id)->first();
        if ($existing) {
            return $existing;
        }

        $certificate = Certificate::create([
            'code' => $this->newCode(),
            'user_id' => $user->id,
            'course_id' => $course->id,
            'recipient_name' => $user->displayName(),
            'course_title' => $course->title,
            'hours' => $course->cpd_hours ?: ($course->totalMinutes() ? round($course->totalMinutes() / 60, 1) : null),
            'issued_at' => now(),
        ]);

        if ($notify) {
            app(LearningService::class)->mail($user, new CertificateIssued($certificate));
        }

        return $certificate;
    }

    public function newCode(): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', (string) Settings::get('site.name', 'OA')), 0, 2) ?: 'OA');
        do {
            $random = collect(range(1, 8))->map(fn () => self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)])->implode('');
            $code = $prefix.'-'.substr($random, 0, 4).'-'.substr($random, 4);
        } while (Certificate::where('code', $code)->exists());

        return $code;
    }

    public function pdf(Certificate $certificate): string
    {
        $options = new Options;
        $options->setIsRemoteEnabled(false);
        $options->setDefaultFont('DejaVu Serif');
        $options->setChroot([base_path(), (string) config('filesystems.disks.public.root')]);
        $options->setTempDir(storage_path('framework/cache'));
        $options->setFontCache(storage_path('framework/cache'));

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->loadHtml(view('certificates.pdf', [
            'certificate' => $certificate,
            'logo' => $this->imageData(Settings::get('branding.logo_id')),
            'signature' => $this->imageData(Settings::get('certificates.signature_id')),
            'signatory' => Settings::get('certificates.signatory_name') ?: Settings::get('doctor.name'),
            'signatoryTitle' => Settings::get('certificates.signatory_title') ?: Settings::get('doctor.credentials'),
            'statement' => Settings::get('certificates.statement') ?: 'has successfully completed the online course',
            'primary' => Settings::get('theme.primary', '#12324f'),
            'accent' => Settings::get('theme.accent', '#0f8b8d'),
        ])->render());
        $dompdf->render();

        return $dompdf->output();
    }

    public function filename(Certificate $certificate): string
    {
        return 'certificate-'.Str::slug($certificate->course_title).'-'.$certificate->code.'.pdf';
    }

    /** Embeds a media-library image (PNG/JPEG original) as a data URI; Dompdf cannot read WebP. */
    protected function imageData(mixed $mediaId): ?string
    {
        $media = $mediaId ? Media::find($mediaId) : null;
        if (! $media || ! in_array($media->mime_type, ['image/png', 'image/jpeg'], true)) {
            return null;
        }
        $disk = Storage::disk($media->disk ?: 'public');
        if (! $disk->exists($media->path)) {
            return null;
        }

        return 'data:'.$media->mime_type.';base64,'.base64_encode($disk->get($media->path));
    }
}
