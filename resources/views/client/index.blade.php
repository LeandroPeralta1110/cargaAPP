<x-app-layout>
<div class="container mx-auto">
    <div class="bg-white shadow-lg rounded-lg overflow-hidden">
        <div class="p-4 border-b">
            <div class="flex justify-between items-center">
                <span class="text-xl font-bold">{{ __('Client') }}</span>
                <div class="space-x-2">
                    <a href="{{ route('clients.create') }}" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                        {{ __('Create New') }}
                    </a>
                </div>
            </div>
        </div>

        @if ($message = Session::get('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4">
                <p>{{ $message }}</p>
            </div>
        @endif

        <div class="p-4">
            <div class="overflow-x-auto">
                <table class="min-w-full border border-gray-300">
                    <thead>
                        <tr>
                            <th class="border-b p-2">#</th>
                            <th class="border-b p-2">Client Id</th>
                            <th class="border-b p-2">Razon Social</th>
                            <th class="border-b p-2">Telefono</th>
                            <th class="border-b p-2">Email</th>
                            <th class="border-b p-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($clients as $client)
                            <tr>
                                <td class="border-b p-2">{{ ++$i }}</td>
                                <td class="border-b p-2">{{ $client->client_id }}</td>
                                <td class="border-b p-2">{{ $client->razon_social }}</td>
                                <td class="border-b p-2">{{ $client->telefono }}</td>
                                <td class="border-b p-2">{{ $client->email }}</td>
                                <td class="border-b p-2">
                                    <div class="space-x-2">
                                        <a href="{{ route('clients.show', $client->client_id) }}" class="bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-600">
                                            <i class="fa fa-fw fa-eye"></i> {{ __('Show') }}
                                        </a>                                        
                                        <a href="{{ route('clients.edit',$client->client_id) }}" class="bg-green-500 text-white px-2 py-1 rounded hover:bg-green-600">
                                            <i class="fa fa-fw fa-edit"></i> {{ __('Edit') }}
                                        </a>
                                        <form action="{{ route('clients.destroy',$client->client_id) }}" method="POST" class="inline-block">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600">
                                                <i class="fa fa-fw fa-trash"></i> {{ __('Delete') }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="p-4">
            {!! $clients->links() !!}
        </div>
    </div>
</div>
</x-app-layout>
