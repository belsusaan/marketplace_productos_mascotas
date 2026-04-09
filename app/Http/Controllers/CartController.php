<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateCartItemRequest;
use App\Http\Resources\CartResource;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class CartController extends Controller
{
    #[OA\Get(
        path: '/cart',
        operationId: 'getCart',
        summary: 'Ver carrito actual',
        description: 'Devuelve el carrito del usuario autenticado con todos sus items. Si no existe, lo crea automáticamente.',
        tags: ['Carrito'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Carrito del usuario',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'user_id', type: 'integer', example: 1),
                            new OA\Property(property: 'cart_items', type: 'array', items: new OA\Items(
                                properties: [
                                    new OA\Property(property: 'id', type: 'integer', example: 1),
                                    new OA\Property(property: 'product_id', type: 'integer', example: 3),
                                    new OA\Property(property: 'quantity', type: 'integer', example: 2),
                                    new OA\Property(property: 'unit_price', type: 'number', format: 'float', example: 25.50),
                                ]
                            )),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 500, description: 'Error interno del servidor'),
        ]
    )]
    public function index(Request $request): CartResource
    {
        $cart = Cart::with('cartItems.product')
            ->firstOrCreate(['user_id' => $request->user()->id]);

        $this->authorize('view', $cart);

        return new CartResource($cart);
    }

    #[OA\Post(
        path: '/cart',
        operationId: 'addCartItem',
        summary: 'Agregar item al carrito',
        description: 'Agrega un producto al carrito. Si el producto ya existe, incrementa la cantidad. Valida stock disponible.',
        tags: ['Carrito'],
        security: [['BearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['product_id', 'quantity'],
                properties: [
                    new OA\Property(property: 'product_id', type: 'integer', example: 1),
                    new OA\Property(property: 'quantity', type: 'integer', minimum: 1, example: 2),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Item agregado al carrito',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'user_id', type: 'integer', example: 1),
                            new OA\Property(property: 'cart_items', type: 'array', items: new OA\Items(type: 'object')),
                        ]),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Stock insuficiente o error de validación',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Stock insuficiente. Disponible: 3'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 500, description: 'Error interno del servidor'),
        ]
    )]
    public function store(Request $request): JsonResponse|CartResource
    {
        $this->authorize('create', Cart::class);

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($request->product_id);

        if ($product->stock < $request->quantity) {
            return response()->json([
                'message' => 'Stock insuficiente. Disponible: ' . $product->stock
            ], 422);
        }

        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);
        $item = $cart->cartItems()->where('product_id', $product->id)->first();

        if ($item) {
            $newQty = $item->quantity + $request->quantity;
            if ($product->stock < $newQty) {
                return response()->json([
                    'message' => 'Stock insuficiente. Disponible: ' . $product->stock
                ], 422);
            }
            $item->update(['quantity' => $newQty]);
        } else {
            $cart->cartItems()->create([
                'product_id' => $product->id,
                'quantity'   => $request->quantity,
                'unit_price' => $product->price,
            ]);
        }

        $cart->load('cartItems.product');

        return new CartResource($cart);
    }

    #[OA\Put(
        path: '/cart/{item_id}',
        operationId: 'updateCartItem',
        summary: 'Actualizar cantidad de un item',
        description: 'Modifica la cantidad de un item específico del carrito. Valida stock disponible.',
        tags: ['Carrito'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'item_id',
                in: 'path',
                required: true,
                description: 'ID del item en el carrito',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['quantity'],
                properties: [
                    new OA\Property(property: 'quantity', type: 'integer', minimum: 1, example: 3),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cantidad actualizada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'cart_items', type: 'array', items: new OA\Items(type: 'object')),
                        ]),
                    ]
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Stock insuficiente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Stock insuficiente. Disponible: 2'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Item no encontrado'),
            new OA\Response(response: 500, description: 'Error interno del servidor'),
        ]
    )]
    public function update(UpdateCartItemRequest $request, $item_id): JsonResponse|CartResource
    {
        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);
        $this->authorize('update', $cart);

        $item = $cart->cartItems()->findOrFail($item_id);

        if ($item->product->stock < $request->quantity) {
            return response()->json([
                'message' => 'Stock insuficiente. Disponible: ' . $item->product->stock
            ], 422);
        }

        $item->update(['quantity' => $request->quantity]);
        $cart->load('cartItems.product');

        return new CartResource($cart);
    }

    #[OA\Delete(
        path: '/cart/{item_id}',
        operationId: 'removeCartItem',
        summary: 'Eliminar un item del carrito',
        description: 'Elimina un producto específico del carrito del usuario autenticado.',
        tags: ['Carrito'],
        security: [['BearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'item_id',
                in: 'path',
                required: true,
                description: 'ID del item a eliminar',
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Item eliminado. Devuelve el carrito actualizado.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'cart_items', type: 'array', items: new OA\Items(type: 'object')),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 404, description: 'Item no encontrado'),
            new OA\Response(response: 500, description: 'Error interno del servidor'),
        ]
    )]
    public function destroy(Request $request, $item_id): CartResource
    {
        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);
        $this->authorize('delete', $cart);

        $item = $cart->cartItems()->findOrFail($item_id);
        $item->delete();

        $cart->load('cartItems.product');

        return new CartResource($cart);
    }

    #[OA\Delete(
        path: '/cart',
        operationId: 'clearCart',
        summary: 'Vaciar carrito completo',
        description: 'Elimina todos los items del carrito del usuario autenticado.',
        tags: ['Carrito'],
        security: [['BearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Carrito vaciado. Devuelve el carrito vacío.',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'object', properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 1),
                            new OA\Property(property: 'cart_items', type: 'array', items: new OA\Items(type: 'object'), example: []),
                        ]),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 500, description: 'Error interno del servidor'),
        ]
    )]
    public function clear(Request $request): CartResource
    {
        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);
        $this->authorize('delete', $cart);

        $cart->cartItems()->delete();
        $cart->load('cartItems.product');

        return new CartResource($cart);
    }
}