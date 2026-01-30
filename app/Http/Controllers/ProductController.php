<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Product;  // se importa el mode de Producto para traer el modelo de datos
use Illuminate\Support\Facades\Validator;  // se importa el validador para validar los datos que vienen del front

class ProductController extends Controller
{
    //GET
    public function index()
    {
        $students = Product::all(); // se obtienen todos los productos de la base de datos

        if ($students->isEmpty()) {
            $data = [
                'message' => 'No products found',
                'status' => 200
            ]; // si no hay productos, se retorna un mensaje de error
            return response()->json($data, 404);
        }
        return response()->json($students); // se retornan los productos en formato JSON
    }

    //POST
    public function store(Request $request) // store para almacenar lo que vienn del front
    {
        $validator = validator::make($request->all(), [  // aca valiamos los datos que vienen del front como zod
            'name' => 'required|string|max:255',
            'description' => 'required|nullable|string',
            'price' => 'required|numeric|min:0',
        ]);
        // luego se valida si el validador falla
        if ($validator->fails()) {
            $data = [
                'message' => 'Error de validación',
                'errors' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }
        // si todo esra correcto, se crea el producto
        $product = Product::create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
        ]);

        // si no se creo un producto, se retorna un error
        if (!$product) {
            $data = [
                'message' => 'Error al crear el producto',
                'status' => 500
            ];
            return response()->json($data, 500);
        }

        //si se creo el producto, se retorna un mensaje de exito
        $data = [
            'message' => 'Producto creado exitosamente',
            'status' => 201
        ];
        return response()->json($data, 201);
    }

    //GET FOR ID
    public function show($id)
    {
        $product = Product::find($id); // se busca el producto por id
        if (!$product) {
            $data = [
                'message' => 'Producto no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }

        $data = [
            'product' => $product,
            'status' => 200
        ];
        return response()->json($data, 200);
    }

    //DELETE FOR ID
    public function destroy($id)
    {
        $product = Product::find($id);
        if (!$product) {
            $data = [
                'message' => 'Producto no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
        $product->delete();
        $data = [
            'message' => 'Producto eliminado exitosamente',
            'status' => 200
        ];
        return response()->json($data, 200);
    }

    //PUT
    public function update(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) {
            $data = [
                'message' => 'Producto no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|nullable|string',
            'price' => 'required|numeric|min:0',
        ]);
        if ($validator->fails()) {
            $data = [
                'message' => 'Error de validación',
                'errors' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }
        $product->name = $request->name;
        $product->description = $request->description;
        $product->price = $request->price;
        $product->save();
        $data = [
            'message' => 'Producto actualizado exitosamente',
            'status' => 200
        ];
        return response()->json($data, 200);
    }

    //PAT
    public function updatePartial(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) {
            $data = [
                'message' => 'Producto no encontrado',
                'status' => 404
            ];
            return response()->json($data, 404);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'price' => 'numeric|min:0',
        ]);
        if ($validator->fails()) {
            $data = [
                'message' => 'Error de validación',
                'errors' => $validator->errors(),
                'status' => 400
            ];
            return response()->json($data, 400);
        }
        //para actilzar un campo individualmente
        if ($request->has('name')) {
            $product->name = $request->name;
        }
        if ($request->has('description')) {
            $product->description = $request->description;
        }
        if ($request->has('price')) {
            $product->price = $request->price;
        }
        $product->save();
        $data = [
            'message' => 'Producto actualizado exitosamente',
            'product' => $product,
            'status' => 200
        ];
        return response()->json($data, 200);
    }
}
