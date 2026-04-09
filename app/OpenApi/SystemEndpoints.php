<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Placeholder de endpoints del sistema.
 * El endpoint /health puede removerse cuando AuthController tenga sus anotaciones.
 */
class SystemEndpoints
{
    #[OA\Get(
        path: '/health',
        operationId: 'healthCheck',
        summary: 'Health check',
        description: 'Verifica que la API esté operativa.',
        tags: ['System'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'API operativa',
                content: new OA\JsonContent(
                    properties: [new OA\Property(property: 'status', type: 'string', example: 'ok')]
                )
            ),
        ]
    )]
    public function health(): void {}
}
