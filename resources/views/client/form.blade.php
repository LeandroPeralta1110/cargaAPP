<div class="box box-info padding-1">
    <div class="box-body">
        
        <div class="form-group">
            {{ Form::label('client_id') }}
            {{ Form::text('client_id', $client->client_id, ['class' => 'form-control', 'placeholder' => 'Client Id']) }}
        </div>
        <div class="form-group">
            {{ Form::label('razon_social') }}
            {{ Form::text('razon_social', $client->razon_social, ['class' => 'form-control', 'placeholder' => 'Razon Social']) }}
        </div>
        <div class="form-group">
            {{ Form::label('telefono') }}
            {{ Form::text('telefono', $client->telefono, ['class' => 'form-control', 'placeholder' => 'Telefono']) }}
        </div>
        <div class="form-group">
            {{ Form::label('email') }}
            {{ Form::text('email', $client->email, ['class' => 'form-control', 'placeholder' => 'Email']) }}
        </div>

    </div>
    <div class="box-footer mt20">
        <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
    </div>
</div>