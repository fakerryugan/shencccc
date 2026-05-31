<section id="hrd-panel-form" class="view-panel view-hidden">
                    <form id="hrd-form-pelamar" class="bg-white/80 rounded-2xl p-6 border space-y-6 max-w-3xl">
                        <input type="hidden" id="hrd-form-id" value="">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div><label class="text-xs font-bold uppercase text-slate-500">Nama *</label><input type="text" id="hrd-form-nama" required maxlength="120" class="mt-1 w-full px-4 py-3 rounded-xl border"></div>
                            <div><label class="text-xs font-bold uppercase text-slate-500">Tanggal lahir *</label><input type="date" id="hrd-form-tgl-lahir" required class="mt-1 w-full px-4 py-3 rounded-xl border"><p class="mt-1 text-sm text-blue-600 font-semibold">Umur: <span id="hrd-form-umur-preview">—</span> tahun</p></div>
                            <div class="md:col-span-2"><label class="text-xs font-bold uppercase text-slate-500">WhatsApp</label><input type="tel" id="hrd-form-whatsapp" placeholder="08..." class="mt-1 w-full px-4 py-3 rounded-xl border"></div>
                            <div><label class="text-xs font-bold uppercase text-slate-500">Masih bekerja?</label><select id="hrd-form-masih-bekerja" class="mt-1 w-full px-4 py-3 rounded-xl border text-sm"><option value="">— Pilih —</option><option value="ya">Ya</option><option value="tidak">Tidak</option></select></div>
                            <div class="md:col-span-2"><label class="text-xs font-bold uppercase text-slate-500">Posisi (maks. 2) *</label><div id="hrd-form-posisi-multi" class="flex flex-wrap gap-2 mt-2"></div></div>
                            <input type="hidden" id="hrd-form-posisi" value="">
                            <div><label class="text-xs font-bold uppercase text-slate-500">Status</label><select id="hrd-form-status" class="mt-1 w-full px-4 py-3 rounded-xl border"></select></div>
                        </div>
                        <div><label class="text-xs font-bold uppercase text-slate-500 block mb-2">Foto diri *</label>
                            <p class="text-[10px] text-slate-400 mb-2">Satu foto wajib (konsisten dengan portal pelamar).</p>
                            <div class="max-w-[180px]">
                                <div class="photo-slot bg-slate-50 flex flex-col items-center justify-center text-slate-400" data-photo-idx="0"><i class="fa-solid fa-camera text-2xl mb-1"></i><span class="text-[10px] font-bold">Foto diri</span></div>
                            </div>
                        </div>
                        <div><label class="text-xs font-bold uppercase text-slate-500 block mb-2">Dokumen lamaran (opsional)</label>
                            <p class="text-[10px] text-slate-400 mb-2">PDF saja <em>atau</em> hingga 4 foto CV — tidak keduanya.</p>
                            <div class="cv-slot-hrd mb-3" data-cv-pdf-hrd>
                                <p class="text-xs font-bold text-blue-600">CV / lamaran (PDF)</p>
                                <span class="cv-slot-label text-sm font-semibold block mt-1">Klik untuk unggah PDF</span>
                            </div>
                            <p class="text-[10px] text-slate-400 font-bold mb-2 text-center">— atau foto / screenshot CV —</p>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                <div class="doc-photo-slot-hrd text-slate-400" data-doc-idx="0"><i class="fa-solid fa-file-image text-xl mb-1"></i><span class="text-[10px] font-bold">CV 1</span></div>
                                <div class="doc-photo-slot-hrd text-slate-400" data-doc-idx="1"><i class="fa-solid fa-file-image text-xl mb-1"></i><span class="text-[10px] font-bold">CV 2</span></div>
                                <div class="doc-photo-slot-hrd text-slate-400" data-doc-idx="2"><i class="fa-solid fa-file-image text-xl mb-1"></i><span class="text-[10px] font-bold">CV 3</span></div>
                                <div class="doc-photo-slot-hrd text-slate-400" data-doc-idx="3"><i class="fa-solid fa-file-image text-xl mb-1"></i><span class="text-[10px] font-bold">CV 4</span></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between items-center mb-2"><label class="text-xs font-bold uppercase text-slate-500">Catatan HRD</label><button type="button" id="hrd-btn-tambah-catatan" class="text-xs font-bold text-emerald-600">+ Tambah catatan</button></div>
                            <div id="hrd-form-catatan-list" class="space-y-2"></div>
                        </div>
                        <div class="flex gap-3"><button type="submit" class="px-6 py-3 rounded-xl bg-blue-600 text-white font-bold">Simpan</button><a href="#pelamar" class="px-6 py-3 rounded-xl border font-bold">Batal</a></div>
                    </form>
                </section>