<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Unit;
use App\Models\Zona;
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

class ZonaController extends Controller
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

        $zona = Zona::with(['unit', 'customer'])
                ->filter(request(['search']))
                ->sortable()
                ->paginate($row)
                ->appends(request()->query());

        return view('zona.index', [
            'zona' => $zona,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('zona.create', [
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
        //     $path = 'public/kepatuhan_intern/';

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


            $filePath = 'public/zona/documents/';
    
            $file->storeAs($filePath, $fileFileName);
            $validatedData['product_file'] = $fileFileName;
        }        

        Zona::create($validatedData);

        return Redirect::route('zona.index')->with('success', 'Kepatuhan Intern has been created!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Zona $zona)
    {
        // Generate a barcode
        // $generator = new BarcodeGeneratorHTML();

        // $barcode = $generator->getBarcode($product->product_code, $generator::TYPE_CODE_128);

        return view('zona.show', [
            'zona' => $zona,
            // 'barcode' => $barcode,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Zona $zona)
    {
        return view('zona.edit', [
            // 'categories' => Category::all(),
            'units' => Unit::all(),
            'customers' => Customer::all(),
            'zona' => $zona,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Zona $zona)
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
        //     $path = 'public/kepatuhan_intern/';

            
        //     if($kepatuhan_intern->product_image){
        //         Storage::delete($path . $kepatuhan_intern->product_image);
        //     }

        //     $file->storeAs($path, $fileName);
        //     $validatedData['product_image'] = $fileName;
        // }

         //delete photo kalau sudah ada
        if ($file = $request->file('product_file')) {
            $fileFileName = hexdec(uniqid()) . '.' . $file->getClientOriginalExtension();
            $filePath = 'public/zona/documents/';

            if($zona->product_file){
                Storage::delete($filePath . $zona->product_file);
            }
    
            $file->storeAs($filePath, $fileFileName);
            $validatedData['product_file'] = $fileFileName;
        }   

        Zona::where('id', $zona->id)->update($validatedData);

        return Redirect::route('zona.index')->with('success', 'Kepatuhan Intern has been updated!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy (Zona $zona)
    {
        /**
         * Delete photo if exists.
         */
        // if($kepatuhan_intern->product_image){
        //     Storage::delete('public/kepatuhan_intern/' . $kepatuhan_intern->product_image);
        // }

        if($zona->product_file){
            Storage::delete('public/zona/documents' . $zona->product_file);
        }

        Zona::destroy($zona->id);

        return Redirect::route('zona.index')->with('success', 'Kepatuhan Intern has been deleted!');
    }

    /**
     * Handle export data products.
     */
    public function import()
    {
        return view('zona.import');
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

            Zona::insert($data);

        } catch (Exception $e) {
            // $error_code = $e->errorInfo[1];
            return Redirect::route('zona.index')->with('error', 'There was a problem uploading the data!');
        }
        return Redirect::route('zona.index')->with('success', 'Data product has been imported!');
    }

    /**
     * Handle export data products.
     */
    function export(){
        $zona = Zona::all()->sortBy('product_name');

        $zona_array [] = array(
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

        foreach($zona as $zona)
        {
            $zona_array[] = array(
                'Product Name' => $zona->product_name,
                // 'Category Id' => $product->category_id,
                // 'Unit Id' => $kepatuhan_intern->unit_id,
                // 'Customer Id' => $kepatuhan_intern->customer_id,
                // 'Product Code' => $product->product_code,
                // 'Stock' => $product->stock,
                // 'Buying Price' =>$product->buying_price,
                // 'Selling Price' =>$product->selling_price,
                // 'Product Image' => $kepatuhan_intern->product_image,
                'Produc File' => $zona->product_file,
            );
        }

        $this->exportExcel($zona_array);
    }

    public function exportExcel($zona){
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '4000M');

        try {
            $spreadSheet = new Spreadsheet();
            $spreadSheet->getActiveSheet()->getDefaultColumnDimension()->setWidth(20);
            $spreadSheet->getActiveSheet()->fromArray($zona);
            $Excel_writer = new Xls($spreadSheet);
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="zona.xls"');
            header('Cache-Control: max-age=0');
            ob_end_clean();
            $Excel_writer->save('php://output');
            exit();
        } catch (Exception $e) {
            return;
        }
    }

}
