<?php
namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
class TambonController extends Controller
{

    public $data;

    public function __construct()
    {
        // อ่านข้อมูลจากไฟล์ JSON ในโฟลเดอร์ public
        $path = public_path('raw_database.json');
        $this->data = json_decode(file_get_contents($path), false);
    }

    public function getProvinces()
    {
        $data = $this->data;
        $provinces = array_map(function ($item) {
            return $item->province;
        }, $data);
        $provinces = array_unique($provinces);
        $provinces = array_values($provinces);
        return response()->json($provinces);
    }

    public function getAmphoes(Request $request)
    {
        $data = $this->data;
        $amphoes = array_filter($data, function ($item) use ($request) {
            return $item->province == $request->get('province');
        });
        $amphoes = array_map(function ($item) {
            return $item->amphoe;
        }, $amphoes);
        $amphoes = array_unique($amphoes);
        $amphoes = array_values($amphoes);
        return response()->json($amphoes);
    }

    public function getTambons(Request $request)
    {
        $data = $this->data;
        $tambons = array_filter($data, function ($item) use ($request) {
            return $item->amphoe == $request->get('amphoe') && $item->province == $request->get('province');
        });
        $tambons = array_map(function ($item) {
            return $item->district;
        }, $tambons);
        $tambons = array_unique($tambons);
        $tambons = array_values($tambons);
        return response()->json($tambons);
    }

    public function getZipcodes(Request $request)
    {
        $data = $this->data;
        $zipcodes = array_filter($data, function ($item) use ($request) {
            return $item->district == $request->get('tambon') && $item->amphoe == $request->get('amphoe') && $item->province == $request->get('province');
        });
        $zipcodes = array_map(function ($item) {
            return $item->zipcode;
        }, $zipcodes);
        $zipcodes = array_values($zipcodes);
        return response()->json($zipcodes);
    }
}
