<?php

namespace App\Http\Controllers;
use App\Models\Formato;
use App\Models\FormatoVersion;
use App\Models\TipoProceso;
use App\Models\TipoDocProceso;
use setasign\Fpdi\Fpdi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FormatoController extends Controller
{
    public function index(){
        $formatos = Formato::with('ultimaVersion')->get();
        return view('formato.listaFormatos', compact('formatos'));
    }

    public function llenarFormatoPDF($name){
        $pdfPath = storage_path('app/pdfs/formulario.pdf'); // plantilla del PDF
        $outputPath = storage_path('app/pdfs/formulario_completado.pdf'); // PDF generado
    
        $pdf = new Fpdi();
        $pdf->AddPage();
        $pdf->setSourceFile($pdfPath);
        $tplIdx = $pdf->importPage(1);
        $pdf->useTemplate($tplIdx, 0, 0, 210, 297); // Ajustar según tamaño del PDF
    
        // Configurar fuente y tamaño
        $pdf->SetFont('Helvetica', '', 12);
    
        // Posicionar y escribir los datos en los campos del formulario
        $pdf->SetXY(40, 40); 
        $pdf->Write(10, "$name");
    
        return response($pdf->Output('', 'I'))->header('Content-Type', 'application/pdf');
    }

    public function crearNuevoFormato(){
        $tipoProcesos = TipoProceso::all();
        $tipoDocProcesos = TipoDocProceso::all();
        return view('formato.crearFormato', compact('tipoProcesos', 'tipoDocProcesos'));
    }

    public function crearVersionFormato($id){
        $formato = Formato::find($id);
        $tipoProcesos = TipoProceso::all();
        $tipoDocProcesos = TipoDocProceso::all();
        return view('formato.nuevaVersion', compact('formato', 'tipoProcesos', 'tipoDocProcesos'));
    }

    private function guardarPDF($file, $version, $nombre){
        $nombreLimpio = preg_replace('/[^A-Za-z0-9\-]/', '_', $nombre); // Solo letras, números y guiones bajos
        $nombreLimpio = strtolower($nombreLimpio); // Convertir a minúsculas

        $filename = $nombreLimpio . '_v' . $version . '.pdf';
        $path = $file->storeAs('pdfs', $filename, 'public');
        return $path;
    }

    public function guardarVersionFormato(Request $request){
        
        $validator = Validator::make($request->all(), [
            'pdf' => 'required|mimes:pdf|max:2048',
            'VerElaboro' => 'required|string|regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ]+(?:\s[A-Za-zÁÉÍÓÚáéíóúÑñ]+)*$/|max:255',
            'VerReviso' => 'required|string|regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ]+(?:\s[A-Za-zÁÉÍÓÚáéíóúÑñ]+)*$/|max:255',
            'VerAprobo' => 'required|string|regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ]+(?:\s[A-Za-zÁÉÍÓÚáéíóúÑñ]+)*$/|max:255'
        ],[
            'required' => 'El campo es obligatorio.',
            'regex' => 'El campo solo puede contener letras y espacios.',

            'pdf.mimes' => 'El archivo debe ser un PDF.',
            'pdf.max' => 'El archivo no debe pesar más de 2MB.'
        ]);

        //manejo de errores
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $formatoVersion = new FormatoVersion();
        $formatoVersion->VerElaboro = $request->VerElaboro;
        $formatoVersion->VerReviso = $request->VerReviso;
        $formatoVersion->VerAprobo = $request->VerAprobo;
        $formatoVersion->VerFecReg = now();
        $formatoVersion->VerHorReg = now();

        $version = FormatoVersion::where('IdFormato', $request->IdFormato)->max('Version') + 1;
        $formatoVersion->Version = $version;

        //guardar pdf en storage/app/pdfs/
        $path = $this->guardarPDF($request->file('pdf'), $version, $request->FormNom);

        $formatoVersion->Ruta = $path;
        $formatoVersion->IdFormato = $request->IdFormato;
        $formatoVersion->save();

        return response()->json([
            'message' => 'Nueva versión creada exitosamente',
            'redirect' => route('formatos.index'),
            'type' => 'success', 
            'title' => 'Nueva versión creada exitosamente'
        ]); 
    }


    public function guardarFormato(Request $request){

        $validator = Validator::make($request->all(), [
            'pdf' => 'required|mimes:pdf|max:2048',
            'FormCod' => 'required|string|unique:_formatos,FormCod|max:50',
            'FormNom' => 'required|string|regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ]+(?:\s[A-Za-zÁÉÍÓÚáéíóúÑñ]+)*$/|max:50',
            'FormUbicacion' => 'required|string|regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ]+(?:\s[A-Za-zÁÉÍÓÚáéíóúÑñ]+)*$/|max:50',
            'VerElaboro' => 'required|string|regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ]+(?:\s[A-Za-zÁÉÍÓÚáéíóúÑñ]+)*$/|max:50',
            'VerReviso' => 'required|string|regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ]+(?:\s[A-Za-zÁÉÍÓÚáéíóúÑñ]+)*$/|max:50',
            'VerAprobo' => 'required|string|regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ]+(?:\s[A-Za-zÁÉÍÓÚáéíóúÑñ]+)*$/|max:50'
        ],[
            'pdf.required' => 'El archivo PDF es requerido',
            'pdf.mimes' => 'El archivo debe ser un PDF',
            'pdf.max' => 'El archivo PDF no debe pesar más de 2MB',

            'regex' => 'El campo solo puede contener letras y un espacio entre palabras',
            'required' => 'Este campo es requerido',

            'FormCod.unique' => 'El código del formato ya existe',
        ]);

        //manejo de errores
         if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        //guardar pdf en storage/app/pdfs/ 
        $path = $this->guardarPDF($request->file('pdf'), 1, $request->FormNom);
    
        $formato = new Formato();
        $formato->FormCod = $request->FormCod;
        $formato->FormNom = $request->FormNom;
        $formato->FormTipProc = $request->FormTipProc;
        $formato->FormTipDoc = $request->FormTipDoc;
        $formato->FormUbicacion = $request->FormUbicacion;
        $formato->save();

        $formatoVersion = new FormatoVersion();
        $formatoVersion->VerElaboro = $request->VerElaboro;
        $formatoVersion->VerReviso = $request->VerReviso;
        $formatoVersion->VerAprobo = $request->VerAprobo;
        $formatoVersion->VerFecReg = now();
        $formatoVersion->VerHorReg = now();
        $formatoVersion->Ruta = $path;
        $formatoVersion->Version = 1;
        $formatoVersion->IdFormato = $formato->IdFormato;
        $formatoVersion->save();

        return response()->json([
            'message' => 'Formato creado exitosamente',
            'redirect' => route('formatos.index'),
            'type' => 'success', 
            'title' => 'Formato creado exitosamente'
        ]); 
    }

    public function versionesFormato($id){
        $formato = Formato::find($id);
        $versiones = FormatoVersion::where('IdFormato', $id)->get();
        return view('formato.listaVersiones', compact('formato', 'versiones'));
    }
}
