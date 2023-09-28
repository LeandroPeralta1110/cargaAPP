<div>
    <tbody class="bg-white">
        @foreach ($datosProcesadosTipo3 as $index => $fila)
            @if ($index >= $desde && $index < $hasta)
                <tr>
                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200">
                        {{ $fila['tipo_registro'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200">
                        {{ $fila['total_importe'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200">
                        {{ $fila['total_registros'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200">
                        {{ $fila['importe_aceptados'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200">
                        {{ $fila['cantidad_registros_tipo2_aceptados'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200">
                        {{ $fila['importes_rechazados'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200">
                        {{ $fila['cantidad_registros_tipo2_rechazados'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200">
                        {{ $fila['importe_comision'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200">
                        {{ $fila['importe_IVA'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200">
                        {{ $fila['importe_retencion_IVA'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200">
                        {{ $fila['importe_ingreso_bruto'] }}
                    </td>
                    <td class="px-6 py-4 whitespace-no-wrap border-b border-gray-200">
                        {{ $fila['importe_sellado_provincial'] }}
                    </td>
                </tr>
            @endif
        @endforeach
    </tbody>
</div>
