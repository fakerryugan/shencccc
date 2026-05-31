<section id="hrd-panel-aktivitas" class="view-panel view-hidden space-y-4">
                    <div class="flex flex-wrap gap-3 items-center">
                        <select id="hrd-aktivitas-filter" class="px-4 py-2 rounded-xl border text-sm">
                            <option value="">Semua kegiatan</option>
                            <option value="lamaran_baru">Mengirim lamaran</option>
                        </select>
                        <p class="text-xs text-slate-500">Klik baris untuk membuka profil pelamar.</p>
                    </div>
                    <div class="bg-white/80 rounded-2xl border overflow-hidden">
                        <div id="hrd-aktivitas-list" class="divide-y divide-slate-100"></div>
                        <p id="hrd-aktivitas-empty" class="hidden p-8 text-center text-slate-500">Belum ada aktivitas tercatat.</p>
                        <div id="hrd-aktivitas-list-footer" class="list-page-footer hidden">
                            <p id="hrd-aktivitas-list-count" class="text-center text-xs text-slate-500 mb-2"></p>
                            <button type="button" id="hrd-btn-aktivitas-load-more" class="btn-load-more-list">Muat lebih banyak</button>
                        </div>
                    </div>
                </section>