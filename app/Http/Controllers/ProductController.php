<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

/**
 *
 */
class ProductController extends ApiController
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getAll(Request $request): JsonResponse
    {
        try {
            $products = Product::query();

            $perPage = $request->get('perPage', 20);
            $search = $request->get('search', '');

            if ($search && $search !== '') {
                $products = $products->where(function ($query) use ($search) {
                    $query->where('name', 'LIKE', '%' . $search . '%')
                        ->orWhere('description', 'LIKE', '%' . $search . '%');
                });
            }

            $categoryId = $request->get('category');

            if ($categoryId) {
                $products = $products->where('category_id', $categoryId);
            }

            $status = $request->get('status');

            if ($status) {
                $products = $products->where('status', $status);
            }

            $products = $products->paginate($perPage);

            $results = [
                'data' => $products->items(),
                'currentPage' => $products->currentPage(),
                'perPage' => $products->perPage(),
                'total' => $products->total(),
                'hasMorePages' => $products->hasMorePages()
            ];

            return $this->sendResponse($results);
        } catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function upload(Request $request)
    {
        if ($request->has('image')) {
            $file = $request->file('image');

            $filename = 'P'.time().'.'.$file->getClientOriginalExtension();

            $path = 'products/';

            Storage::putFileAs($path, $file, $filename);

            return $path.$filename;
        }
    }

    public function getAllProductsForCategory($categoryId)
    {
        $products = Product::where('category_id', $categoryId)
            ->orWhereHas('category', function ($query) use ($categoryId) {
               $query->where('parent_id', $categoryId)
                   ->orWhereHas('parent', function ($query) use ($categoryId) {
                       $query->where('parent_id', $categoryId);
                   });
            })->get();

//        $categories = [$categoryId];
//
//        $category = Category::find($categoryId);
//
//        if (count($category->childs) > 0) {
//            foreach ($category->childs as $subCategory) {
//                $categories[] = $subCategory->id;
//
//                if (count($subCategory->childs) > 0) {
//                    foreach ($subCategory->childs as $subSubCategory) {
//                        $categories[] = $subSubCategory->id;
//                    }
//                }
//            }
//        }
//
//        $products = Product::whereIn('category_id', $categories)->get();

        return $products->toArray();
    }


    public function get($id) {
        try {
            $product = Product::find($id);
            if(!$product) {
                return $this->sendError('Product doesn\'t exist', [], Response::HTTP_NOT_FOUND);
            }
            return $this->sendResponse($product->toArray());
        } catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function add(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'category_id'=>'required|exists:categories,id',
                'name' => 'required|min:5',
                'description' => 'required|min:10',
                'quantity' => 'numeric',
                'image' => 'nullable',
                'status' => 'integer|required|min:0|max:1'
            ]);

            if($validator->fails()) {
                return $this->sendError('Bad request!', $validator->messages()->toArray());
            }

            $product = new Product();
            $this->extracted($request, $product);

            return $this->sendResponse([], Response::HTTP_CREATED);
        } catch (Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update($id, Request $request) : JsonResponse {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|min:5',
                'category_id' => 'required|exists:categories,id',
                'description' => 'required|min:10',
                'quantity' => 'numeric',
                'image' => 'nullable',
                'status' => 'integer|required|min:0|max:1'
            ]);
            if($validator->fails()) {
                return $this->sendError('Bad request!', $validator->errors()->toArray());
            }

            $product = Product::find($id);
            if(!$product) {
                return $this->sendError('Product id doesn\'t exist');
            }
            return $this->sendResponse($request->all());
            $this->extracted($request, $product);
            return $this->sendResponse($product->toArray());
        } catch (Exception $exception) {
            Log::error($exception);
            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @param Request $request
     * @param $product
     * @return void
     */
    public function extracted(Request $request, $product): void
    {
        $product->category_id = $request->input('category_id');
        $product->name = $request->input('name');
        $product->description = $request->input('description');
        if (!$request->input('quantity')) {
            $product->quantity = 0;
        } else {
            $product->quantity = $request->input('quantity');
        }
        if ($request->input('image')) {
            $product->image = $this->upload($request);
        }
        $product->price = $request->input('price');
        $product->status = $request->input('status');
        $product->save();
    }

    public function delete($id) {
        try {
            $product = Product::find($id);
            if(!$product) {
                return $this->sendError('Product not found', [], Response::HTTP_NOT_FOUND);
            }
            DB::beginTransaction();
            if($product->image && Storage::exists($product->image)) {
                Storage::delete($product->image);
            }
            $product->delete();
            DB::commit();
            return $this->sendResponse([], Response::HTTP_NO_CONTENT);
        } catch (Exception $exception) {
            Log::error($exception);
            return $this->sendError('Something went wrong, please contact administrator!', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
