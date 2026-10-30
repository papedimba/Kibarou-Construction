<?php
declare(strict_types=1);

namespace App\Core;

use App\Middleware\{CsrfMiddleware, TenantMiddleware};
use App\Multisite\DomainManager;

final class App
{
    public function __construct(private Container $c, private string $root)
    {
    }

    public function handle(Request $req): Response
    {
        try {
            $this->c->get(Session::class)->start($req->secure);
            $isPlatform = $req->host === DomainManager::normalize(Env::get('PLATFORM_HOST', '') ?? '');
            $router = new Router();
            (require $this->root . ($isPlatform ? '/routes/platform.php' : '/routes/site.php'))($router);
            $match = $router->match($req->method, $req->path);
            if ($match === null) {
                $res = $isPlatform || $req->host === ''
                    ? Response::html(View::render('errors/message', ['title' => 'Page introuvable', 'message' => 'Cette page n\'existe pas.']), 404)
                    : $this->c->get(TenantMiddleware::class)->handle($req, fn() => Response::html(View::render('errors/message', ['title' => 'Page introuvable', 'message' => 'Cette page n\'existe pas.']), 404));
            } else {
                [$handler, $params, $routeMw] = $match;
                $stack = [...($isPlatform ? [] : [TenantMiddleware::class]), CsrfMiddleware::class, ...$routeMw];
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
        $res->headers += [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Content-Security-Policy' => "default-src 'self'; img-src 'self' data: https:; style-src 'self'; script-src 'self'; frame-ancestors 'none'; form-action 'self'",
        ];
        return $res;
    }
}
