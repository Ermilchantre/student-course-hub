<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Slim\Psr7\Response as SlimResponse;

class AuthMiddleware implements MiddlewareInterface
{
    private $logger;
    private $basePath;

    public function __construct(LoggerInterface $logger, string $basePath = '')
    {
        $this->logger = $logger;
        $this->basePath = $basePath;
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            $this->logger->warning(
                'Unauthorized admin access attempt',
                [
                'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown',
                'path' => $request->getUri()->getPath()
                ]
            );

            $response = new SlimResponse();
            return $response->withHeader('Location', $this->basePath . '/admin/login')
                ->withStatus(302);
        }

        return $handler->handle($request);
    }
}
