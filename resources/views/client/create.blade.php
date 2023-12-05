<x-app-layout>
    <section class="content container mx-auto">
        <div class="row">
            <div class="col-md-12">

                @includeif('partials.errors')

                <div class="bg-white shadow-lg rounded-lg overflow-hidden">
                    <div class="p-4 border-b">
                        <span class="text-xl font-bold">{{ __('Create') }} Client</span>
                    </div>
                    <div class="p-4">
                        <form method="POST" action="{{ route('clients.store') }}" role="form" enctype="multipart/form-data">
                            @csrf
                            @include('client.form')
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </section>
</x-app-layout>
