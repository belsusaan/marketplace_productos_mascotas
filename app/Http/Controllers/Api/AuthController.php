<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[OA\Post(
        path: '/register',
        operationId: 'register',
        summary: 'Registrar un nuevo usuario',
        description: 'Crea una cuenta nueva. El rol asignado por defecto es "buyer".',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Juan Pérez'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'juan@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, example: 'secret123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'secret123'),
                    new OA\Property(property: 'phone', type: 'string', nullable: true, example: '+54 9 11 1234-5678'),
                    new OA\Property(property: 'address', type: 'string', nullable: true, example: 'Av. Corrientes 1234, CABA'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Usuario registrado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'token', type: 'string', example: '1|abc123...'),
                        new OA\Property(
                            property: 'user',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'Juan Pérez'),
                                new OA\Property(property: 'email', type: 'string', example: 'juan@example.com'),
                                new OA\Property(property: 'phone', type: 'string', nullable: true, example: '+54 9 11 1234-5678'),
                                new OA\Property(property: 'address', type: 'string', nullable: true, example: 'Av. Corrientes 1234, CABA'),
                                new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), example: ['buyer']),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-04-08T12:00:00.000000Z'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validación',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'El email ya está registrado.'),
                        new OA\Property(property: 'errors', type: 'object', example: ['email' => ['Este email ya está registrado.']]),
                    ]
                )
            ),
            new OA\Response(response: 500, description: 'Error interno del servidor'),
        ]
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'phone'    => $request->phone,
            'address'  => $request->address,
        ]);

        // TODO: Asignar rol 'buyer' por defecto una vez que Persona 4 seedee los roles.
        // $user->assignRole('buyer');

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => new UserResource($user),
        ], 201);
    }

    #[OA\Post(
        path: '/login',
        operationId: 'login',
        summary: 'Iniciar sesión',
        description: 'Autentica al usuario y devuelve un Bearer Token para usar en los endpoints protegidos.',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'juan@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret123'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login exitoso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'token', type: 'string', example: '2|xyz789...'),
                        new OA\Property(
                            property: 'user',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'Juan Pérez'),
                                new OA\Property(property: 'email', type: 'string', example: 'juan@example.com'),
                                new OA\Property(property: 'phone', type: 'string', nullable: true, example: '+54 9 11 1234-5678'),
                                new OA\Property(property: 'address', type: 'string', nullable: true, example: 'Av. Corrientes 1234, CABA'),
                                new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), example: ['buyer']),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-04-08T12:00:00.000000Z'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Credenciales inválidas',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Credenciales inválidas.'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validación',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'El email es obligatorio.'),
                        new OA\Property(property: 'errors', type: 'object', example: ['email' => ['El email es obligatorio.']]),
                    ]
                )
            ),
            new OA\Response(response: 500, description: 'Error interno del servidor'),
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Credenciales inválidas.'], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => new UserResource($user),
        ]);
    }

    #[OA\Post(
        path: '/logout',
        operationId: 'logout',
        summary: 'Cerrar sesión',
        description: 'Revoca el token actual. Requiere autenticación.',
        tags: ['Auth'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sesión cerrada exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Sesión cerrada correctamente.'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ]
                )
            ),
            new OA\Response(response: 500, description: 'Error interno del servidor'),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }

    #[OA\Get(
        path: '/user',
        operationId: 'me',
        summary: 'Obtener perfil del usuario autenticado',
        description: 'Devuelve los datos del usuario cuyo token se envía en el header.',
        tags: ['Users'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Perfil del usuario',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'Juan Pérez'),
                                new OA\Property(property: 'email', type: 'string', example: 'juan@example.com'),
                                new OA\Property(property: 'phone', type: 'string', nullable: true, example: '+54 9 11 1234-5678'),
                                new OA\Property(property: 'address', type: 'string', nullable: true, example: 'Av. Corrientes 1234, CABA'),
                                new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), example: ['buyer']),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-04-08T12:00:00.000000Z'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ]
                )
            ),
            new OA\Response(response: 500, description: 'Error interno del servidor'),
        ]
    )]
    public function me(Request $request): JsonResponse
    {
        return response()->json(new UserResource($request->user()));
    }

    #[OA\Put(
        path: '/user',
        operationId: 'updateUser',
        summary: 'Actualizar perfil del usuario autenticado',
        description: 'Actualiza uno o más campos del perfil. Todos los campos son opcionales.',
        tags: ['Users'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Juan Pérez Actualizado'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'nuevo@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, example: 'nuevapass123'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'nuevapass123'),
                    new OA\Property(property: 'phone', type: 'string', nullable: true, example: '+54 9 11 9999-8888'),
                    new OA\Property(property: 'address', type: 'string', nullable: true, example: 'Av. Santa Fe 456, CABA'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Perfil actualizado exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'name', type: 'string', example: 'Juan Pérez Actualizado'),
                                new OA\Property(property: 'email', type: 'string', example: 'nuevo@example.com'),
                                new OA\Property(property: 'phone', type: 'string', nullable: true, example: '+54 9 11 9999-8888'),
                                new OA\Property(property: 'address', type: 'string', nullable: true, example: 'Av. Santa Fe 456, CABA'),
                                new OA\Property(property: 'roles', type: 'array', items: new OA\Items(type: 'string'), example: ['buyer']),
                                new OA\Property(property: 'created_at', type: 'string', format: 'date-time', example: '2026-04-08T12:00:00.000000Z'),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validación',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Este email ya está registrado por otro usuario.'),
                        new OA\Property(property: 'errors', type: 'object', example: ['email' => ['Este email ya está registrado por otro usuario.']]),
                    ]
                )
            ),
            new OA\Response(response: 500, description: 'Error interno del servidor'),
        ]
    )]
    public function update(UpdateUserRequest $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->only(['name', 'email', 'phone', 'address']);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        return response()->json(new UserResource($user->fresh()));
    }

    #[OA\Delete(
        path: '/user',
        operationId: 'deleteUser',
        summary: 'Eliminar cuenta del usuario autenticado',
        description: 'Elimina la cuenta y revoca todos los tokens del usuario autenticado.',
        tags: ['Users'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cuenta eliminada exitosamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Cuenta eliminada correctamente.'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
                    ]
                )
            ),
            new OA\Response(response: 500, description: 'Error interno del servidor'),
        ]
    )]
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Cuenta eliminada correctamente.']);
    }
}
