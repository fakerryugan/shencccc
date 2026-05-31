<section id="hrd-panel-dashboard" class="view-panel space-y-4">
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                        <div class="bg-white/80 rounded-2xl p-4 border"><p class="text-[10px] font-bold text-slate-400 uppercase">Total</p><p id="hrd-stat-total" class="text-2xl font-black text-blue-700">0</p></div>
                        <div class="bg-white/80 rounded-2xl p-4 border"><p class="text-[10px] font-bold text-slate-400 uppercase">Belum dinilai</p><p id="hrd-stat-belum" class="text-2xl font-black">0</p></div>
                        <div class="bg-white/80 rounded-2xl p-4 border"><p class="text-[10px] font-bold text-slate-400 uppercase">Pertimbangkan</p><p id="hrd-stat-pertimbangkan" class="text-2xl font-black text-amber-600">0</p></div>
                        <div class="bg-white/80 rounded-2xl p-4 border"><p class="text-[10px] font-bold text-slate-400 uppercase">Diterima</p><p id="hrd-stat-diterima" class="text-2xl font-black text-emerald-600">0</p></div>
                        <div class="bg-white/80 rounded-2xl p-4 border"><p class="text-[10px] font-bold text-slate-400 uppercase">Tidak cocok</p><p id="hrd-stat-tidak-cocok" class="text-2xl font-black text-red-600">0</p></div>
                    </div>
                    <div id="hrd-status-legend" class="flex flex-wrap gap-2"></div>
                    <div class="bg-white/80 rounded-2xl p-5 border"><h3 class="font-bold mb-3">Posisi teratas</h3><div id="hrd-stat-posisi" class="space-y-2 text-sm"></div></div>
                    <div class="flex flex-wrap gap-2">
                        <a href="#pelamar/baru" class="px-5 py-3 rounded-xl bg-blue-600 text-white font-bold text-sm"><i class="fa-solid fa-plus mr-1"></i> Tambah pelamar</a>
                        <button type="button" id="hrd-btn-import-backup-dash" class="px-5 py-3 rounded-xl border font-bold text-sm"><i class="fa-solid fa-file-import mr-1"></i> Import backup JSON</button>
                    </div>
                </section>