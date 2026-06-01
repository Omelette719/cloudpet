<div class="p-6 bg-white rounded-lg shadow-md mt-6">
    <h3 class="text-lg font-semibold mb-4">Demo Upload Foto Pet (S3 / MinIO)</h3>

    @if (session()->has('message'))
        <div class="p-3 mb-4 text-green-700 bg-green-100 rounded">
            {{ session('message') }}
        </div>
    @endif
    
    @if (session()->has('error'))
        <div class="p-3 mb-4 text-red-700 bg-red-100 rounded">
            {{ session('error') }}
        </div>
    @endif

    <form wire:submit.prevent="save" class="space-y-4">
        <div>
            <input type="file" wire:model="photo" class="block w-full text-sm text-gray-500
                file:mr-4 file:py-2 file:px-4
                file:rounded-md file:border-0
                file:text-sm file:font-semibold
                file:bg-indigo-50 file:text-indigo-700
                hover:file:bg-indigo-100"
            >
            @error('photo') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        @if ($photo)
            <div class="mt-2">
                <p class="text-sm text-gray-500 mb-1">Preview:</p>
                <img src="{{ $photo->temporaryUrl() }}" class="w-32 h-32 object-cover rounded shadow">
            </div>
        @endif

        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700" wire:loading.attr="disabled">
            <span wire:loading.remove wire:target="save">Upload Foto</span>
            <span wire:loading wire:target="save">Mengupload...</span>
        </button>
    </form>

    @if ($uploadedUrl)
        <div class="mt-6 p-4 border border-gray-200 rounded">
            <h4 class="font-medium text-sm text-gray-700 mb-2">Hasil di Bucket MinIO:</h4>
            <a href="{{ $uploadedUrl }}" target="_blank" class="text-blue-500 text-sm break-all hover:underline mb-2 block">
                {{ $uploadedUrl }}
            </a>
            <img src="{{ $uploadedUrl }}" alt="Uploaded Pet" class="w-48 mt-2 rounded shadow">
        </div>
    @endif
</div>