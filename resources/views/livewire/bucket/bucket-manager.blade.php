<div>
    {{-- Header & Aksi --}}
    <div class="cp-card" style="margin-bottom: 1.5rem; display: flex; flex-direction: column; gap: 1rem;">
        
        {{-- Bagian Atas: Info Bucket & Aksi Utama --}}
        <div style="display: flex; flex-direction: column; gap: 1rem;">
            
            {{-- Baris 1: Info Bucket & Tombol Aksi --}}
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; border-bottom: 1px solid #eef5e7; padding-bottom: 1rem;">
                <div>
                    <h2 class="cp-section-title" style="margin-bottom: 0;">🗂️ Bucket: {{ $bucket->bucket_name }}</h2>
                    <div style="font-size: 0.85rem; color: #768971; font-weight: 600; margin-top: 0.4rem; display: flex; align-items: center; gap: 0.4rem;">
                        Path: 
                        <span style="background: #eef5e7; padding: 0.2rem 0.6rem; border-radius: 6px; color: #496443; border: 1px solid #c9dcb9;">
                            {{ $bucket->bucket_name }} / <span style="opacity: 0.8;">{{ $currentPrefix ?: 'Root' }}</span>
                        </span>
                    </div>
                </div>
                
                {{-- Aksi Kanan Atas (Tombol Pengaturan Bucket) --}}
                <div style="display: flex; align-items: center; gap: 0.8rem; flex-wrap: wrap; justify-content: flex-end;">
                    
                    {{-- Tombol Hapus Bucket --}}
                    <button 
                        wire:click.prevent="deleteBucket" 
                        wire:confirm="Yakin ingin menghapus bucket {{ $bucket->bucket_name }} secara permanen? Semua data di dalamnya akan hilang."
                        class="cp-btn" 
                        style="background: #ffebee; color: #c62828; border: 1px solid #ffcdd2; padding: 0.45rem 0.8rem; border-radius: 6px; font-size: 0.85rem; display: flex; align-items: center; gap: 0.3rem; cursor: pointer;"
                    >
                        <span>🗑️</span> Hapus Bucket
                    </button>
                </div>
            </div>

            {{-- Baris 2: Komponen Upload File (Full Width) --}}
            <div style="width: 100%; margin-top: 0.5rem;">
                <livewire:bucket.upload-file 
                    :bucket="$bucket" 
                    :prefix="$currentPrefix" 
                    :key="'upload-cmp-' . $bucket->id . '-' . ($currentPrefix ?: 'root')" 
                />
            </div>

        </div> {{-- ✅ PENUTUP DIV --}}
        {{-- Action Bar: Input Buat Folder --}}
        <div style="display: flex; gap: 0.5rem; align-items: center; border-top: 1px dashed #c9dcb9; padding-top: 1rem; margin-top: 0.5rem;">
            <input type="text" wire:model="newFolderName" placeholder="Nama folder baru..." 
                   style="padding: 0.5rem 0.8rem; border: 1px solid #c9dcb9; border-radius: 6px; font-size: 0.85rem; flex: 1; max-width: 250px; outline: none; color: #455b41; font-weight: 600; background: #fafcf9;">
            
            <button wire:click="createFolder" 
                    class="cp-btn" style="background: #eef5e7; color: #496443; border: 1px solid #c9dcb9; padding: 0.5rem 1rem; box-shadow: none; cursor: pointer;">
                <span wire:loading.remove wire:target="createFolder">📁 Buat Folder</span>
                <span wire:loading wire:target="createFolder">⏳ Proses...</span>
            </button>
        </div>
    </div>

    {{-- Pesan Error/Info --}}
    @if (session()->has('error'))
        <div style="background: #ffebee; color: #c62828; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 700; border: 1px solid #ffcdd2;">
            ⚠️ {{ session('error') }}
        </div>
    @endif
    @if (session()->has('message'))
        <div style="background: #e8f5e9; color: #2e7d32; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem; font-weight: 700; border: 1px solid #c8e6c9;">
            ✅ {{ session('message') }}
        </div>
    @endif

    {{-- Tabel File Explorer --}}
    <h3 class="cp-section-title">📂 File Explorer</h3>
    <div class="cp-table-wrap" wire:on="file-uploaded='$refresh'">
        <table class="cp-table">
            <thead>
                <tr>
                    <th style="width: 40%;">Nama</th>
                    <th style="width: 15%;">Ukuran</th>
                    <th style="width: 25%;">Terakhir Diubah</th>
                    <th style="text-align: right; width: 20%;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                {{-- Baris Kembali (Go Up) --}}
                @if(!empty($currentPrefix))
                    <tr style="background-color: #fafcf9;">
                        <td colspan="4">
                            <button wire:click="goUp" style="background: none; border: none; font-weight: 700; color: #5b7955; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; padding: 0.3rem 0;">
                                <span style="font-size: 1.2rem;">🔙</span> Naik Satu Level (..)
                            </button>
                        </td>
                    </tr>
                @endif

                {{-- Daftar Folder --}}
                @foreach($folders as $folder)
                    <tr style="transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f0f4ec'" onmouseout="this.style.backgroundColor='transparent'">
                        <td>
                            <button wire:click="openFolder('{{ $folder['Prefix'] }}')" style="background: none; border: none; font-weight: 700; color: #45613f; cursor: pointer; display: flex; align-items: center; gap: 0.5rem; text-align: left; width: 100%;">
                                <span style="font-size: 1.2rem;">📁</span> {{ rtrim(str_replace($currentPrefix, '', $folder['Prefix']), '/') }}
                            </button>
                        </td>
                        <td style="color: #768971; font-size: 0.85rem; font-weight: 600;">-</td>
                        <td style="color: #768971; font-size: 0.85rem; font-weight: 600;">-</td>
                        <td style="text-align: right;">
                            <button wire:click="deleteFolder('{{ $folder['Prefix'] }}')" 
                                    wire:confirm="Peringatan: Yakin ingin menghapus folder ini beserta SELURUH isi file di dalamnya?"
                                    style="background: none; border: none; color: #d32f2f; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 0.2rem; font-size: 0.85rem; float: right;">
                                <span>🗑️</span> Hapus
                            </button>
                        </td>
                    </tr>
                @endforeach

                {{-- Daftar File --}}
                @forelse($objects as $object)
                    <tr style="transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f0f4ec'" onmouseout="this.style.backgroundColor='transparent'">
                        <td style="font-weight: 600; color: #45613f; display: flex; align-items: center; gap: 0.5rem;">
                            <span style="font-size: 1.2rem;">📄</span> 
                            <span style="word-break: break-all;">{{ str_replace($currentPrefix, '', $object['Key']) }}</span>
                        </td>
                        <td style="font-size: 0.85rem; color: #768971; font-weight: 600;">
                            {{ number_format($object['Size'] / 1024, 2) }} KB
                        </td>
                        <td style="font-size: 0.85rem; color: #768971; font-weight: 600;">
                            {{ \Carbon\Carbon::parse($object['LastModified'])->timezone('Asia/Jakarta')->format('d M Y, H:i') }}
                        </td>
                        <td style="text-align: right;">
                            <div style="display: flex; justify-content: flex-end; gap: 0.8rem;">
                                <button wire:click="generateDownloadUrl('{{ $object['Key'] }}')" 
                                        style="background: none; border: none; color: #2e7d32; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 0.2rem; font-size: 0.85rem;">
                                    <span>⬇️</span> Unduh
                                </button>
                                <button wire:click="deleteObject('{{ $object['Key'] }}')" 
                                        wire:confirm="Yakin ingin menghapus {{ str_replace($currentPrefix, '', $object['Key']) }}?"
                                        style="background: none; border: none; color: #d32f2f; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 0.2rem; font-size: 0.85rem;">
                                    <span>🗑️</span> Hapus
                                </button>
                                <button wire:click="showFileDetails('{{ $object['Key'] }}')" 
                                        style="background: none; border: none; color: #1976d2; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 0.2rem; font-size: 0.85rem;">
                                    <span>ℹ️</span> Detail
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    @if(empty($folders) && empty($currentPrefix))
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 4rem 1rem; color: #8ca582; font-weight: 600; background: #fafcf9;">
                                <span style="font-size: 3rem; display: block; margin-bottom: 1rem; opacity: 0.6;">📭</span>
                                Bucket ini masih kosong. Silakan upload file atau buat folder baru.
                            </td>
                        </tr>
                    @endif
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Modal File Details (System & Custom Metadata) --}}
    @if($selectedFileDetails)
        <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; display: flex; align-items: center; justify-content: center;">
            <div style="background: white; border-radius: 12px; width: 90%; max-width: 500px; padding: 1.5rem; box-shadow: 0 10px 25px rgba(0,0,0,0.2);">
                
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eef5e7; padding-bottom: 1rem; margin-bottom: 1rem;">
                    <h3 style="margin: 0; color: #455b41; font-size: 1.2rem;">ℹ️ Detail & Metadata</h3>
                    <button wire:click="closeFileDetails" style="background: none; border: none; font-size: 1.5rem; cursor: pointer; color: #999;">&times;</button>
                </div>

                <div style="display: flex; flex-direction: column; gap: 0.8rem;">
                    <div style="background: #fafcf9; padding: 0.8rem; border-radius: 6px; border: 1px solid #eef5e7;">
                        <p style="margin: 0 0 0.4rem 0; font-size: 0.75rem; color: #89a081; font-weight: 700; text-transform: uppercase;">Key / Path</p>
                        <p style="margin: 0; font-size: 0.9rem; font-weight: 600; word-break: break-all;">{{ $selectedFileDetails['Key'] }}</p>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.8rem;">
                        <div style="background: #fafcf9; padding: 0.8rem; border-radius: 6px; border: 1px solid #eef5e7;">
                            <p style="margin: 0 0 0.4rem 0; font-size: 0.75rem; color: #89a081; font-weight: 700; text-transform: uppercase;">Ukuran</p>
                            <p style="margin: 0; font-size: 0.9rem; font-weight: 600;">{{ number_format($selectedFileDetails['ContentLength'] / 1024, 2) }} KB</p>
                        </div>
                        <div style="background: #fafcf9; padding: 0.8rem; border-radius: 6px; border: 1px solid #eef5e7;">
                            <p style="margin: 0 0 0.4rem 0; font-size: 0.75rem; color: #89a081; font-weight: 700; text-transform: uppercase;">Content Type</p>
                            <p style="margin: 0; font-size: 0.9rem; font-weight: 600;">{{ $selectedFileDetails['ContentType'] }}</p>
                        </div>
                    </div>

                    {{-- Custom Metadata --}}
                    <div>
                        <h4 style="margin: 0.5rem 0; color: #455b41; font-size: 1rem;">🏷️ Custom Metadata</h4>
                        @if(empty($selectedFileDetails['Metadata']))
                            <div style="padding: 0.8rem; background: #f5f5f5; border-radius: 6px; text-align: center; color: #888; font-size: 0.85rem; font-style: italic;">
                                Tidak ada metadata kustom pada file ini.
                            </div>
                        @else
                            <div style="border: 1px solid #eef5e7; border-radius: 6px; overflow: hidden;">
                                @foreach($selectedFileDetails['Metadata'] as $key => $val)
                                    <div style="display: flex; border-bottom: 1px solid #eef5e7; padding: 0.6rem 0.8rem;">
                                        <div style="width: 40%; font-weight: 700; font-size: 0.85rem; color: #5b7955;">{{ $key }}</div>
                                        <div style="width: 60%; font-size: 0.85rem; color: #333;">{{ $val }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <div style="margin-top: 1.5rem; text-align: right;">
                    <button wire:click="closeFileDetails" class="cp-btn" style="background: #455b41; color: white; padding: 0.5rem 1.5rem; border: none; border-radius: 6px; cursor: pointer; font-weight: 600;">Tutup</button>
                </div>
            </div>
        </div>
    @endif
</div>