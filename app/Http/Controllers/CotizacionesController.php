<?php

namespace App\Http\Controllers;

use App\Models\Cotizacion;
use App\Models\CotizacionItem;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class CotizacionesController extends Controller
{
    public function index()
    {
        $data["asset_css"] = ['comun/tablas'];
        $data["asset_js"] = ['cotizaciones/cotizaciones'];
        return view('cotizacion.index', $data);
    }

    public function create()
    {
        $clientes = Cliente::select('id','name','rut','email')->orderBy('name')->get();
        return view('cotizacion.create', compact('clientes'));
    }

    public function store(Request $request)
    {
        // Normalizar total y precios (CLP)
        $normalizeCLP = fn($v) => (int) preg_replace('/[^0-9]/', '', (string)($v ?? '0'));

        $validated = $request->validate([
            'agent' => 'required|string|max:100',
            'date' => 'required|date',
            'client_id' => 'required|exists:clients,id',
            'work' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.amount' => 'required|integer|min:1',
            'items.*.price' => 'required', // se normaliza
        ]);

        DB::transaction(function() use ($request, $validated, $normalizeCLP) {
            // Calcular totales NETOS (sin IVA) a partir de los items
            $itemsData = collect($validated['items'])->map(function($it) use ($normalizeCLP){
                $qty = (int) $it['amount'];
                $unit = $normalizeCLP($it['price']); // Precio NETO unitario
                $total = $qty * $unit; // Total NETO por ítem
                return [
                    'description' => $it['description'],
                    'amount' => $qty,
                    'price' => $unit,
                    'total' => $total, // Neto
                ];
            });

            $netTotal = $itemsData->sum('total'); // Neto
            $grossTotal = (int) round($netTotal * 1.19); // Con IVA 19%

            $cot = Cotizacion::create([
                'agent' => $validated['agent'],
                'date' => $validated['date'],
                'client_id' => $validated['client_id'],
                'work' => $validated['work'] ?? null,
                'email' => $validated['email'] ?? null,
                'total' => $grossTotal, // Guardamos total con IVA
            ]);

            foreach ($itemsData as $row) {
                $cot->items()->create($row);
            }

            AuditLogger::log($request, 'create', 'cotizaciones', $cot->id, 'Creó cotización #'.$cot->id.' (Neto: '.number_format($netTotal,0,',','.').' IVA: '.number_format($grossTotal-$netTotal,0,',','.').' Total: '.number_format($grossTotal,0,',','.').')');
        });

        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('cotizaciones.index')->with('success', 'Cotización creada');
    }

    public function getData()
    {
        $cotizaciones = Cotizacion::with(['cliente:id,name,rut,email'])
            ->select(['id','client_id','agent','date','total','work','email','created_at'])
            ->orderByDesc('created_at')
            ->get();
        return response()->json(['data' => $cotizaciones]);
    }

    public function show(Cotizacion $cotizacion)
    {
        $cotizacion->load(['cliente','items']);
        // Log de visualización opcional
        AuditLogger::log(request(), 'view', 'cotizaciones', $cotizacion->id, 'Vio cotización #' . $cotizacion->id);

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['cotizacion' => $cotizacion]);
        }
        return view('cotizacion.show', compact('cotizacion'));
    }

    public function edit(Cotizacion $cotizacion)
    {
        $cotizacion->load('items');
        $clientes = Cliente::select('id','name','rut','email')->orderBy('name')->get();
        return view('cotizacion.edit', compact('cotizacion','clientes'));
    }

    public function update(Request $request, Cotizacion $cotizacion)
    {
        $normalizeCLP = fn($v) => (int) preg_replace('/[^0-9]/', '', (string)($v ?? '0'));

        $validated = $request->validate([
            'agent' => 'required|string|max:100',
            'date' => 'required|date',
            'client_id' => 'required|exists:clients,id',
            'work' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'items' => 'required|array|min:1',
            'items.*.id' => 'nullable|integer|exists:quotation_items,id',
            'items.*.description' => 'required|string|max:255',
            'items.*.amount' => 'required|integer|min:1',
            'items.*.price' => 'required',
        ]);

        DB::transaction(function() use ($request, $validated, $normalizeCLP, $cotizacion) {
            $itemsData = collect($validated['items'])->map(function($it) use ($normalizeCLP){
                $qty = (int) $it['amount'];
                $unit = $normalizeCLP($it['price']); // Precio NETO unitario
                $total = $qty * $unit; // Neto por ítem
                return [
                    'id' => $it['id'] ?? null,
                    'description' => $it['description'],
                    'amount' => $qty,
                    'price' => $unit,
                    'total' => $total, // Neto
                ];
            });

            $netTotal = $itemsData->sum('total');
            $grossTotal = (int) round($netTotal * 1.19);

            $cotizacion->update([
                'agent' => $validated['agent'],
                'date' => $validated['date'],
                'client_id' => $validated['client_id'],
                'work' => $validated['work'] ?? null,
                'email' => $validated['email'] ?? null,
                'total' => $grossTotal,
            ]);

            // Sincronizar items: actualizar/crear según id, y eliminar los no presentes
            $existingIds = $cotizacion->items()->pluck('id')->toArray();
            $sentIds = [];
            foreach ($itemsData as $row) {
                if (!empty($row['id'])) {
                    $item = $cotizacion->items()->where('id', $row['id'])->first();
                    if ($item) {
                        $item->update($row);
                        $sentIds[] = $item->id;
                    }
                } else {
                    $item = $cotizacion->items()->create($row);
                    $sentIds[] = $item->id;
                }
            }
            $toDelete = array_diff($existingIds, $sentIds);
            if (!empty($toDelete)) {
                $cotizacion->items()->whereIn('id', $toDelete)->delete();
            }

            AuditLogger::log($request, 'update', 'cotizaciones', $cotizacion->id, 'Actualizó cotización #'.$cotizacion->id.' (Neto: '.number_format($netTotal,0,',','.').' IVA: '.number_format($grossTotal-$netTotal,0,',','.').' Total: '.number_format($grossTotal,0,',','.').')');
        });

        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('cotizaciones.show', $cotizacion)->with('success', 'Cotización actualizada');
    }

    public function pdf(Cotizacion $cotizacion)
    {
        $cotizacion->load(['cliente', 'items']);

        AuditLogger::log(request(), 'export_pdf', 'cotizaciones', $cotizacion->id, 'Exportó PDF cotización #' . $cotizacion->id);

        return view('cotizacion.index_pdf', [
            'quotation' => $cotizacion,
            'asset_css' => ['cotizaciones/pdf'],
            'asset_js' => ['cotizaciones/cotizacion_pdf'],
        ]);
    }

    public function destroy(Request $request, Cotizacion $cotizacion)
    {
        $id = $cotizacion->id;
        DB::transaction(function() use ($request, $cotizacion, $id) {
            // Borrar items explícitamente si la relación no tiene cascade
            if (method_exists($cotizacion, 'items')) {
                $cotizacion->items()->delete();
            }
            $cotizacion->delete();
            AuditLogger::log($request, 'delete', 'cotizaciones', $id, 'Eliminó cotización #' . $id);
        });

        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('cotizaciones.index')->with('success', 'Cotización eliminada');
    }

    public function sendEmail(Request $request, Cotizacion $cotizacion)
    {
        $cotizacion->load('cliente');

        // Validaciones mínimas: aceptar archivo PDF o base64, y correo opcional
        $request->validate([
            'to' => 'nullable|email',
            'message' => 'nullable|string|max:5000',
            'pdf' => 'nullable|file|mimetypes:application/pdf|max:10240', // 10MB
            'pdf_base64' => 'nullable|string',
        ]);

        $to = $request->input('to')
            ?: ($cotizacion->email ?: optional($cotizacion->cliente)->email);

        if (!$to) {
            return response()->json([
                'success' => false,
                'message' => 'No existe un correo de destino (cotización o cliente).'
            ], 422);
        }

        // Obtener binario del PDF ya generado en el cliente
        $pdfBinary = null;
        $filename = 'cotizacion_' . $cotizacion->id . '.pdf';

        // 1) Preferir referencia a archivo previamente subido para evitar payloads grandes
        $fileUrl = trim((string) $request->input('file_url', ''));
        $filePath = trim((string) $request->input('file_path', ''));
        $fileId = $request->input('file_id');
        if (!$pdfBinary && ($fileUrl || $filePath || $fileId)) {
            try {
                if ($fileId && class_exists(\App\Models\FilesRegistry::class)) {
                    $fr = \App\Models\FilesRegistry::find($fileId);
                    if ($fr && $fr->path) {
                        $filePath = $fr->path;
                        $filename = $fr->file_name ?: $filename;
                    }
                }
                if ($filePath && !$fileUrl) {
                    // Intentar leer primero desde R2 directamente
                    if (Storage::disk('r2')->exists($filePath)) {
                        $pdfBinary = Storage::disk('r2')->get($filePath);
                    } else {
                        // Fallback: rutas locales
                        $local = base_path($filePath);
                        if (!is_readable($local)) {
                            $local = storage_path('app/' . ltrim($filePath, '/'));
                        }
                        if (!is_readable($local)) {
                            $local = public_path(ltrim($filePath, '/'));
                        }
                        if (is_readable($local)) {
                            $pdfBinary = @file_get_contents($local);
                        } else {
                            // Último intento vía endpoint de descarga
                            $dlUrl = url('/files/download/' . ltrim($filePath, '/'));
                            $pdfBinary = @file_get_contents($dlUrl);
                        }
                    }
                }
                if (!$pdfBinary && $fileUrl) {
                    $pdfBinary = @file_get_contents($fileUrl);
                    if (!$filename) {
                        $basename = basename(parse_url($fileUrl, PHP_URL_PATH) ?: '');
                        if ($basename) $filename = $basename;
                    }
                }
            } catch (\Throwable $e) {
                // Continuar con otras opciones
            }
        }

        if ($pdfBinary) {
            $subject = 'Cotización #' . $cotizacion->id . ' - Sociedad Aceros Era Ltda.';
            $body = $request->input('message') ?: (
                'Adjuntamos la cotización #' . $cotizacion->id . ' correspondiente.'
            );
            // Usar destinatario real (calculado arriba)
            $to = "cristofer.miranda@gmail.com";
            // Enviar vía API (Mailgun HTTP) sin dependencias adicionales
            $result = $this->sendEmailViaMailgunApi($to, $subject, $body, $pdfBinary, $filename);
            if (!($result['success'] ?? false)) {
                $err = $result['error'] ?? 'Error desconocido en API de correo';
                throw new \RuntimeException($err);
            }

            AuditLogger::log($request, 'send_email', 'cotizaciones', $cotizacion->id, 'Envió cotización por correo a ' . $to);

            return response()->json(['success' => true]);
        } else {
            // 2) Si no hay archivo referenciado, aceptar subida directa (archivo o base64)
            if ($request->hasFile('pdf')) {
                $file = $request->file('pdf');
                if (!$file->isValid()) {
                    return response()->json(['success' => false, 'message' => 'Archivo inválido'], 422);
                }
                $pdfBinary = file_get_contents($file->getRealPath());
                $filename = $file->getClientOriginalName() ?: $filename;
            } else {
                $data = $request->input('pdf_base64');
                if (!$data) {
                    return response()->json(['success' => false, 'message' => 'Falta el PDF'], 422);
                }
                // Soportar data URI o base64 puro
                if (str_starts_with($data, 'data:')) {
                    $parts = explode(',', $data, 2);
                    $data = $parts[1] ?? '';
                }
                $decoded = base64_decode($data, true);
                if ($decoded === false) {
                    return response()->json(['success' => false, 'message' => 'Base64 inválido'], 422);
                }
                $pdfBinary = $decoded;
            }
        }

        try {
            $subject = 'Cotización #' . $cotizacion->id . ' - Sociedad Aceros Era Ltda.';
            $body = $request->input('message') ?: (
                'Adjuntamos la cotización #' . $cotizacion->id . ' correspondiente.'
            );
            
            // Enviar vía API (Mailgun HTTP) sin dependencias adicionales
            $result = $this->sendEmailViaMailgunApi($to, $subject, $body, $pdfBinary, $filename);
            if (!($result['success'] ?? false)) {
                $err = $result['error'] ?? 'Error desconocido en API de correo';
                throw new \RuntimeException($err);
            }

            AuditLogger::log($request, 'send_email', 'cotizaciones', $cotizacion->id, 'Envió cotización por correo a ' . $to);

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            report($e);
            $message = 'No se pudo enviar el correo';
            $payload = [
                'success' => false,
                'message' => $message,
            ];
            if (config('app.debug')) {
                // Incluir detalles útiles solo en modo debug
                $payload['message'] = $message . ' (' . $e->getMessage() . ')';
                $payload['error'] = $e->getMessage();
                $payload['exception'] = get_class($e);
                $payload['file'] = $e->getFile();
                $payload['line'] = $e->getLine();
            }
            return response()->json($payload, 500);
        }
    }

    // ==========================
    // Envío de correo vía API (Mailgun) sin paquetes externos
    // ==========================
    private function sendEmailViaMailgunApi(string $to, string $subject, string $body, string $pdfBinary, string $filename): array
    {
        $domain = config('services.mailgun.domain');
        $secret = config('services.mailgun.secret');
        $endpoint = config('services.mailgun.endpoint', 'api.mailgun.net');

        if (empty($domain) || empty($secret)) {
            return ['success' => false, 'error' => 'Mailgun no está configurado (falta domain/secret)'];
        }

        $fromAddr = config('mail.from.address', 'no-reply@' . $domain);
        if (strpos($fromAddr, '@') === false) {
            $fromAddr = 'no-reply@' . $domain;
        } elseif (!str_ends_with(strtolower($fromAddr), '@' . strtolower($domain))) {
            $fromAddr = 'no-reply@' . $domain;
        }
        $fromName = config('mail.from.name', 'Sociedad Aceros Era Ltda.');
        $from = sprintf('%s <%s>', $fromName, $fromAddr);

        $tmpPath = tempnam(sys_get_temp_dir(), 'pdf_');
        if ($tmpPath === false) {
            return ['success' => false, 'error' => 'No se pudo crear archivo temporal'];
        }
        file_put_contents($tmpPath, $pdfBinary);

        $url = sprintf('https://%s/v3/%s/messages', $endpoint, $domain);
        $postFields = [
            'from' => $from,
            'to' => $to,
            'subject' => $subject,
            'text' => $body,
            'attachment' => new \CURLFile($tmpPath, 'application/pdf', $filename),
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => 'api:' . $secret,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        @unlink($tmpPath);

        if ($errno) {
            return ['success' => false, 'error' => 'cURL #' . $errno . ': ' . $err];
        }

        $decoded = json_decode((string)$response, true);
        if ($status >= 200 && $status < 300) {
            return ['success' => true, 'status' => $status, 'response' => $decoded ?: $response];
        }

        $msg = $decoded['message'] ?? (is_string($response) ? $response : 'Respuesta desconocida');
        return ['success' => false, 'status' => $status, 'error' => $msg];
    }
}
