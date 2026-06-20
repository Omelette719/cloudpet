<div>
    {{-- Header & Aksi --}}
    <div style="margin-bottom: 1.5rem;">
        <div style="display: flex; justify-content: space-between; align-items: start;">
            <div>
                <h2 class="cp-section-title" style="margin-bottom: 0;">🗂️ Bucket: {{ $bucket->bucket_name }}</h2>
                <div style="font-size: 0.85rem; color: #768971; font-weight: 600; margin-top: 0.3rem;">
                    Path: <span style="color: #496443;">{{ $bucket->bucket_name }} / {{ $currentPrefix }}</span>
                </div>
            </div>
            
            <div style="display: flex; gap: 0.5rem;">
                {{-- Komponen Upload --}}
                <livewire:bucket.upload-file :bucket="$bucket" :key="$bucket->id" />
            </div>
        </div>

        {{-- Input Buat Folder --}}
        <div style="margin-top: 1rem; display: flex; gap: 0.5rem; align-items: center;">
            <input type="text" id="newFolder" placeholder="Nama folder baru..." style="padding: 0.4rem; border: 1px solid #c9dcb9; border-radius: 6px; font-size: 0.85rem;">
            <button onclick="Livewire.dispatch('create-folder', { name: document.getElementById('newFolder').value })" 
                    class="cp-btn" style="background: #eef5e7; color: #496443; border: 1px solid #c9dcb9; padding: 0.4rem 1rem; border-radius: 6px; font-weight: bold; cursor: pointer;">
                + Buat Folder
            </button>
        </div>
    </div>

    {{-- Pesan Error/Info --}}
    @if (session()->has('error'))
        <div style="background: #ffebee; color: #c62828; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; font-weight: bold;">
            {{ session('error') }}
        </div>
    @endif
    @if (session()->has('message'))
        <div style="background: #e8f5e9; color: #2e7d32; padding: 1rem; border-radius: 8px; margin-bottom: 1rem; font-weight: bold;">
            {{ session('message') }}
        </div>
    @endif

    {{-- Tabel File Explorer --}}
    <div class="cp-table-wrap" wire:on="file-uploaded='$refresh'">
        <table class="cp-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Nama</th>
                    <th>Ukuran</th>
                    <th>Terakhir Diubah</th>
                    <th style="text-align: right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                {{-- Baris Kembali (Go Up) --}}
                @if(!empty($currentPrefix))
                    <tr>
                        <td colspan="4">
                            <button wire:click="goUp" style="background: none; border: none; font-weight: bold; color: #5b7955; cursor: pointer; display: flex; align-items: center; gap: 0.5rem;">
                                <span>🔙</span> .. (Kembali)
                            </button>
                        </td>
                    </tr>
                @endif

                {{-- Daftar Folder --}}
                @foreach($folders as $folder)
                    <tr>
                        <td>
                            <button wire:click="openFolder('{{ $folder['Prefix'] }}')" style="background: none; border: none; font-weight: bold; color: #45613f; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; text-align: left;">
                                <span style="font-size: 1.2rem;">📁</span> {{ str_replace($currentPrefix, '', $folder['Prefix']) }}
                            </button>
                        </td>
                        <td>-</td>
                        <td>-</td>
                        <td style="text-align: right;">-</td>
                    </tr>
                @endforeach

                {{-- Daftar File --}}
                @forelse($objects as $object)
                    <tr>
                        <td style="font-weight: 600; color: #45613f; display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-size: 1.2rem;">📄</span> {{ str_replace($currentPrefix, '', $object['Key']) }}
                        </td>
                        <td style="font-size: 0.85rem; color: #768971;">
                            {{ number_format($object['Size'] / 1024, 2) }} KB
                        </td>
                        <td style="font-size: 0.85rem; color: #768971;">
                            {{ \Carbon\Carbon::parse($object['LastModified'])->timezone('Asia/Jakarta')->format('d M Y H:i') }}
                        </td>
                        <td style="text-align: right;">
                            <button wire:click="generateDownloadUrl('{{ $object['Key'] }}')" 
                                    style="background: none; border: none; color: #2e7d32; font-weight: bold; cursor: pointer; margin-right: 15px;">
                                ⬇️ Download
                            </button>
                            <button wire:click="deleteObject('{{ $object['Key'] }}')" 
                                    wire:confirm="Yakin ingin menghapus {{ str_replace($currentPrefix, '', $object['Key']) }}?"
                                    style="background: none; border: none; color: #d32f2f; font-weight: bold; cursor: pointer;">
                                Hapus
                            </button>
                        </td>
                    </tr>
                @empty
                    @if(empty($folders) && empty($currentPrefix))
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 2rem; color: #8ca582; font-weight: 600;">
                                <span style="font-size: 2rem; display: block; margin-bottom: 0.5rem;">📭</span>
                                Bucket ini kosong.
                            </td>
                        </tr>
                    @endif
                @endforelse
            </tbody>
        </table>
    </div>
</div>