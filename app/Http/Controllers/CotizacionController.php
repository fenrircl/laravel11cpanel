<?php

namespace App\Http\Controllers;

namespace App\Http\Controllers;

use App\Models\Factura;
use App\Models\Cliente;
use App\Models\Proveedor;
use App\Models\MetodoPago;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuotationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
       return view('quotation.index');
    }

    public function new()
    {
    // $clients = client::getclientPhone();
    //  return client::has('phones')->get();
    // $clients = client::with('phones')->paginate(10);
    // return $clients;
    // return view('clients.index')->with('clients', $clients);
    //return $$page = request('page', 10);;
    // return view('clients.index', ['clients' => $paginator]);
    //  $provider = Provider::pluck('name', 'id');
    // $activity = Activity::where('subject_type' , 'App\Client')->orderBy('created_at', 'desc')->get()->take(10);
    // $activity = $activity->toJson();
    $city = City::pluck('name', 'id');
    $client = Client::orderBy('name', 'asc')->pluck('name',  'rut');
    return view('quotation.new',compact('city'),compact('client'));
    //return view('quotation.new');
    }

    public function getData()
    {


      //return "asd";
      $client = Client::pluck('name', 'id');
      $work_order = Work_order::pluck('id');
      $quotation = Quotation::whereNotNull('client_id')->with('client')->orhas('work_order')->with('work_order')->get();
      //return $quotation[0]->work_order;
      //return $quotation[0]->id;
      //return $work_order;
      //$invoice = Invoice::find(123);
      //return $invoice->getMedia('invoices');;
      foreach ($quotation as $key ){
      $key->total = "$" . number_format($key->total, 0, ',', '.');

      if(isset($key->work_order)){
        $key->status = "OK";
      }

      if( isset($key->work_order)){
      $key->work_order_id = $key->work_order->id;
      }
    }
   // return $quotation;

      return Datatables::of($quotation)
      ->addColumn('action', function ($quotation) {
      return ' <td>
      <a href="cotizacion/'.$quotation->id.'" class="btn btn-primary" role="button"> <span class="glyphicon glyphicon-edit"> </span>Detalles</a>
      ';
      })

      ->addColumn('work_order', function ($quotation) {
        if ( $quotation->work_order_id > 0 ) {

        return ' <a href="/work_order/'.$quotation->work_order_id.'"  >Orden de trabajo</a>
';
        }

      })

      ->rawColumns(['work_order', 'action'])
      ->make(true);
    }


    public function search()
    {
      //$client = Client::where('rut', request()->id)->first();
      $client = DB::table('clients')
            ->where('rut', request()->id)
            ->join('cities', 'clients.city_id', '=', 'cities.id')
            ->select('clients.*', 'cities.name as ciudad')
            ->get()->first();
       //  $test = json_decode($client);
       // $city = City::where('rut', request()->id)->first();
       // $client = Client::find(43242342);
       // str_replace( array( '[', ']'), '', json_encode($client));
       //optional,
       //return $client;
        if(is_null($client)){
            return Response::json('error');
        }else{
            return Response::json($client);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //

        return view('cotizacion.new');

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

//return $request;
        $client = Client::where('id', $request->id)->first();

        if($request->address !=  $client->adress )
        $client->address = $request->address;
        if($request->phone !=  $client->phone )
        $client->phone = $request->phone;
        if($request->city !=  $client->city_id )
        $client->city_id = $request->city;
//actualiza los datos del cliente al momento de crear nueva cotizacion
        $client->save();

       // return $client;

      $this->validate($request, [
        'search' => 'required|max:255',
        'date' => 'required|max:20',
        'work' => 'max:100',
    ]);
      //  return $request;
        $count = count($request->input('amount'));
        Quotation::store($request);
        $last = Quotation::all()->last()->id;
        $now = Carbon::now()->toDateTimeString();

        //return $count;
        for ($i=0; $i<$count; $i++){
          $data[] = array(
            'amount' => $request->input('amount')[$i],
            'description' => $request->input('description')[$i],
            'price' => $request->input('price')[$i],
            'total' => $request->input('total')[$i],
            'quotation_id' => $last,
            'created_at'=> $now,
            'updated_at'=> $now
            );
        }
    //return $data;
    //DB::table('quotation_items') -> insert($data);
    //return $last;
    $quotationitem = new QuotationItem;
    $quotationitem->insert($data);
    return redirect('/cotizacion')->with('success', 'Cotizacion agregada');

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Quotation  $quotation
     * @return \Illuminate\Http\Response
     */


    public function downloadPDF($id)
{
     // return $id;
     $quotation = Quotation::with('client')->get()->find($id);
     //return $quotation ;
     $client = Client::pluck('name', 'id');
     $city = City::where('id', $quotation->client->city_id)->get();
     $item = Quotationitem::where('quotation_id', $id)->get();

     // return $city;
     foreach ($city as $key ){
    $cityname = $key->name;
   }
   $view =  \View::make('quotation.pdf', compact('cityname', 'item', 'client', 'quotation'))->render();
   $pdf = \App::make('dompdf.wrapper');
   $pdf->loadHTML($view);
   $date =  $quotation->date ? with(new Carbon($quotation->date))->format('d-m-Y') : '';;
   $pdfname = 'cotizacion '.$id.' '.$date.'.pdf';
   //return view('quotation.pdf', ['quotation' => $quotation  , 'client' => $client])->with('cityname', $cityname)->with('item', $item);
   $pdf->setPaper('legal', 'portrait');

   return $pdf->download( $pdfname);

      //$pdf = \PDF::loadView('quotation.quotation', compact('quotation'));

     // return $pdf->download('invoice.pdf');
      //return view('quotation.quotation', ['quotation' => $quotation]);

  }
  public function sendmail(Request $request)
  {
//return $request;
//

$id = $request->quotation;
$quotation = Quotation::with('client')->get()->find($id);
//return $quotation ;
$client = Client::where('id', $quotation->client_id)->first();
$city = City::where('id', $quotation->client->city_id)->get();
$item = Quotationitem::where('quotation_id', $id)->get();

// return $city;
foreach ($city as $key ){
$cityname = $key->name;
}

$body= $request->message;
// $message;
$view =  \View::make('quotation.pdf', compact('cityname', 'item', 'client', 'quotation'))->render();
$pdf = \App::make('dompdf.wrapper');
$pdf->loadHTML($view);
$date =  $quotation->date ? with(new Carbon($quotation->date))->format('d-m-Y') : '';;
$pdfname = 'cotizacion '.$id.' '.$date.'.pdf';
$pdf->setPaper('legal', 'portrait');
$pdf->save(public_path(). '/files/shares/cotizaciones/'.$pdfname  );
Storage::put($pdfname, $pdf->output());

$subject = "Cotizacion Acerosera ".$id." ".$date;
$receiver = $request->mail;
$name = $request->name;
//$sender = $request->sender;

switch ($request->sender) {
  case 1:
  $sender = "";
  break;
  case 2:
  $sender = "Ximena Valledor Rodriguez";
  break;
  case 3:
  $sender = "Eugenio Rodriguez";
  break;
  case 4:
  $sender = "Sebastian Millaom";
  break;
  default:
  $sender = "";
}
//return $receiver;
//return "listo";
$files = public_path(). '/files/shares/cotizaciones/'.$pdfname;
//return $client->name;
//return view('email.quotation',compact('quotation', 'client', 'body', 'name', 'sender'));

\Mail::send('email.quotation', compact('quotation', 'client', 'body', 'name', 'sender'), function ($message) use( $files, $subject,$receiver){
  $message->to($receiver)
  ->subject($subject)
  ->attach($files, [

      'mime' => 'application/pdf'
  ]);

});

alert()->success('Correo enviado correctamente', 'Atención')->autoclose(3000);
return redirect('/cotizacion/'.$request->quotation);

  }

  public function modify(Request $id)
  {
    //  return $id->quotation_id  ;
      $quotation = Quotation::with('client')->get()->find($id->quotation_id);
     // return $quotation;
      $client = Client::where('id', $quotation->client_id )->first();
      $city = City::where('id', $quotation->client->city_id)->first();
//return $client;
      // return $city;

   //  return $cityname;

      $item = Quotationitem::where('quotation_id',$id->quotation_id)->get();
      $count = Quotationitem::where('quotation_id',$id->quotation_id)->count();

        //return $count;

      // return view('quotation.quotation', ['quotation' => $quotation, 'client' => $client  ]);
       //return view('quotation.quotation', ['quotation' => $quotation,'provider' => $provider],compact('city'))->with('item', $item);
       return view('quotation.quotation_update', ['quotation' => $quotation  , 'client' => $client])->with('city', $city)->with('item', $item)->with('count',$count);




//return $client;
  }
    public function show($id)
    {
    //  $pdf = PDF::loadView('quotation.quotation', $id);
      //  return $pdf;
        //
       // $quotation = Quotation::find($id);

       $quotation = Quotation::with('client')->get()->find($id);
       //return ;
       $client = Client::pluck('name', 'id');
       $city = City::where('id', $quotation->client->city_id)->get();
       $work_order = Work_order::where('quotation_id', $quotation->id)->get();
//return $work_order;
       // return $city;
       foreach ($city as $key ){
      $cityname = $key->name;

      }
    //  return $cityname;

       $item = Quotationitem::where('quotation_id', $id)->get();

       //  return $item;

       // return view('quotation.quotation', ['quotation' => $quotation, 'client' => $client  ]);
        //return view('quotation.quotation', ['quotation' => $quotation,'provider' => $provider],compact('city'))->with('item', $item);
        return view('quotation.quotation', ['quotation' => $quotation  , 'client' => $client])->with('cityname', $cityname)->with('item', $item)->with('work_order',$work_order);


    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Quotation  $quotation
     * @return \Illuminate\Http\Response
     */
    public function edit(Quotation $quotation)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Quotation  $quotation
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Quotation $quotation)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Quotation  $quotation
     * @return \Illuminate\Http\Response
     */
    public function destroy(Quotation $quotation)
    {
        //
    }
}
