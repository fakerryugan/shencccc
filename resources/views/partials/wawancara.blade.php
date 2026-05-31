<section id="hrd-panel-wawancara" class="view-panel view-hidden space-y-4 max-w-4xl">
                    <div id="hrd-wa-form-wrap" class="bg-white/80 rounded-2xl p-6 border space-y-4">
                        <h3 class="font-bold text-lg">Undangan &amp; jadwal wawancara</h3>
                        <p class="text-xs text-slate-500">Pilih rentang tanggal, posisi, sesi &amp; kuota. Pelamar <strong>Belum Dinilai</strong> pada posisi terpilih akan diundang dan memilih slot lewat chat (tombol Saya hadir).</p>
                        <div>
                            <p class="text-xs font-bold uppercase text-slate-500 mb-2">Pilih divisi (posisi) *</p>
                            <div id="hrd-undangan-posisi-chks" class="flex flex-wrap gap-2"></div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div><label class="text-xs font-bold uppercase text-slate-500">Tanggal mulai *</label>
                                <input type="date" id="hrd-wawancara-tgl-mulai" class="mt-1 w-full px-4 py-2.5 rounded-xl border text-sm"></div>
                            <div><label class="text-xs font-bold uppercase text-slate-500">Tanggal berakhir *</label>
                                <input type="date" id="hrd-wawancara-tgl-akhir" class="mt-1 w-full px-4 py-2.5 rounded-xl border text-sm"></div>
                        </div>
                        <p class="text-xs text-slate-500"><i class="fa-solid fa-location-dot mr-1"></i> Lokasi: <strong>Sencha House</strong></p>
                        <label class="flex items-center gap-2 text-sm cursor-pointer"><input type="checkbox" id="hrd-iv-dual-session" class="rounded"> Dua sesi per hari (pagi &amp; sore)</label>
                        <div id="hrd-iv-single-times" class="grid grid-cols-2 sm:grid-cols-5 gap-3 p-3 rounded-xl bg-slate-50 border">
                            <div class="sm:col-span-2"><label class="text-xs font-bold text-slate-500">Label sesi</label>
                                <input type="text" id="hrd-iv-label-utama" value="Interview" maxlength="40" class="mt-1 w-full px-3 py-2 rounded-lg border text-sm"></div>
                            <div><label class="text-xs font-bold text-slate-500">Jam mulai *</label>
                                <input type="time" id="hrd-iv-jam-mulai" value="08:00" class="mt-1 w-full px-3 py-2 rounded-lg border text-sm"></div>
                            <div><label class="text-xs font-bold text-slate-500">Jam selesai *</label>
                                <input type="time" id="hrd-iv-jam-selesai" value="12:00" class="mt-1 w-full px-3 py-2 rounded-lg border text-sm"></div>
                            <div><label class="text-xs font-bold text-slate-500">Kuota *</label>
                                <input type="number" id="hrd-wawancara-kuota-utama" min="1" max="500" value="10" class="mt-1 w-full px-3 py-2 rounded-lg border text-sm"></div>
                        </div>
                        <div id="hrd-iv-dual-times" class="hidden space-y-3">
                            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 p-3 rounded-xl border border-violet-200 bg-violet-50/50">
                                <div class="sm:col-span-2"><label class="text-xs font-bold text-slate-500">Sesi 1</label>
                                    <input type="text" id="hrd-iv-label-sesi1" value="Pagi" maxlength="40" class="mt-1 w-full px-3 py-2 rounded-lg border text-sm"></div>
                                <div><label class="text-xs font-bold text-slate-500">Jam mulai *</label>
                                    <input type="time" id="hrd-iv-jam-mulai-1" value="08:00" class="mt-1 w-full px-3 py-2 rounded-lg border text-sm"></div>
                                <div><label class="text-xs font-bold text-slate-500">Jam selesai *</label>
                                    <input type="time" id="hrd-iv-jam-selesai-1" value="12:00" class="mt-1 w-full px-3 py-2 rounded-lg border text-sm"></div>
                                <div><label class="text-xs font-bold text-slate-500">Kuota *</label>
                                    <input type="number" id="hrd-wawancara-kuota-sesi1" min="1" max="500" value="10" class="mt-1 w-full px-3 py-2 rounded-lg border text-sm"></div>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 p-3 rounded-xl border border-violet-200 bg-violet-50/50">
                                <div class="sm:col-span-2"><label class="text-xs font-bold text-slate-500">Sesi 2</label>
                                    <input type="text" id="hrd-iv-label-sesi2" value="Sore" maxlength="40" class="mt-1 w-full px-3 py-2 rounded-lg border text-sm"></div>
                                <div><label class="text-xs font-bold text-slate-500">Jam mulai *</label>
                                    <input type="time" id="hrd-iv-jam-mulai-2" value="13:00" class="mt-1 w-full px-3 py-2 rounded-lg border text-sm"></div>
                                <div><label class="text-xs font-bold text-slate-500">Jam selesai *</label>
                                    <input type="time" id="hrd-iv-jam-selesai-2" value="17:00" class="mt-1 w-full px-3 py-2 rounded-lg border text-sm"></div>
                                <div><label class="text-xs font-bold text-slate-500">Kuota *</label>
                                    <input type="number" id="hrd-wawancara-kuota-sesi2" min="1" max="500" value="10" class="mt-1 w-full px-3 py-2 rounded-lg border text-sm"></div>
                            </div>
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer text-sm"><input type="checkbox" id="hrd-undangan-kirim-chat" class="rounded" checked> <span>Kirim undangan ke chat pelamar (disarankan)</span></label>
                        <div class="p-3 rounded-xl bg-slate-50 border text-xs whitespace-pre-wrap" id="hrd-undangan-preview">Pratinjau undangan akan tampil di sini.</div>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" id="hrd-btn-wawancara-terbitkan" class="px-5 py-2.5 rounded-xl bg-violet-600 text-white text-sm font-bold"><i class="fa-solid fa-paper-plane mr-1"></i> Terbitkan &amp; kirim undangan</button>
                        </div>
                        <p id="hrd-undangan-batch-hint" class="text-[10px] text-slate-400"></p>
                    </div>
                    <div id="hrd-wa-monitor-wrap" class="space-y-4">
                        <h3 class="font-bold text-lg">Gelombang wawancara</h3>
                        <div id="hrd-wa-period-list" class="space-y-2"></div>
                        <div id="hrd-wa-period-detail" class="view-hidden"></div>
                    </div>
                </section>