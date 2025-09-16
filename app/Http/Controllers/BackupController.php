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

        $charset = env('DB_DUMP_CHARSET', 'utf8mb4');
        $collation = env('DB_DUMP_COLLATION', 'utf8mb4_unicode_ci');

        $sqlScript = "-- Simple SQL dump\n" .
            "SET NAMES {$charset};\n" .
            "SET SESSION sql_mode='NO_AUTO_VALUE_ON_ZERO';\n" .
            "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $tableObj) {
            $table = array_values((array)$tableObj)[0];

            // Estructura
            $create = DB::select("SHOW CREATE TABLE `{$table}`")[0];
            $createSql = (string) ($create->{'Create Table'} ?? '');

            // Normalizar CHARSET y COLLATE a valores compatibles
            $normalized = $createSql;
            // ENGINE ... DEFAULT CHARSET=xxx [COLLATE=yyy]
            $normalized = preg_replace('/DEFAULT\\s+CHARSET=([a-zA-Z0-9_]+)/i', 'DEFAULT CHARSET=' . $charset, $normalized);
            $normalized = preg_replace('/CHARSET=([a-zA-Z0-9_]+)/i', 'CHARSET=' . $charset, $normalized);
            // table/column level collations
            $normalized = preg_replace('/COLLATE=([a-zA-Z0-9_]+)/i', 'COLLATE=' . $collation, $normalized);
            $normalized = preg_replace('/COLLATE\\s+([a-zA-Z0-9_]+)/i', 'COLLATE ' . $collation, $normalized);

            $sqlScript .= "\n\n{$normalized};\n\n";

            // Datos
            $rows = DB::table($table)->get();
            foreach ($rows as $row) {
                $values = array_map(function ($v) {
                    if ($v === null) return 'NULL';
                    // tratar binarios/strings de forma simple
                    return "'" . addslashes((string)$v) . "'";
                }, (array)$row);

                $sqlScript .= "INSERT INTO `{$table}` VALUES(" . implode(',', $values) . ");\n";
            }
        }

        $sqlScript .= "\nSET FOREIGN_KEY_CHECKS=1;\n";

        $filename = 'backup-' . date('Y-m-d_H-i-s') . '.sql';
        return Response::make($sqlScript, 200, [
            'Content-Type' => 'application/sql',
            'Content-Disposition' => "attachment; filename={$filename}"
        ]);
    }
}
