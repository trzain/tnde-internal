<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Unit;
use App\Models\LainLain;
// use App\Models\Category;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Redirect;
use PhpOffice\PhpSpreadsheet\Writer\Xls;
// use Picqer\Barcode\BarcodeGeneratorHTML;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Haruncpi\LaravelIdGenerator\IdGenerator;

class LainLainController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $row = (int) request('row', 10);

        if ($row < 1 || $row > 100) {
            abort(400, 'The per-page parameter must be an integer between 1 and 100.');
        }

        $lain_lain = LainLain::with(['unit', 'customer'])
                ->filter(request(['search']))
                ->sortable()
                ->paginate($row)
                ->appends(request()->query());

        return view('lain_lain.index', [
            'lain_lain' => $lain_lain,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('lain_lain.create', [
            // 'categories' => Category::all(),
            'units' => Unit::all(),
            'customers' => Customer::all(),
        ]);
    }

    public function store(Request $request)
    {
        $rules = [
            // 'product_image' => 'image|file|max:2048',
            'product_file' => 'required|mimes:pdf,doc,docx,ppt,pptx,xls,xlsx|max:2048',
            'product_name' => 'required|string',
            'unit_id' => 'required|integer',
            'customer_id' => 'required|integer',
        
        ];

        $validatedData = $request->validate($rules);

        //untuk upload gambar
        // if ($file = $request->file('product_image')) {
        //     $fileName = hexdec(uniqid()).'.'.$file->getClientOriginalExtension();
        //     $path = 'public/lain_lain/';

        //     /**
        //      * Upload an image to Storage
        //      */
        //     $file->storeAs($path, $fileName);
        //     $validatedData['product_image'] = $fileName;
        // }

        //untuk upload file dokumen
         if ($file = $request->file('product_file')) {
            // $fileFileName = hexdec(uniqid()) . '.' . $file->getClientOriginalExtension();
            $fileFileName = $file->getClientOriginalName();


            $filePath = 'public/lain_lain/documents/';
    
            $file->storeAs($filePath, $fileFileName);
            $validatedData['product_file'] = $fileFileName;
        }        

        LainLain::create($validatedData);

        return Redirect::route('lain_lain.index')->with('success', 'Data Kegiatan has been created!');
    }

    /**
     * Display the specified resource.
     */
    public function show(LainLain $lain_lain)
    {
        // Generate a barcode
        // $generator = new BarcodeGeneratorHTML();

        // $barcode = $generator->getBarcode($product->product_code, $generator::TYPE_CODE_128);

        return view('lain_lain.show', [
            'lain_lain' => $lain_lain,
            // 'barcode' => $barcode,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LainLain $lain_lain)
    {
        return view('lain_lain.edit', [
            // 'categories' => Category::all(),
            'units' => Unit::all(),
            'customers' => Customer::all(),
            'lain_lain' => $lain_lain,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LainLain $lain_lain)
    {
        $rules = [
            // 'product_image' => 'image|file|max:2048',
            'product_file' => 'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx|max:2048',
            'product_name' => 'required|string',
            // 'category_id' => 'required|integer',
            'unit_id' => 'integer',
            'customer_id' => 'integer',
            // 'stock' => 'required|integer',
            // 'buying_price' => 'required|integer',
            // 'selling_price' => 'required|integer',
        ];

        $validatedData = $request->validate($rules);

        /**
         * Handle upload an image
         */
        // if ($file = $request->file('product_image')) {
        //     $fileName = hexdec(uniqid()).'.'.$file->getClientOriginalExtension();
        //     $path = 'public/lain_lain/';

            
        //     if($lain_lain->product_image){
        //         Storage::delete($path . $lain_lain->product_image);
        //     }

        //     $file->storeAs($path, $fileName);
        //     $validatedData['product_image'] = $fileName;
        // }

         //delete photo kalau sudah ada
        if ($file = $request->file('product_file')) {
            $fileFileName = hexdec(uniqid()) . '.' . $file->getClientOriginalExtension();
            $filePath = 'public/lain_lain/documents/';

            if($lain_lain->product_file){
                Storage::delete($filePath . $lain_lain->product_file);
            }
    
            $file->storeAs($filePath, $fileFileName);
            $validatedData['product_file'] = $fileFileName;
        }   

        LainLain::where('id', $lain_lain->id)->update($validatedData);

        return Redirect::route('lain_lain.index')->with('success', 'Kepatuhan Intern has been updated!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LainLain $lain_lain)
    {
        /**
         * Delete photo if exists.
         */
        // if($lain_lain->product_image){
        //     Storage::delete('public/lain_lain/' . $lain_lain->product_image);
        // }

        if($lain_lain->product_file){
            Storage::delete('public/lain_lain/documents' . $lain_lain->product_file);
        }

        LainLain::destroy($lain_lain->id);

        return Redirect::route('lain_lain.index')->with('success', 'Kepatuhan Intern has been deleted!');
    }

    /**
     * Handle export data products.
     */
    public function import()
    {
        return view('lain_lain.import');
    }

    public function handleImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xls,xlsx',
        ]);

        $the_file = $request->file('file');

        try{
            $spreadsheet = IOFactory::load($the_file->getRealPath());
            $sheet        = $spreadsheet->getActiveSheet();
            $row_limit    = $sheet->getHighestDataRow();
            $column_limit = $sheet->getHighestDataColumn();
            $row_range    = range( 2, $row_limit );
            $column_range = range( 'J', $column_limit );
            $startcount = 2;
            $data = array();
            foreach ( $row_range as $row ) {
                $data[] = [
                    'product_name' => $sheet->getCell( 'A' . $row )->getValue(),
                    // 'category_id' => $sheet->getCell( 'B' . $row )->getValue(),
                    'unit_id' => $sheet->getCell( 'B' . $row )->getValue(),
                    'customer_id' => $sheet->getCell( 'C' . $row )->getValue(),
                    // 'product_code' => $sheet->getCell( 'E' . $row )->getValue(),
                    // 'stock' => $sheet->getCell( 'F' . $row )->getValue(),
                    // 'buying_price' => $sheet->getCell( 'G' . $row )->getValue(),
                    // 'selling_price' =>$sheet->getCell( 'I' . $row )->getValue(),
                    // 'product_image' =>$sheet->getCell( 'D' . $row )->getValue(),
                    'product_file' =>$sheet->getCell( 'D' . $row )->getValue(),
                ];
                $startcount++;
            }

            LainLain::insert($data);

        } catch (Exception $e) {
            // $error_code = $e->errorInfo[1];
            return Redirect::route('lain_lain.index')->with('error', 'There was a problem uploading the data!');
        }
        return Redirect::route('lain_lain.index')->with('success', 'Data product has been imported!');
    }

    /**
     * Handle export data products.
     */
    function export(){
        $lain_lain = LainLain::all()->sortBy('product_name');

        $lain_lain_array [] = array(
            'Nama Kegiatan',
            // 'Category Id',
            // 'Unit Id',
            // 'Customer Id',
            // 'Product Code',
            // 'Stock',
            // 'Buying Price',
            // 'Selling Price',
            // 'Product Image',
            'Product File',
        );

        foreach($lain_lain as $lain_lain)
        {
            $lain_lain_array[] = array(
                'Product Name' => $lain_lain->product_name,
                // 'Category Id' => $product->category_id,
                // 'Unit Id' => $lain_lain->unit_id,
                // 'Customer Id' => $lain_lain->customer_id,
                // 'Product Code' => $product->product_code,
                // 'Stock' => $product->stock,
                // 'Buying Price' =>$product->buying_price,
                // 'Selling Price' =>$product->selling_price,
                // 'Product Image' => $lain_lain->product_image,
                'Produc File' => $lain_lain->product_file,
            );
        }

        $this->exportExcel($lain_lain_array);
    }

    public function exportExcel($lain_lain){
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '4000M');

        try {
            $spreadSheet = new Spreadsheet();
            $spreadSheet->getActiveSheet()->getDefaultColumnDimension()->setWidth(20);
            $spreadSheet->getActiveSheet()->fromArray($lain_lain);
            $Excel_writer = new Xls($spreadSheet);
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="lain_lain.xls"');
            header('Cache-Control: max-age=0');
            ob_end_clean();
            $Excel_writer->save('php://output');
            exit();
        } catch (Exception $e) {
            return;
        }
    }

}
