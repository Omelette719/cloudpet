<div class="p-6 bg-white rounded-lg shadow-md mt-6">
    <h3 class="text-lg font-semibold mb-4">Demo Upload Foto Pet (S3 / MinIO)</h3>

    @if (session()->has('message'))
        <div class="p-3 mb-4 text-green-700 bg-green-100 rounded">
            ✅ {{ session('message') }}
        </div>
    @endif
    
    @if (session()->has('error'))
        <div class="p-3 mb-4 text-red-700 bg-red-100 rounded">
            ❌ {{ session('error') }}
        </div>
    @endif

    <form wire:submit.prevent="save" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Pilih Foto Pet</label>
            <input 
                type="file" 
                wire:model.defer="photo" 
                accept="image/*" 
                class="block w-full text-sm text-gray-500
                    file:mr-4 file:py-2 file:px-4
                    file:rounded-md file:border-0
                    file:text-sm file:font-semibold
                    file:bg-indigo-50 file:text-indigo-700
                    hover:file:bg-indigo-100"
            >
            @error('photo') 
                <span class="text-red-500 text-sm block mt-1">{{ $message }}</span> 
            @enderror
        </div>

        @if ($photo)
            <div class="mt-2">
                <p class="text-sm text-gray-500 mb-1">Preview:</p>
                <img src="{{ $photo->temporaryUrl() }}" class="w-32 h-32 object-cover rounded shadow">
            </div>
        @endif

        <button 
            type="submit" 
            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition disabled:opacity-50" 
            wire:loading.attr="disabled"
        >
            <span wire:loading.remove wire:target="save">📤 Upload Foto</span>
            <span wire:loading wire:target="save">⏳ Mengupload...</span>
        </button>
    </form>

    @if ($uploadedUrl)
        <div class="mt-6 p-4 border border-green-200 rounded bg-green-50">
            <h4 class="font-medium text-sm text-green-700 mb-3">✅ Hasil Upload:</h4>
            <div class="space-y-3">
                <div>
                    <p class="text-xs text-gray-600 mb-1 font-semibold">📍 URL:</p>
                    <div class="bg-white p-2 rounded border border-gray-200">
                        <p class="text-xs font-mono break-all">{{ $uploadedUrl }}</p>
                    </div>
                </div>
                <div>
                    <p class="text-xs text-gray-600 mb-1 font-semibold">🖼️ Preview:</p>
                    <img 
                        src="{{ $uploadedUrl }}" 
                        alt="Uploaded Pet" 
                        class="w-48 rounded shadow" 
                        onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22192%22 height=%22192%22%3E%3Crect fill=%22%23f0f0f0%22 width=%22192%22 height=%22192%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-family=%22Arial%22 font-size=%2214%22 fill=%22%23999%22%3EImage not available%3C/text%3E%3C/svg%3E'"
                    >
                </div>
            </div>
        </div>
    @endif
</div>
