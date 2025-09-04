<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogsController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::with('user')->orderByDesc('id');

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function($w) use ($q) {
                $w->where('description', 'like', "%{$q}%")
                  ->orWhere('entity_id', 'like', "%{$q}%")
                  ->orWhere('url', 'like', "%{$q}%");
            });
        }

        $logs = $query->paginate(25)->withQueryString();

        return view('admin.audit.index', [
            'logs' => $logs,
            'asset_css' => ['comun/tablas'],
        ]);
    }
}
