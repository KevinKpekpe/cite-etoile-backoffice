<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds HTTP security headers to every response.
 *
 * Hardens the application against clickjacking, MIME-type sniffing,
 * information leakage, and cross-site scripting at the transport layer.
 */
class AddSecurityHeaders
{
    /**
     * Content-Security-Policy directives.
     *
     * Restricts origins for each resource type. 'unsafe-inline' is allowed
     * only for styles to support Blade-rendered class attributes.
     * 'unsafe-eval' is intentionally excluded.
     */
    private const CSP = "default-src 'self'; "
        ."script-src 'self'; "
        ."style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
        ."font-src 'self' https://fonts.gstatic.com; "
        ."img-src 'self' data:; "
        ."connect-src 'self'; "
        ."frame-ancestors 'none'; "
        ."base-uri 'self'; "
        ."form-action 'self';";

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevent the page from being embedded inside an <iframe> by any origin.
        $response->headers->set('X-Frame-Options', 'DENY');

        // Prevent the browser from guessing the MIME type (sniffing).
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Instruct the browser to send the full origin only for same-origin requests.
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Restrict powerful browser features that the app does not require.
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=()',
        );

        // Apply the Content-Security-Policy on non-download responses only.
        // Streaming file downloads must not be constrained by CSP.
        if (! $this->isFileDownload($response)) {
            $response->headers->set('Content-Security-Policy', self::CSP);
        }

        return $response;
    }

    /**
     * Determine whether the response is a file download.
     *
     * File downloads (PDF receipts, CSV exports, document attachments) carry
     * a Content-Disposition attachment header and must not be blocked by CSP.
     */
    private function isFileDownload(Response $response): bool
    {
        $disposition = $response->headers->get('Content-Disposition', '');

        return str_contains($disposition, 'attachment');
    }
}
