<?php

namespace App\Http\Controllers;

// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\DB;

// class DashboardController extends Controller
// {
//     public function index()
//     {
//         $data = DB::table('zonas')
//             ->select('product_name', 'unit_id', 'customer_id', 'product_file', 'created_at')
//             ->union(DB::table('manajemen_risikos')->select('product_name', 'unit_id', 'customer_id', 'product_file', 'created_at'))
//             ->union(DB::table('lain_lains')->select('product_name', 'unit_id', 'customer_id', 'product_file', 'created_at'))
//             ->union(DB::table('kepatuhan_interns')->select('product_name', 'unit_id', 'customer_id', 'product_file', 'created_at'))
//             ->get();

//         return view('dashboard', compact('data'));
//     }
// }

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
{
    $dataZonas = DB::table('zonas')
        ->select('zonas.product_name', 'customers.name as customer_name', 'units.name as unit_name', 'zonas.product_file', 'zonas.created_at')
        ->join('customers', 'zonas.customer_id', '=', 'customers.id')
        ->join('units', 'zonas.unit_id', '=', 'units.id')
        ->get();

    $dataManajemenRisikos = DB::table('manajemen_risikos')
        ->select('manajemen_risikos.product_name', 'customers.name as customer_name', 'units.name as unit_name', 'manajemen_risikos.product_file', 'manajemen_risikos.created_at')
        ->join('customers', 'manajemen_risikos.customer_id', '=', 'customers.id')
        ->join('units', 'manajemen_risikos.unit_id', '=', 'units.id')
        ->get();


        $dataLainLains = DB::table('lain_lains')
    ->select('lain_lains.product_name', 'customers.name as customer_name', 'units.name as unit_name', 'lain_lains.product_file', 'lain_lains.created_at')
    ->join('customers', 'lain_lains.customer_id', '=', 'customers.id')
    ->join('units', 'lain_lains.unit_id', '=', 'units.id')
    ->get();


    $dataKepatuhanInterns = DB::table('kepatuhan_interns')
        ->select('kepatuhan_interns.product_name', 'customers.name as customer_name', 'units.name as unit_name', 'kepatuhan_interns.product_file', 'kepatuhan_interns.created_at')
        ->join('customers', 'kepatuhan_interns.customer_id', '=', 'customers.id')
        ->join('units', 'kepatuhan_interns.unit_id', '=', 'units.id')
        ->get();

    // Gabungkan semua data menjadi satu array
    $allData = $dataZonas->concat($dataManajemenRisikos)->concat($dataLainLains)->concat($dataKepatuhanInterns);

    return view('dashboard.index', compact('allData'));
}

}
