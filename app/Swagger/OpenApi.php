<?php

namespace App\Swagger;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Baballe Jaji Limited API",
 *     description="API documentation for Parts, Orders, Suppliers, Dashboard, and Reports"
 * )
 *
 * @OA\Server(
 *     url="/",
 *     description="Base server"
 * )
 *
 * @OA\SecurityScheme(
 *   securityScheme="bearerAuth",
 *   type="http",
 *   scheme="bearer",
 *   bearerFormat="Token"
 * )
 */
class OpenApi
{
}


