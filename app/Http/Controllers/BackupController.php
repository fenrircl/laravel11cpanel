<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class BackupController extends Controller
{
    public function download(Request $request)
    {
        // Validar token (si no usas auth:api)
        $token = $request->header('X-API-TOKEN');
        if ($token !== env('BACKUP_API_TOKEN')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $tables = DB::select('SHOW TABLES');
        $sqlScript = '';

        foreach ($tables as $tableObj) {
            $table = array_values((array)$tableObj)[0];

            // Estructura
            $create = DB::select("SHOW CREATE TABLE `$table`")[0];
            $sqlScript .= "\n\n" . $create->{'Create Table'} . ";\n\n";

            // Datos
            $rows = DB::table($table)->get();
            foreach ($rows as $row) {
                $values = array_map(function ($v) {
                    return $v === null ? 'NULL' : "'" . addslashes($v) . "'";
                }, (array)$row);

                $sqlScript .= "INSERT INTO `$table` VALUES(" . implode(',', $values) . ");\n";
            }
        }

        $filename = 'backup-' . date('Y-m-d_H-i-s') . '.sql';
        return Response::make($sqlScript, 200, [
            'Content-Type' => 'application/sql',
            'Content-Disposition' => "attachment; filename={$filename}"
        ]);
    }
}
