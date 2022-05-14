<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Repositories\CategoryRepository;
use Illuminate\Http\Request;
use Swagger\Annotations as SWG;

class CategoryController extends Controller
{
    protected $categoryRepository;
    protected $postRepository;
    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }
    /**
     * @SWG\Get(
     *     path="/api/get-discount-blog-by-category",
     *     summary="Danh sách tin tức thông báo khuyến mại",
     *     tags={"Blogs"},
     *     description="Danh sách tin tức thông báo khuyến mại",
     *     security = { { "basicAuth": {} } },
     *     @SWG\Parameter(
     *         name="category_id",
     *         in="query",
     *         type="string",
     *         description="ID danh mục khuyến mại",
     *         required=true,
     *     ),
     *      @SWG\Parameter(
     *         name="limit",
     *         in="query",
     *         type="string",
     *         description="Limit tin",
     *         required=true,
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK",
     *     ),
     *     @SWG\Response(
     *         response=422,
     *         description="Missing Data"
     *     )
     * )
     */
    public function getDisCountPost(Request $request){
        $category_id = $request->category_id;
        $limit = $request->limit;
        $category = $this->categoryRepository->scopeQuery(function($e) use($category_id){
            return $e->where('id',$category_id);
        })->first();
        $category->setRelation('posts', $category->posts()->paginate($limit));
        return response()->json($category);
    }
    /**
     * @SWG\Get(
     *     path="/api/get-single-post",
     *     summary="Chi tiết blog",
     *     tags={"Blogs"},
     *     description="Trang chi tiết bài viết",
     *     security = { { "basicAuth": {} } },
     *     @SWG\Parameter(
     *         name="post_id",
     *         in="query",
     *         type="string",
     *         description="ID bài viết",
     *         required=true,
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="OK",
     *     ),
     *     @SWG\Response(
     *         response=422,
     *         description="Missing Data"
     *     )
     * )
     */
    public function getSinglePost(Request $request){
        $post_id = $request->post_id;
        $post = Post::where('id',$post_id)->first();
        return response()->json($post);
    }

}
