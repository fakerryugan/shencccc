<section id="hrd-panel-lowongan-form" class="view-panel view-hidden">
                    <form id="hrd-form-lowongan" class="bg-white/80 rounded-2xl p-6 border space-y-5 max-w-3xl">
                        <input type="hidden" id="hrd-lowongan-id" value="">
                        <div class="flex flex-wrap justify-between gap-2 items-start">
                            <h3 id="hrd-lowongan-form-heading" class="font-bold text-lg">Lowongan baru</h3>
                            <a href="#lowongan" class="text-sm font-bold text-slate-500 hover:underline">← Daftar lowongan</a>
                        </div>
                        <div>
                            <label class="text-xs font-bold uppercase text-slate-500">Judul posisi (tampil di portal) *</label>
                            <select id="hrd-lowongan-title" required class="mt-1 w-full px-4 py-3 rounded-xl border text-lg font-bold">
                                <option value="">— Pilih dari master posisi —</option>
                            </select>
                            <p class="text-[10px] text-slate-500 mt-1">Daftar dari menu <strong>Kelola Posisi</strong>. Tambah posisi baru di sana jika belum ada.</p>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-bold uppercase text-slate-500">Status</label>
                                <select id="hrd-lowongan-status" class="mt-1 w-full px-4 py-2.5 rounded-xl border text-sm">
                                    <option value="open">Dibuka (hijau)</option>
                                    <option value="filled">Terisi / penuh (merah)</option>
                                </select>
                            </div>
                            <div class="flex items-end">
                                <label class="inline-flex items-center gap-2 text-sm cursor-pointer pb-2">
                                    <input type="checkbox" id="hrd-lowongan-published" checked class="rounded">
                                    <span>Tayangkan di portal pelamar</span>
                                </label>
                            </div>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-4 space-y-3 bg-slate-50/80">
                            <p class="text-xs font-bold text-slate-600">Penutupan otomatis</p>
                            <label class="inline-flex items-center gap-2 text-sm cursor-pointer">
                                <input type="checkbox" id="hrd-lowongan-auto-close" class="rounded">
                                <span>Otomatis tutup saat jumlah pelamar <strong>diterima</strong> mencapai</span>
                            </label>
                            <input type="number" id="hrd-lowongan-auto-limit" min="1" max="9999" value="30" class="w-32 px-3 py-2 rounded-lg border text-sm" placeholder="30">
                            <p class="text-[10px] text-slate-500">Status manual dari dropdown di atas selalu bisa dipakai HRD (mengunci dari perubahan otomatis).</p>
                        </div>
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <label class="text-xs font-bold uppercase text-slate-500">Blok informasi</label>
                                <button type="button" id="hrd-btn-lowongan-add-section" class="text-xs font-bold text-emerald-600">+ Tambah informasi</button>
                            </div>
                            <div id="hrd-lowongan-sections-editor" class="space-y-2"></div>
                            <p class="text-[10px] text-slate-500 mt-2">Judul saja tanpa deskripsi diperbolehkan (mis. header "Waiters").</p>
                        </div>
                        <div class="flex gap-3 pt-2">
                            <button type="submit" class="px-6 py-3 rounded-xl bg-blue-600 text-white font-bold">Simpan</button>
                            <a href="#lowongan" class="px-6 py-3 rounded-xl border font-bold">Batal</a>
                        </div>
                    </form>
                </section>