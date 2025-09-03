

@section('content')

@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<style>
  #left { float:left }
  #right { float:right }
  @media print {
    .no-print {
      visibility: hidden;
    }
  }

  div.header {
    line-height: 0.8;
  }
</style>

<div id="quotation">
 

{{-- <header>
    <div class="container header">
      <section class="main row">
   
        <div class="col-xs-6 " >
           
            <img src="{{ public_path() . $image_path }}" style="width: 95%; height: 50%">        </div>
    </section>
  </div>
</header> --}}


<div class="row">
  <div class="col-md-6">
    <img src="{{ asset('img/logo.png') }}" style="width: 25%;" alt="Logo">
  </div>
  
  <div class="col-md-6">
    <center>
    <h1>  {!! Form::label('work', 'Trabajo:') !!}
      {!! Form::label('work', $quotation->work, ['class' => 'add-on', 'placeholder' => "", 'size' => 100]) !!}
    </h1>
    <h2>{!! Form::label('id', 'Cotización ') !!}
      {!! Form::label('id', $quotation->id, ['class' => 'add-on', 'placeholder' => "", 'size' => 20]) !!}
    </h2>
    </center>
  </div>
</div>
<br>

  <div class="panel panel-default">
      <div class="panel-body">

  {!! Form::label('name', 'Nombre:') !!}
  {!! Form::label('name', $quotation->client->name, ['class' => 'add-on', 'placeholder' => "", 'size' => 20]) !!}
  <br>
  {!! Form::label('rut', 'Rut:') !!}
  {{ Form::label('search', $quotation->client->rut, array('id' => 'search')) }}

  <br>

  {!! Form::label('rut', 'Direccion:') !!}
  @if(isset($quotation->client->address))
  {!! Form::label('address', $quotation->client->address, ['class' => 'add-on', 'placeholder' => "", 'size' => 20]) !!}
  @endif

  @if(isset($quotation->client->cityname))
  {!! Form::label('city', ' , ') !!}
  {!! Form::label('city', $cityname,array('class' => '')) !!}
  @endif

  {!! Form::label('rut', 'Telefono:') !!}
  @if(isset($quotation->client->phone))
  {!! Form::label('phone', null, ['class' => 'add-on', 'placeholder' => "", 'size' => 20]) !!}
  @endif
  <br>

  {!! Form::label('rut', 'Email:') !!}
  @if(isset($quotation->client->email))
  {!! Form::label('email', $quotation->client->email, ['class' => 'add-on', 'placeholder' => "", 'size' => 20]) !!}
  @endif
  <br>

  {!! Form::label('date', 'Fecha:') !!}
  {!! Form::label('date', $quotation->date, ['class' => 'add-on', 'placeholder' => "", 'size' => 20]) !!}
  <br>

  {!! Form::label('agent', 'Representante:') !!}
  {!! Form::label('agent', $quotation->agent, ['class' => 'add-on', 'placeholder' => "", 'size' => 20]) !!}



<br>
<br>
<style>
  
  th,
  td {
    border: 1px solid black;
    border-collapse: collapse;
  }
  table{
    border-top:1px solid black;
    border-collapse: collapse;
  }
</style>
  </div>
</div>
<br>
<div class="panel panel-primary">
  <div class="panel-heading">
    <h3 class="panel-title">Detalle</h3>
  </div>
  <div class="panel-body">
    <table class="table table-responsive table-inverse " style="width:100%">
      <thead class="thead-inverse">
        <tr>
          <th>Cantidad</th>
          <th>descripcion</th>
          <th>Precio</th>
          <th>Total</th>
        </tr>
      </thead>
            
    <tbody>
      @foreach($item as $item)
      <tr style=" border: 1px solid black;">
      <td> {{ $item->amount }} </td>
      <td> {{ $item->description }} </td>
      <td>{{"$" . number_format($item->price, 0, ',', '.')}}</td>
      <td>{{"$" . number_format($item->total, 0, ',', '.')}}</td>
      </tr>
      @endforeach
      <tr>
        <td colspan="2" style="border: 0px !important :border-collapse: collapse;"></td><td>NETO</td>
        <td>{{ "$" . number_format(($quotation->total / 1.19)) }}</td>
      </tr>
      <tr>
        <td colspan="2" style="border: 0px !important :border-collapse: collapse;"></td><td>IVA</td>
        <td>{{ "$" . number_format(($quotation->total / 1.19)*0.19) }}</td>
      </tr>
      <tr>
        <td colspan="2" style="border: 0px !important :border-collapse: collapse;"></td><td>TOTAL</td>
        <td>{{ "$" . number_format(($quotation->total)) }}</td>
      </tr>
    </tbody>       
  </table>
</div>
</div>
{{-- <div class="panel panel-default">
  <div class="panel-body">
    <br>
    <div align="right">
    {!! Form::label('date', 'subtotal:') !!}
    {!! Form::label('total', "$" . number_format(($quotation->total / 1.19), 0, ',', '.'), ['class' => 'add-on', 'placeholder' => "", 'size' => 20]) !!}
    <br>

    {!! Form::label('date', 'iva:') !!}
    {!! Form::label('total', "$" . number_format((($quotation->total / 1.19) * 0.19), 0, ',', '.') , ['class' => 'add-on', 'placeholder' => "", 'size' => 20]) !!}
    <br>

    {!! Form::label('date', 'total:') !!}
    {!! Form::label('total', "$" . number_format($quotation->total, 0, ',', '.'), ['class' => 'add-on', 'placeholder' => "", 'size' => 20]) !!}
      </div>
    </div>
    <br>
</div> --}}



<br><br><br><br><br><br>
<footer>
    <div class="container">
       <section class="main row">       
          <div class="col-md-12">
          Hacer todos los cheques pagaderos a  Sociedad Aceros Era Ltda.</p>
          Si tiene alguna pregunta relacionada con esta factura, le rogamos se ponga en contacto con:</p>
          Ximena Valledor R.  Celular 9 - 88 63 192  E-Mail: contacto@acerosera.cl</p>
          <br>
          <p>Datos de transferencia : sociedad Aceros ERA Ltda<br />
            Rut 76.150.341-3<br />
            Cuenta corriente banco chile N*1201237809<br />
            Administracion@acerosera.cl</p>            
          </div>
    </section>
  </div>
</footer>


</div>
<script type="text/javascript">
  function printDiv(divName) {
       var printContents = document.getElementById(divName).innerHTML;
       var originalContents = document.body.innerHTML;

       document.body.innerHTML = printContents;

       window.print();

       document.body.innerHTML = originalContents;
  }
      </script>
      <style>
        @media print {
           a[href]:after {
              content: none !important;
           }
        }
        </style>





@include('scripts.scripts')


  <link rel="stylesheet" type="text/css" href="{{ URL::asset('/css/sweetalert2.min.css') }} ">
  <link rel="stylesheet" type="text/css" href="{{ URL::asset('/css/fixedHeader.dataTables.min.css') }} ">
  <link rel="stylesheet" type="text/css" href="{{ URL::asset('/css/jquery.dataTables.yadcf.css') }} ">
  <link rel="stylesheet" type="text/css" href="{{ URL::asset('/css/jquery-ui.min.css') }} ">
    <!-- Bootstrap 3.3.7 -->
    <script src="{{ asset('vendor/adminlte/vendor/bootstrap/dist/js/bootstrap.min.js') }}"></script>

    <!-- Font Awesome -->

  <script src={{ asset('/js/plugin/sweetalert2.min.js') }} ></script>
  @show
