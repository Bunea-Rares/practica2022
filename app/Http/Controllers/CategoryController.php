<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 *
 */
class CategoryController extends ApiController
{
    /**
     * @param Request $request
     */
    public function getAll(Request $request)
    {
        if(Category::all()->first() != null)
            return $this->sendResponse(Category::all()->toArray());
        return $this->sendError('Db is empty');
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function add(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|max:50',
                'parent_id' => 'nullable|exists:categories,id'
            ]);

            if ($validator->fails()) {
                return $this->sendError('Bad request!', $validator->messages()->toArray());
            }

            $name = $request->get('name');
            $parentId = $request->get('parent_id');

            if ($parentId) {
                $parent = Category::find($parentId);

                if ($parent->parent?->parent) {
                    return $this->sendError('You can\'t add a 3rd level subcategory!');
                }
            }

            $category = new Category();
            $category->name = $name;
            $category->parent_id = $parentId;
            $category->save();

            return $this->sendResponse($category->toArray());
        } catch (\Exception $exception) {
            Log::error($exception);

            return $this->sendError('Something went wrong, please contact administrator!');
        }
    }

    /**
     * @param Category $category
     */
    public function get(Category $category)
    {
        if($category->exists()) {
            return $this->sendResponse($category->toArray());
        }
        return $this->sendError("Id doesn't exists");
    }

    /**
     * @param $id
     * @param Request $request
     */
    public function update($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:50',
            'parent_id' => 'nullable|exists:categories,id'
        ]);
        if($validator->fails()) {
            return $this->sendError('Bad request', $validator->messages()->toArray());
        }
        $category = Category::find($id);
        if($category == null) {
            return $this->sendError("Category doesn't exists");
        }
        if ($request->input('parent_id')) {
            $parent = Category::find($request->input('parent_id'));

            if ($parent->parent?->parent) {
                return $this->sendError('You can\'t add a 3rd level subcategory!');
            }
        }
        if($category->parent_id == $request->input('parent_id'))
            return $this->sendError("Your request id and id can't match");
        $category->parent_id = $request->input('parent_id');
        $category->name = $request->input('name');
        $category->save();
        return $this->sendResponse($category->toArray());
    }

    /**
     * @param $id
     */
    public function delete($id)
    {
        $category = Category::find($id);
        if($category == null) {
            return $this->sendError("Category doesn't exist");
        }
        $category->children()->delete();
        $category->delete();
        return $this->sendResponse('Success');
    }
}
