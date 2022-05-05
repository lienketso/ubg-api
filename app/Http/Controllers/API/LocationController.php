<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Repositories\DistrictRepository;
use App\Repositories\ProvinceRepository;
use App\Repositories\WardRepository;
use Illuminate\Http\Request;
use Swagger\Annotations as SWG;

class LocationController extends Controller
{
    protected $provinceRepository;
    protected $wardRepository;
    protected $districtRepository;

    public function __construct(ProvinceRepository $provinceRepository, WardRepository $wardRepository, DistrictRepository $districtRepository)
    {
        $this->provinceRepository = $provinceRepository;
        $this->wardRepository = $wardRepository;
        $this->districtRepository = $districtRepository;
    }
    /**
     * @SWG\Get(
     *     path="/api/get-province",
     *     summary="Danh sách Tỉnh thành",
     *     description="Lấy ra danh sách Tỉnh thành",
     *     tags={"Locations"},
     *     security = { { "basicAuth": {} } },
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
    public function getProvince(Request $request){
        $provinces = $this->provinceRepository->get(['id','name','gso_id']);
        return response()->json($provinces);
    }
    /**
     * @SWG\Get(
     *     path="/api/get-districts",
     *     summary="Danh sách Quận / Huyện",
     *     description="Lấy ra danh sách quận huyện theo tỉnh thành",
     *     tags={"Locations"},
     *     security = { { "basicAuth": {} } },
     *     @SWG\Parameter(
     *         name="province_id",
     *         in="query",
     *         type="string",
     *         description="ID của tỉnh thành",
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
    public function getDistrictByProvince(Request $request){
        $districts = $this->districtRepository->scopeQuery(function ($q) use($request){
            return $q->where('province_id',$request->province_id);
        })->get(['id','name','gso_id','province_id']);
        return response()->json($districts);
    }
    /**
     * @SWG\Get(
     *     path="/api/get-wards",
     *     summary="Danh sách Phường / Xã",
     *     description="Lấy ra danh sách Phường / Xã theo Quận huyện",
     *     tags={"Locations"},
     *     security = { { "basicAuth": {} } },
     *     @SWG\Parameter(
     *         name="district_id",
     *         in="query",
     *         type="string",
     *         description="ID của tỉnh thành",
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
    public function getWardByDistrict(Request $request){
        $wards = $this->wardRepository->scopeQuery(function($q) use($request){
            return $q->where('district_id',$request->district_id);
        })->get(['id','name','gso_id','district_id']);
        return response()->json($wards);
    }

}
