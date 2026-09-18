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
 *
 * The Content-Security-Policy is only applied in production because the
 * Vite development server runs on a separate port (e.g. localhost:5173)
 * which would be blocked by a strict 'self'-only policy.
 */
class AddSecurityHeaders
{
    /**
     * Build the Content-Security-Policy directive string.
     *
     * - Fonts are loaded from Bunny Fonts (fonts.bunny.net), which is the
     *   privacy-friendly CDN used by the laravel-vite-plugin fonts helper.
     * - 'unsafe-inline' is allowed for styles only, to support Blade-rendered
     *   class attributes and framework-injected style blocks.
     * - 'unsafe-eval' is intentionally excluded.
     */
    private function csp(): string
    {
        return "default-src 'self'; "
            ."script-src 'self'; "
            ."style-src 'self' 'unsafe-inline' https://fonts.bunny.net; "
            ."font-src 'self' https://fonts.bunny.net data:; "
            ."img-src 'self' data:; "
            ."connect-src 'self'; "
            ."frame-ancestors 'none'; "
            ."base-uri 'self'; "
            ."form-action 'self';";
    }

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

        // Apply CSP only in production.
        // In local/staging environments the Vite dev server runs on a different
        // port (e.g. localhost:5173) and would be blocked by a strict same-origin
        // policy, breaking hot module replacement and asset loading.
        if (config('app.env') === 'production' && ! $this->isFileDownload($response)) {
            $response->headers->set('Content-Security-Policy', $this->csp());
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
