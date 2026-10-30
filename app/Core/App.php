<?php
declare(strict_types=1);

namespace App\Core;

use App\Controllers\Frontend\FallbackController;
use App\Middleware\{ActivityLogMiddleware, CsrfMiddleware, TenantMiddleware};
use App\Multisite\{DomainManager, TenantResolver};

final class App
{
    public function __construct(private Container $c, private string $root)
    {
    }

    public function handle(Request $req): Response
    {
        try {
            $this->c->set(Request::class, $req);
            $this->c->get(Session::class)->start($req->secure);
            // The platform host serves /platform/*. If the same host is also a site's domain (single-domain install),
            // every other path belongs to that site; on a platform host with no site, everything stays platform.
            $isPlatform = $req->host === DomainManager::normalize(Env::get('PLATFORM_HOST', '') ?? '')
                && (str_starts_with($req->path, '/platform') || $this->c->get(TenantResolver::class)->resolve($req->host) === null);
            $router = new Router();
            (require $this->root . ($isPlatform ? '/routes/platform.php' : '/routes/site.php'))($router);
            $match = $router->match($req->method, $req->path);
            if ($match === null) {
                $res = $isPlatform || $req->host === ''
                    ? Response::html(View::render('errors/message', ['title' => 'Page introuvable', 'message' => 'Cette page n\'existe pas.']), 404)
                    : $this->c->get(TenantMiddleware::class)->handle($req, fn() => $this->c->get(FallbackController::class)->notFound($req, []));
            } else {
                [$handler, $params, $routeMw] = $match;
                $stack = [...($isPlatform ? [] : [TenantMiddleware::class]), CsrfMiddleware::class, ActivityLogMiddleware::class, ...$routeMw];
                $next = fn(Request $r) => $this->c->get($handler[0])->{$handler[1]}($r, $params);
                foreach (array_reverse($stack) as $spec) {
                    [$cls, $arg] = array_pad(explode(':', $spec, 2), 2, null);
                    $mw = $this->c->get($cls);
                    $prev = $next;
                    $next = fn(Request $r) => $mw->handle($r, $prev, $arg);
                }
                $res = $next($req);
            }
        } catch (\Throwable $e) {
            @file_put_contents($this->root . '/storage/logs/app.log', date('c') . ' ' . $e . "\n", FILE_APPEND);
            $msg = Env::get('APP_DEBUG') === '1' ? $e->getMessage() : 'Une erreur est survenue.';
            $res = Response::html(View::render('errors/message', ['title' => 'Erreur serveur', 'message' => $msg]), 500);
        }
        if ($req->secure) { // no includeSubDomains: tenants may live on sub-domains of the base domain
            $res->headers['Strict-Transport-Security'] = 'max-age=31536000';
        }
        $res->headers += [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Content-Security-Policy' => "default-src 'self'; img-src 'self' data: https:; style-src 'self'; script-src 'self'; frame-ancestors 'none'; form-action 'self'; frame-src https://www.youtube-nocookie.com https://player.vimeo.com https://www.openstreetmap.org; media-src 'self' https:",
        ];
        return $res;
    }
}
