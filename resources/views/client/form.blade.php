<div class="box box-info padding-1">
    <div class="box-body">

        {!! Form::open(['route' => 'client.store', 'method' => 'POST']) !!}

        <div class="form-group mt-3">
            {{ Form::label('client_id', 'ID de Cliente') }}
            {{ Form::text('client_id', null, ['class' => 'form-control' . ($errors->has('client_id') ? ' is-invalid' : ''), 'placeholder' => 'ID de Cliente']) }}
            {!! $errors->first('client_id', '<div class="invalid-feedback">:message</div>') !!}
        </div>

        <div class="form-group mt-3">
            {{ Form::label('razon_social', 'Razón Social') }}
            {{ Form::text('razon_social', null, ['class' => 'form-control' . ($errors->has('razon_social') ? ' is-invalid' : ''), 'placeholder' => 'Razón Social']) }}
            {!! $errors->first('razon_social', '<div class="invalid-feedback">:message</div>') !!}
        </div>

        <div class="form-group mt-3">
            {{ Form::label('telefono', 'Teléfono') }}
            {{ Form::text('telefono', null, ['class' => 'form-control' . ($errors->has('telefono') ? ' is-invalid' : ''), 'placeholder' => 'Teléfono']) }}
            {!! $errors->first('telefono', '<div class="invalid-feedback">:message</div>') !!}
        </div>

        <div class="form-group mt-3">
            {{ Form::label('email', 'Email') }}
            {{ Form::text('email', null, ['class' => 'form-control' . ($errors->has('email') ? ' is-invalid' : ''), 'placeholder' => 'Email']) }}
            {!! $errors->first('email', '<div class="invalid-feedback">:message</div>') !!}
        </div>

        <div class="form-group mt-3">
            {{ Form::label('otros_campos', 'Otros Campos') }}
            {{ Form::text('otros_campos', null, ['class' => 'form-control' . ($errors->has('otros_campos') ? ' is-invalid' : ''), 'placeholder' => 'Otros Campos']) }}
            {!! $errors->first('otros_campos', '<div class="invalid-feedback">:message</div>') !!}
        </div>

        <div class="form-group mt-3">
            {{ Form::submit('Guardar', ['class' => 'btn btn-primary']) }}
        </div>

        {!! Form::close() !!}

    </div>
</div>
