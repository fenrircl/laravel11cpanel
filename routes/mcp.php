<?php

use PhpMcp\Laravel\Facades\Mcp;
use App\Models\{Cliente, Proveedor, Factura, Cotizacion};

// ===== Recursos (basados en modelos) =====
Mcp::resourceTemplate('factura://{id}', function(int $id): array {
    $f = Factura::with(['cliente:id,name,rut','proveedor:id,name,rut'])->findOrFail($id);
    return [
        'id' => $f->id,
        'invoice' => $f->invoice,
        'date' => optional($f->date)->toDateString(),
        'expiry' => optional($f->expiry)->toDateString(),
        'amount' => (int) $f->amount,
        'status' => (int) $f->status,
        'status_text' => $f->status_text,
        'tipo' => $f->tipo,
        'cliente' => $f->cliente ? [ 'id' => $f->cliente->id, 'name' => $f->cliente->name, 'rut' => $f->cliente->rut ] : null,
        'proveedor' => $f->proveedor ? [ 'id' => $f->proveedor->id, 'name' => $f->proveedor->name, 'rut' => $f->proveedor->rut ] : null,
    ];
})
    ->name('factura_detalle')
    ->description('Detalle de una factura por ID')
    ->mimeType('application/json');

Mcp::resourceTemplate('cotizacion://{id}', function(int $id): array {
    $c = Cotizacion::with(['cliente:id,name,rut,email,phone','items'])->findOrFail($id);
    return [
        'id' => $c->id,
        'date' => optional($c->date)->toDateString(),
        'total' => (int) $c->total,
        'agent' => $c->agent,
        'work' => $c->work,
        'email' => $c->email,
        'cliente' => $c->cliente ? [ 'id' => $c->cliente->id, 'name' => $c->cliente->name, 'rut' => $c->cliente->rut ] : null,
        'items' => ($c->items ?? collect())->map(fn($it) => [
            'id' => $it->id,
            'description' => $it->description,
            'amount' => (int) $it->amount,
            'price' => (int) $it->price,
            'total' => (int) $it->total,
        ])->toArray(),
    ];
})
    ->name('cotizacion_detalle')
    ->description('Detalle de una cotización con ítems')
    ->mimeType('application/json');

Mcp::resourceTemplate('cliente://{id}', function(int $id): array {
    $c = Cliente::findOrFail($id);
    return [ 'id' => $c->id, 'name' => $c->name, 'rut' => $c->rut, 'email' => $c->email, 'phone' => $c->phone ];
})
    ->name('cliente_detalle')
    ->description('Detalle de cliente por ID')
    ->mimeType('application/json');

Mcp::resourceTemplate('proveedor://{id}', function(int $id): array {
    $p = Proveedor::findOrFail($id);
    return [ 'id' => $p->id, 'name' => $p->name, 'rut' => $p->rut, 'email' => $p->email, 'phone' => $p->phone ];
})
    ->name('proveedor_detalle')
    ->description('Detalle de proveedor por ID')
    ->mimeType('application/json');

// ===== Herramientas (para n8n) =====
// Clientes: búsqueda simple (nombre/RUT)
Mcp::tool(function(?string $q = null, int $per_page = 10, int $page = 1): array {
    $per_page = max(1, min($per_page, 100));
    $query = Cliente::query();
    if ($q) {
        $qv = trim($q);
        $query->where(function($b) use ($qv){
            $b->where('name','like',"%{$qv}%")->orWhere('rut','like',"%{$qv}%");
        });
    }
    $p = $query->orderByDesc('id')->paginate($per_page, ['*'], 'page', $page);
    return [
        'items' => array_map(fn($c) => [ 'id'=>$c->id,'name'=>$c->name,'rut'=>$c->rut,'email'=>$c->email,'phone'=>$c->phone ], $p->items()),
        'pagination' => [ 'current_page'=>$p->currentPage(),'per_page'=>$p->perPage(),'total'=>$p->total(),'last_page'=>$p->lastPage() ]
    ];
})
    ->name('clientes_buscar')
    ->description('Buscar clientes por nombre o RUT')
    ->inputSchema([
        'type' => 'object',
        'properties' => [
            'q' => ['type'=>'string','description'=>'Texto de búsqueda (nombre o RUT)'],
            'per_page' => ['type'=>'integer','minimum'=>1,'maximum'=>100],
            'page' => ['type'=>'integer','minimum'=>1],
        ],
    ]);

// Proveedores: búsqueda simple (nombre/RUT)
Mcp::tool(function(?string $q = null, int $per_page = 10, int $page = 1): array {
    $per_page = max(1, min($per_page, 100));
    $query = Proveedor::query();
    if ($q) {
        $qv = trim($q);
        $query->where(function($b) use ($qv){
            $b->where('name','like',"%{$qv}%")->orWhere('rut','like',"%{$qv}%");
        });
    }
    $p = $query->orderByDesc('id')->paginate($per_page, ['*'], 'page', $page);
    return [
        'items' => array_map(fn($c) => [ 'id'=>$c->id,'name'=>$c->name,'rut'=>$c->rut,'email'=>$c->email,'phone'=>$c->phone ], $p->items()),
        'pagination' => [ 'current_page'=>$p->currentPage(),'per_page'=>$p->perPage(),'total'=>$p->total(),'last_page'=>$p->lastPage() ]
    ];
})
    ->name('proveedores_buscar')
    ->description('Buscar proveedores por nombre o RUT')
    ->inputSchema([
        'type' => 'object',
        'properties' => [
            'q' => ['type'=>'string','description'=>'Texto de búsqueda (nombre o RUT)'],
            'per_page' => ['type'=>'integer','minimum'=>1,'maximum'=>100],
            'page' => ['type'=>'integer','minimum'=>1],
        ],
    ]);

// Facturas por cliente
Mcp::tool(function(int $cliente_id, ?string $estado = null, ?string $desde = null, ?string $hasta = null, int $per_page = 10, int $page = 1): array {
    $per_page = max(1, min($per_page, 100));
    $q = Factura::query()->with(['cliente:id,name,rut'])
        ->where('client_id',$cliente_id);
    if ($estado !== null && $estado !== '') {
        $map = ['pendiente'=>0,'pending'=>0,'0'=>0,'pagado'=>1,'paid'=>1,'1'=>1];
        $status = $map[strtolower((string)$estado)] ?? null;
        if ($status !== null) { $q->where('status',$status); }
    }
    if ($desde) { $q->whereDate('date','>=',$desde); }
    if ($hasta) { $q->whereDate('date','<=',$hasta); }
    $p = $q->orderByDesc('date')->paginate($per_page, ['*'], 'page', $page);
    return [
        'items' => array_map(fn($f)=>[
            'id'=>$f->id,'invoice'=>$f->invoice,'date'=>optional($f->date)->toDateString(),'amount'=>(int)$f->amount,'status'=>(int)$f->status,
            'cliente'=>$f->cliente?[ 'id'=>$f->cliente->id,'name'=>$f->cliente->name,'rut'=>$f->cliente->rut ]:null
        ], $p->items()),
        'pagination' => [ 'current_page'=>$p->currentPage(),'per_page'=>$p->perPage(),'total'=>$p->total(),'last_page'=>$p->lastPage() ]
    ];
})
    ->name('cliente_listar_facturas')
    ->description('Listar facturas de un cliente con filtros y paginación')
    ->inputSchema([
        'type'=>'object',
        'properties'=>[
            'cliente_id'=>['type'=>'integer'],
            'estado'=>['type'=>'string','enum'=>['pendiente','pagado','0','1','paid','pending']],
            'desde'=>['type'=>'string','format'=>'date'],
            'hasta'=>['type'=>'string','format'=>'date'],
            'per_page'=>['type'=>'integer','minimum'=>1,'maximum'=>100],
            'page'=>['type'=>'integer','minimum'=>1],
        ],
        'required'=>['cliente_id']
    ]);

// Facturas por proveedor
Mcp::tool(function(int $proveedor_id, ?string $estado = null, ?string $desde = null, ?string $hasta = null, int $per_page = 10, int $page = 1): array {
    $per_page = max(1, min($per_page, 100));
    $q = Factura::query()->with(['proveedor:id,name,rut'])
        ->where('provider_id',$proveedor_id);
    if ($estado !== null && $estado !== '') {
        $map = ['pendiente'=>0,'pending'=>0,'0'=>0,'pagado'=>1,'paid'=>1,'1'=>1];
        $status = $map[strtolower((string)$estado)] ?? null;
        if ($status !== null) { $q->where('status',$status); }
    }
    if ($desde) { $q->whereDate('date','>=',$desde); }
    if ($hasta) { $q->whereDate('date','<=',$hasta); }
    $p = $q->orderByDesc('date')->paginate($per_page, ['*'], 'page', $page);
    return [
        'items' => array_map(fn($f)=>[
            'id'=>$f->id,'invoice'=>$f->invoice,'date'=>optional($f->date)->toDateString(),'amount'=>(int)$f->amount,'status'=>(int)$f->status,
            'proveedor'=>$f->proveedor?[ 'id'=>$f->proveedor->id,'name'=>$f->proveedor->name,'rut'=>$f->proveedor->rut ]:null
        ], $p->items()),
        'pagination' => [ 'current_page'=>$p->currentPage(),'per_page'=>$p->perPage(),'total'=>$p->total(),'last_page'=>$p->lastPage() ]
    ];
})
    ->name('proveedor_listar_facturas')
    ->description('Listar facturas de un proveedor con filtros y paginación')
    ->inputSchema([
        'type'=>'object',
        'properties'=>[
            'proveedor_id'=>['type'=>'integer'],
            'estado'=>['type'=>'string','enum'=>['pendiente','pagado','0','1','paid','pending']],
            'desde'=>['type'=>'string','format'=>'date'],
            'hasta'=>['type'=>'string','format'=>'date'],
            'per_page'=>['type'=>'integer','minimum'=>1,'maximum'=>100],
            'page'=>['type'=>'integer','minimum'=>1],
        ],
        'required'=>['proveedor_id']
    ]);

// Búsqueda/listado de facturas con filtros
Mcp::tool(function(?string $q = null, ?string $estado = null, ?string $tipo = null, ?int $cliente_id = null, ?int $proveedor_id = null, ?string $desde = null, ?string $hasta = null, ?string $sort_by = 'date', ?string $sort_dir = 'desc', int $per_page = 10, int $page = 1): array {
    $per_page = max(1, min($per_page, 100));
    $qb = Factura::query()->with(['cliente:id,name,rut','proveedor:id,name,rut']);
    if ($q) { $qb->where('invoice','like',"%{$q}%"); }
    if ($tipo === 'cliente') { $qb->whereNotNull('client_id'); }
    if ($tipo === 'proveedor') { $qb->whereNotNull('provider_id'); }
    if ($cliente_id) { $qb->where('client_id',$cliente_id); }
    if ($proveedor_id) { $qb->where('provider_id',$proveedor_id); }
    if ($estado !== null && $estado !== '') {
        $map = ['pendiente'=>0,'pending'=>0,'0'=>0,'pagado'=>1,'paid'=>1,'1'=>1];
        $status = $map[strtolower((string)$estado)] ?? null;
        if ($status !== null) { $qb->where('status',$status); }
    }
    if ($desde) { $qb->whereDate('date','>=',$desde); }
    if ($hasta) { $qb->whereDate('date','<=',$hasta); }
    $sortBy = in_array($sort_by, ['date','amount','invoice','id'], true) ? $sort_by : 'date';
    $sortDir = strtolower($sort_dir) === 'asc' ? 'asc' : 'desc';
    $qb->orderBy($sortBy, $sortDir);
    $p = $qb->paginate($per_page, ['*'], 'page', $page);
    return [
        'items' => array_map(fn($f)=>[
            'id'=>$f->id,'invoice'=>$f->invoice,'date'=>optional($f->date)->toDateString(),'amount'=>(int)$f->amount,'status'=>(int)$f->status,'status_text'=>$f->status_text,'tipo'=>$f->tipo,
            'cliente'=>$f->cliente?[ 'id'=>$f->cliente->id,'name'=>$f->cliente->name,'rut'=>$f->cliente->rut ]:null,
            'proveedor'=>$f->proveedor?[ 'id'=>$f->proveedor->id,'name'=>$f->proveedor->name,'rut'=>$f->proveedor->rut ]:null
        ], $p->items()),
        'pagination' => [ 'current_page'=>$p->currentPage(),'per_page'=>$p->perPage(),'total'=>$p->total(),'last_page'=>$p->lastPage() ]
    ];
})
    ->name('facturas_buscar')
    ->description('Buscar/listar facturas con filtros y ordenamiento')
    ->inputSchema([
        'type'=>'object',
        'properties'=>[
            'q'=>['type'=>'string'],'estado'=>['type'=>'string'],'tipo'=>['type'=>'string','enum'=>['cliente','proveedor']],
            'cliente_id'=>['type'=>'integer'],'proveedor_id'=>['type'=>'integer'],
            'desde'=>['type'=>'string','format'=>'date'],'hasta'=>['type'=>'string','format'=>'date'],
            'sort_by'=>['type'=>'string','enum'=>['date','amount','invoice','id']],'sort_dir'=>['type'=>'string','enum'=>['asc','desc']],
            'per_page'=>['type'=>'integer','minimum'=>1,'maximum'=>100],'page'=>['type'=>'integer','minimum'=>1]
        ]
    ]);

// Total pendiente de pago
Mcp::tool(function(?int $cliente_id = null, ?int $proveedor_id = null, ?string $desde = null, ?string $hasta = null): array {
    $qb = Factura::query();
    if ($cliente_id) { $qb->where('client_id',$cliente_id); }
    if ($proveedor_id) { $qb->where('provider_id',$proveedor_id); }
    if ($desde) { $qb->whereDate('date','>=',$desde); }
    if ($hasta) { $qb->whereDate('date','<=',$hasta); }
    $sum = (int) $qb->where('status', 0)->sum('amount');
    return [ 'total_pendiente' => $sum ];
})
    ->name('facturas_total_pendiente')
    ->description('Total pendiente de pago con filtros opcionales')
    ->inputSchema([
        'type'=>'object',
        'properties'=>[
            'cliente_id'=>['type'=>'integer'],'proveedor_id'=>['type'=>'integer'],
            'desde'=>['type'=>'string','format'=>'date'],'hasta'=>['type'=>'string','format'=>'date']
        ]
    ]);

// Cotizaciones: búsqueda/listado
Mcp::tool(function(?string $q = null, ?int $cliente_id = null, ?string $desde = null, ?string $hasta = null, ?string $sort_by = 'date', ?string $sort_dir = 'desc', int $per_page = 10, int $page = 1): array {
    $per_page = max(1, min($per_page, 100));
    $qb = Cotizacion::query()->with(['cliente:id,name,rut']);
    if ($cliente_id) { $qb->where('client_id', $cliente_id); }
    if ($desde) { $qb->whereDate('date','>=',$desde); }
    if ($hasta) { $qb->whereDate('date','<=',$hasta); }
    if ($q) {
        $qv = trim($q);
        $qb->where(function($b) use ($qv){
            $b->whereHas('cliente', function($bc) use ($qv){ $bc->where('name','like',"%{$qv}%")->orWhere('rut','like',"%{$qv}%"); })
              ->orWhere('work','like',"%{$qv}%");
        });
    }
    $sortBy = in_array($sort_by, ['date','total','id'], true) ? $sort_by : 'date';
    $sortDir = strtolower($sort_dir) === 'asc' ? 'asc' : 'desc';
    $qb->orderBy($sortBy, $sortDir);
    $p = $qb->paginate($per_page, ['*'], 'page', $page);
    return [
        'items' => array_map(fn($c)=>[
            'id'=>$c->id,'date'=>optional($c->date)->toDateString(),'total'=>(int)$c->total,'agent'=>$c->agent,'work'=>$c->work,
            'cliente'=>$c->cliente?[ 'id'=>$c->cliente->id,'name'=>$c->cliente->name,'rut'=>$c->cliente->rut ]:null
        ], $p->items()),
        'pagination' => [ 'current_page'=>$p->currentPage(),'per_page'=>$p->perPage(),'total'=>$p->total(),'last_page'=>$p->lastPage() ]
    ];
})
    ->name('cotizaciones_buscar')
    ->description('Buscar/listar cotizaciones con filtros')
    ->inputSchema([
        'type'=>'object',
        'properties'=>[
            'q'=>['type'=>'string'],'cliente_id'=>['type'=>'integer'],
            'desde'=>['type'=>'string','format'=>'date'],'hasta'=>['type'=>'string','format'=>'date'],
            'sort_by'=>['type'=>'string','enum'=>['date','total','id']],'sort_dir'=>['type'=>'string','enum'=>['asc','desc']],
            'per_page'=>['type'=>'integer','minimum'=>1,'maximum'=>100],'page'=>['type'=>'integer','minimum'=>1]
        ]
    ]);

// Cotización: detalle por ID (como herramienta)
Mcp::tool(function(int $id): array {
    $c = Cotizacion::with(['cliente:id,name,rut,email,phone','items'])->findOrFail($id);
    return [
        'id'=>$c->id,'date'=>optional($c->date)->toDateString(),'total'=>(int)$c->total,'agent'=>$c->agent,'work'=>$c->work,'email'=>$c->email,
        'cliente'=>$c->cliente?[ 'id'=>$c->cliente->id,'name'=>$c->cliente->name,'rut'=>$c->cliente->rut ]:null,
        'items'=>($c->items ?? collect())->map(fn($it)=>['id'=>$it->id,'description'=>$it->description,'amount'=>(int)$it->amount,'price'=>(int)$it->price,'total'=>(int)$it->total])->toArray()
    ];
})
    ->name('cotizacion_detalle_tool')
    ->description('Obtener detalle de una cotización por ID')
    ->inputSchema([
        'type'=>'object',
        'properties'=>[ 'id'=>['type'=>'integer'] ],
        'required'=>['id']
    ]);