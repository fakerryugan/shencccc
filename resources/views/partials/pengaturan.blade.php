@verbatim
<section id="hrd-panel-pengaturan" class="view-panel view-hidden space-y-4 max-w-lg">
                    <div class="bg-white/80 rounded-2xl p-6 border space-y-4">
                        <h3 class="font-bold">Pengaturan portal pelamar</h3>
                        <label class="flex items-center gap-3 cursor-pointer"><input type="checkbox" id="hrd-toggle-fake-activity" class="rounded"> <span class="text-sm">Tampilkan notifikasi aktivitas palsu di portal pelamar</span></label>
                    </div>
                    <div class="bg-white/80 rounded-2xl p-6 border space-y-3">
                        <h3 class="font-bold">Template undangan wawancara</h3>
                        <p class="text-xs text-slate-500">Variabel: <code class="text-[10px]">{{nama}}</code> <code class="text-[10px]">{{posisi}}</code> <code class="text-[10px]">{{tanggalMulai}}</code> <code class="text-[10px]">{{tanggalAkhir}}</code> <code class="text-[10px]">{{lokasi}}</code></p>
                        <textarea id="hrd-undangan-template" rows="10" class="w-full px-3 py-2 rounded-xl border text-sm font-mono"></textarea>
                        <button type="button" id="hrd-btn-save-undangan-template" class="px-4 py-2 rounded-xl bg-blue-600 text-white text-sm font-bold">Simpan template</button>
                    </div>
                    <div class="bg-white/80 rounded-2xl p-6 border space-y-3">
                        <h3 class="font-bold">Template chat &mdash; Tidak Diterima</h3>
                        <p class="text-xs text-slate-500">Dikirim otomatis ke chat pelamar saat status diubah menjadi <strong>Tidak Diterima</strong>. Variabel: <code class="text-[10px]">{{nama}}</code></p>
                        <textarea id="hrd-status-reject-template" rows="9" maxlength="2000" class="w-full px-3 py-2 rounded-xl border text-sm font-mono" placeholder="Halo {{nama}}, ..."></textarea>
                        <button type="button" id="hrd-btn-save-status-reject-template" class="px-4 py-2 rounded-xl bg-blue-600 text-white text-sm font-bold">Simpan template</button>
                    </div>
                    <div class="bg-amber-50 rounded-2xl p-6 border border-amber-200 space-y-3">
                        <h3 class="font-bold text-amber-900"><i class="fa-solid fa-database mr-1"></i> Migrasi Firebase (proyek baru)</h3>
                        <ol class="text-xs text-slate-600 space-y-1 list-decimal list-inside">
                            <li>Isi <code class="text-[10px]">firebaseConfig</code> di bagian script file <strong>NEW HRD.html</strong> &amp; <strong>NEW pelamar.html</strong> (project baru).</li>
                            <li>Publish <code class="text-[10px]">firestore.rules</code> yang sama di project baru · aktifkan Anonymous Auth.</li>
                            <li>Dari project <strong>lama</strong>: buka HRD lama → Export, atau gunakan Export di sini jika masih terhubung ke project lama.</li>
                            <li>Di project <strong>baru</strong> (file ini): <strong>Restore</strong> → pilih file JSON.</li>
                        </ol>
                        <div class="flex flex-wrap gap-2 pt-1">
                            <button type="button" id="hrd-btn-export-full-backup" class="px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-bold" title="Unduh semua data Firestore ke file JSON"><i class="fa-solid fa-download mr-1"></i> Export (unduh JSON)</button>
                            <button type="button" id="hrd-btn-restore-full-backup" class="px-4 py-2 rounded-xl bg-violet-600 text-white text-sm font-bold" title="Unggah file JSON hasil Export ke Firestore ini"><i class="fa-solid fa-upload mr-1"></i> Restore (unggah JSON)</button>
                        </div>
                        <p id="hrd-migration-status-hint" class="text-[11px] text-slate-500"><strong>Export</strong> = mengunduh backup. <strong>Restore</strong> = memilih file JSON lalu menulis ke project <code class="text-[10px]">sencha-recruitment-lan</code> (firebaseConfig file ini).</p>
                    </div>
                    <div id="hrd-backup-import-card" class="hidden bg-white/80 rounded-2xl p-6 border space-y-3">
                        <h3 class="font-bold">Import backup JSON (format lama / Cursor)</h3>
                        <p id="hrd-backup-import-hint" class="text-xs text-slate-500"></p>
                        <button type="button" id="hrd-btn-import-backup-run" class="px-4 py-2 rounded-xl bg-violet-600 text-white text-sm font-bold">Pilih file & import pelamar saja</button>
                    </div>
                </section>
@endverbatim