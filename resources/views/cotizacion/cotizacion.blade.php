@extends('adminlte::page')
@section('title_postfix', " Cotizacion")


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
footer {
    text-align: justify;
    text-justify: inter-word;
}
</style>

<div id="quotation">

<header>
    <div class="container header">
      <p><h2>Sociedad Aceros Era Ltda.</h2></p>
      <section class="main row">
          <div class="col-md-6 ">
              <p>76.150.341 - 3</p>
          <p>Alessandri 109, Tierras Blancas</p>
          <p>Coquimbo</p>
          <p>contacto@acerosera.cl</p>
          <p>(051) 2249328</p>
          </div>
        <div class="col-md-6 ">
        <img src="/img/logo.png " style="width: 60%;" alt="Logo">
      </div>
    </section>
  </div>
</header>

<center>



<h1>  {!! Form::label('work', 'Trabajo:') !!}

  {!! Form::label('work', $quotation->work, ['class' => 'add-on', 'placeholder' => "", 'size' => 100]) !!}</h1>
  <h2>{!! Form::label('id', 'Cotización ') !!}
{!! Form::label('id', $quotation->id, ['class' => 'add-on', 'placeholder' => "", 'size' => 20]) !!}</h2>
</center>

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
{!! Form::label('phone', $quotation->client->phone, ['class' => 'add-on', 'placeholder' => "", 'size' => 20]) !!}
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

  </div>
</div>
<br>
<div class="panel panel-primary">
  <div class="panel-heading">
    <h3 class="panel-title">Item</h3>
  </div>
  <div class="panel-body">
<table class="table table-responsive table-inverse  table-bordered">
  <thead class="thead-inverse">
    <tr>
      <th>Cantidad</th>
      <th>descripcion</th>
      <th>Precio</th>
      <th>Total</th>
    </tr>
  </thead>
          @foreach($item as $item)
  <tbody>
    <tr>
    <td> {{ $item->amount }} </td>
    <td> <p class="small"> {{ $item->description }} </p></td>


    <td>{{"$" . number_format($item->price, 0, ',', '.')}}</td>
    <td>{{"$" . number_format($item->total, 0, ',', '.')}}</td>

    </tr>
  </tbody>
      @endforeach
</table>
</div>
</div>

<div class="panel panel-default">
  <div class="panel-body">

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


<button type="button" onclick="window.print()"class="btn btn-primary no-print"><span class="glyphicon glyphicon-print"></span> Imprimir</button>
<a href="{{ url('/') }}/cotizacion/pdf/{{$quotation->id}}" class="btn btn btn-primary no-print" role="button"><span class="glyphicon glyphicon-download-alt"></span> Descargar </a>

<button type="button" class="btn btn-primary  no-print" data-toggle="modal" data-target="#addInvoice">
  <span class="glyphicon glyphicon-envelope"></span>   Correo
</button>

@if (isset($quotation->work_order->id))
<a href="/work_order/{{$quotation->work_order->id}}" class="btn btn btn-primary no-print" role="button"><span class="glyphicon glyphicon-thumbs-up"></span> Orden de trabajo </a>
@else
<a href="{{URL::route('work_order.preorder', array('quotation_id' => $quotation->id , 'client_id' =>  $quotation->client->id))}}"class="btn btn btn-primary no-print" role="button">Generar orden de trabajo </a>

@endif

<a href="{{URL::route('cotizacion.modify', array('quotation_id' => $quotation->id ))}}"class="btn btn btn-primary no-print" role="button"><span class="glyphicon glyphicon-wrench "></span> Modificar </a>

<br><br>
<footer>
    <div class="container">
       <section class="main row">
          <div class="col-md-12 text-justify">
          Hacer todos los cheques pagaderos a  Sociedad Aceros Era Ltda.
          Si tiene alguna pregunta relacionada con esta factura, le rogamos se ponga en contacto con:
          Ximena Valledor R.  Celular 9 - 88 63 192  E-Mail: contacto@acerosera.cl
          <br><br>
          <p>Datos de transferencia : sociedad Aceros ERA Ltda<br />
            Rut 76.150.341-3<br />
            Cuenta corriente banco chile N*1201237809<br />
            Administracion@acerosera.cl</p>     
          </div>
    </section>
  </div>
</footer>


</div>


   <!-- Ventana enviar por correo-->
   <div class="modal fade" id="addInvoice" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
          <h4 class="modal-title" id="myModalLabel">Enviar cotizacion por correo</h4>
        </div>
        <div class="modal-body">
             <form action="{{ route('quotation.sendmail') }}" method="post">
            <input type="email" name="mail" placeholder="correo electronico" size="40" value="{{$quotation->client->email}}"><br>
             <input type="text" name="name" placeholder="nombre" value="{{$quotation->agent}}" size="40"><br>

            <textarea type="text" name="message" id="message"  rows="8" cols="42" placeholder="Mensaje"></textarea><br><br>
            <input type="hidden" name="quotation" value="{{$quotation->id}}">

            {!! Form::label('sender', 'Enviado por: ') !!}
            {{ Form::select('sender', array('1' => 'Contacto', '2' => 'Ximena','3' => 'Eugenio','4' => 'Sebastian'), '1') }}
            <br><br>
            {{ csrf_field() }}
            <button type="submit" class="btn btn-primary">Enviar correo</button>
        </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-primary" data-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
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





@include('scripts.scripts')
@include('scripts.quotations')
@include('sweet::alert')
<link rel="stylesheet" type="text/css" href="{{ URL::asset('css/print.css') }}">

@stop
@push('css')

<link rel="stylesheet" type="text/css" href="{{ URL::asset('/css/sweetalert2.min.css') }} ">
<link rel="stylesheet" type="text/css" href="{{ URL::asset('/css/fixedHeader.dataTables.min.css') }} ">
<link rel="stylesheet" type="text/css" href="{{ URL::asset('/css/jquery.dataTables.yadcf.css') }} ">
<link rel="stylesheet" type="text/css" href="{{ URL::asset('/css/jquery-ui.min.css') }} ">


@push('js')
<script src={{ asset('/js/plugin/sweetalert2.min.js') }} ></script>
